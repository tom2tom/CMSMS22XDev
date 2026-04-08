<?php
# BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: FilePicker - a CMSMS addon module to provide file picking capabilities.
# (c) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#-------------------------------------------------------------------------
# This file is part of FilePicker
# FilePicker is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# FilePicker is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#-------------------------------------------------------------------------
# END_LICENSE

use CMSMS\FilePickerInterface;
use CMSMS\FilePickerProfile;
use CMSMS\FileType;
use CMSMS\FileTypeHelper;
use CMSMS\HookManager;
use FilePicker\Profile;
use FilePicker\ProfileDAO;
use FilePicker\ProfilesCache;

require_once cms_join_path(__DIR__, 'lib', 'class.ProfileDAO.php');

final class FilePicker extends CMSModule implements FilePickerInterface
{
    protected $_dao;
    protected $_typehelper;
    protected $macos;
    protected $winos;

    public function __construct()
    {
        parent::__construct();
        $this->_dao = new ProfileDAO($this);
        $this->_typehelper = new FileTypeHelper();
    }

    private function _encodefilename($filename)
    {
        return str_replace('==', '', base64_encode($filename));
    }

    private function _decodefilename($encodedfilename)
    {
        return base64_decode($encodedfilename . '==');
    }

    private function _GetTemplateObject()
    {
        $ret = $this->GetActionTemplateObject();
        if( is_object($ret) ) return $ret;
        return cmsms()->GetSmarty();
    }

    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetDependencies() { return array('FileManager' => '0.4'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetVersion() { return '1.1'; }
    public function HasAdmin() { return TRUE; }
    public function MinimumCMSVersion() { return '2.2'; }
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function HasCapability( $capability, $params = array() )
    {
        switch( $capability ) {
            case CmsCoreCapabilities::CONTENT_BLOCKS:
            case CmsCoreCapabilities::TASKS:
            case 'filepicker':
            case 'upload':
                return TRUE;
            default:
                return FALSE;
        }
    }

    public function GetAdminMenuItems()
    {
        if( $this->VisibleToAdminUser() ) {
            $obj = CmsAdminMenuItem::from_module($this);
            $obj->section = 'siteadmin';
            $obj->title = $this->Lang('title_filepicker_settings');
            $obj->description = $this->Lang('desc_filepicker_settings');
            return [$obj];
        }
        return [];
    }

    public function get_tasks()
    {
        $fp = cms_join_path(__DIR__,'lib','class.ResetCounterJob.php');
        if( is_file($fp) ) {
            if( func_num_args() > 0 && func_get_arg(0) ) { // want filepath(s)
                return [$fp];
            } else {
                require_once $fp;
                return [new FilePicker\ResetCounterJob()];
            }
        }
        return [];
    }

    /**
     *{content_module} tag setup
     *
     * @param string $blockName
     * @param string $value
     * @param array $params
     * @param bool $adding UNUSED
     * @param ContentBase $content_obj UNUSED
     * @return string
     */
    public function GetContentBlockFieldInput($blockName, $value, array $params, $adding, ContentBase $content_obj)
    {
        if( !$blockName ) return '';
        $uid = get_userid(FALSE);
        $profile_name = get_parameter_value($params, 'profile');
        $profile = $this->get_profile_or_default($profile_name, '', $uid);
        if( $params ) {
            unset($params['profile'],
                $params['top'], // no top-folder change allowed here
                $params['can_delete'],// no writability-change allowed here
                $params['can_mkdir'],
                $params['can_upload']);
            // TODO any other relevant property-additions or -limitations?
            $profile->overrideWith($params);
        }
        return $this->get_html($blockName, $value, $profile);
    }

    /**
     * {content_module} tag retriever
     *
     * @param string $blockName
     * @param array $blockParams UNUSED
     * @param array $inputParams
     * @param ContentBase $content_obj UNUSED
     * @return string maybe empty
     */
    public function GetContentBlockFieldValue($blockName, array $blockParams, $inputParams, ContentBase $content_obj)
    {
        if( $blockName && isset($inputParams[$blockName]) ) {
            return $inputParams[$blockName];
        }
        return ''; // should never happen
    }

    /**
     * {content_module} tag validator
     *
     * @param string $blockName
     * @param string $value url
     * @param array $blockparams
     * @param ContentBase $content_obj UNUSED
     * @return string empty or error-message
     */
    public function ValidateContentBlockFieldValue($blockName, $value, array $blockparams, ContentBase $content_obj)
    {
        $value = trim((string)$value);
        if( $value ) {
            $res = cms_utils::validate_url($value, '!' . FileType::TYPE_EXECUTABLE);
            if( $res !== TRUE ) return $res;
        }
        elseif( isset($blockparams['required']) ) {
            return $this->lang('err_missing_value', $blockName);
        }
        return '';
    }

    /**
     * {content_module} tag renderer
     * If $blockparams includes a member 'format', then values
     * 'absolute' 'anchor' and 'image' may be specified for that parameter
     *
     * @param string $blockName
     * @param string $value
     * @param array $blockparams
     * @param ContentBase $content_obj UNUSED
     * @return string maybe empty or $value
     */
    public function RenderContentBlockField($blockName, $value, array $blockparams, ContentBase $content_obj)
    {
        $value = trim((string)$value);
        if( $value ) {
            if( !empty($blockparams['format']) ) {
                switch ($blockparams['format']) {
                    case 'absolute':
                        if ($value[0] == '/' && $value[1] != '/') return CMS_ROOT_URL . $value;
                        break;
                    case 'anchor':
                        $url = ($value[0] == '/' && $value[1] != '/') ? CMS_ROOT_URL . $value : $value;
                        return "<a href=\"$url\" target=\"_blank\">$blockName</a>";
                        break;
                    case 'image':
                        $url = ($value[0] == '/' && $value[1] != '/') ? CMS_ROOT_URL . $value : $value;
                        return "<img src=\"$url\" alt=\"$blockName image\" title=\"$blockName\">";
                        break;
                }
            }
            return $value;
        }
        return '';
    }

    /**
     *
     * @param string $path Default '' hence filemanager_utils::get_cwd()
     * @return array
     */
    public function GetFileList($path = '')
    {
        $sortby = 'nameasc'; //TODO 'none' if relevant profile has no sorting
        return filemanager_utils::get_file_list($path, $sortby);
    }

    /**
     *
     * @param string $profile_name
     * @param string $dir Directory path Default ''
     * @param int $uid Current user numeric id or < 0 for frontend use Default 0
     * @return Profile
     */
    public function get_profile_or_default($profile_name, $dir = '', $uid = 0)
    {
        $profile_name = trim((string)$profile_name);
        $profile = ($profile_name) ? $this->_dao->loadByName($profile_name) : null;
        if ($profile) {
            if ($uid < 1 || !check_permission($uid,'Modify Files')) {
                $profile->overrideWith([
                'can_delete' => FilePickerProfile::FLAG_NONE,
                'can_mkdir' => FilePickerProfile::FLAG_NONE,
                'can_upload' => FilePickerProfile::FLAG_NONE
                ]);
            }
        } else {
            $profile = $this->get_default_profile($dir, $uid);
        }
        return $profile;
    }

    /**
     *
     * @param string $dir Directory path Default ''
     * @param int $uid  Current user numeric id or < 0 for frontend use Default 0
     * @return Profile
     */
    public function get_default_profile($dir = '', $uid = 0)
    {
        // TODO try for a specific default for dir+uid
        $profile = $this->_dao->loadDefault(); // generic default if any
        if (!$profile) { $profile =  new Profile(); }
        if ($uid < 1 || !check_permission($uid,'Modify Files')) {
            $profile->overrideWith([
            'can_delete' => FilePickerProfile::FLAG_NONE,
            'can_mkdir' => FilePickerProfile::FLAG_NONE,
            'can_upload' => FilePickerProfile::FLAG_NONE
            ]);
        }
        return $profile;
    }

    /**
     *
     * @return string
     */
    public function get_browser_url()
    {
        return $this->create_url('m1_', 'filepicker');
    }

    /**
     *
     * @staticvar bool $scripted
     * @param string $name
     * @param string $value
     * @param Profile $profile
     * @param bool $required
     * @return mixed string | null
     */
    public function get_html($name, $value, Profile $profile, $required = FALSE)
    {
        static $scripted = false;
        if( !$scripted ) { // once-per-request
            HookManager::add_hook('admin_add_headtext', function() {
                $root_url = CMS_ROOT_URL;
                return "<script src=\"$root_url/lib/jquery/js/jquery.cmsms_filepicker.js\" defer></script>\n";
            });
            $scripted = true;
        }

        $_instance = 'i'.uniqid();
        if( $value === '-1' ) $value = '';

        $sig = ProfilesCache::get_instance()->set($profile);

        switch( $profile->type ) {
        case FileType::TYPE_IMAGE:
            $key = 'select_an_image';
            break;
        case FileType::TYPE_AUDIO:
            $key = 'select_an_audio_file';
            break;
        case FileType::TYPE_VIDEO:
            $key = 'select_a_video_file';
            break;
        case FileType::TYPE_MEDIA:
            $key = 'select_a_media_file';
            break;
        case FileType::TYPE_XML:
            $key = 'select_an_xml_file';
            break;
        case FileType::TYPE_DOCUMENT:
            $key ='select_a_document';
            break;
        case FileType::TYPE_ARCHIVE:
            $key = 'select_an_archive_file';
            break;
//      case FileType::TYPE_ANY:
        default:
            $key = 'select_a_file';
            break;
        }

        $smarty = cmsms()->GetSmarty(); // OR $this->_GetTemplateObject() ?
        $modname = $this->GetName();
        $tpl = $smarty->createTemplate("module_file_tpl:$modname;contentblock.tpl", null, $modname, $smarty);
        $tpl->assign('mod', $this)
         ->assign('sig', $sig)
         ->assign('blockName', $name)
         ->assign('value', $value)
         ->assign('instance', $_instance)
         ->assign('profile', $profile)
         ->assign('required', $required)
         ->assign('title', $this->Lang($key));
        return $tpl->fetch();
    }

    /**
     * Report whether the supplied filepath is for an image
     *
     * @param string $fullpath
     * @return bool
     */
    public function is_image($fullpath)
    {
        $fullpath = trim((string)$fullpath);
        if( !$fullpath ) return FALSE;

        return $this->_typehelper->is_image($fullpath);
    }

    /**
     * Report whether the supplied filepath is acceptable in terms of the
     * supplied Profile properties
     *
     * @param Profile $profile
     * @param string $fullpath
     * @return bool
     */
    public function is_acceptable_filename(Profile $profile, $fullpath)
    {
        $fullpath = trim((string)$fullpath);
        $basename = basename($fullpath);
        if( !$basename ) { return FALSE; }
        if( strncasecmp($basename, 'index.htm', 9) == 0 ) { return FALSE; }
        if( endswith($basename, '.') ) { return FALSE; }
        if( !$profile->show_hidden ) {
            if( filemanager_utils::is_hidden_file($fullpath) ) { return FALSE; }
        }

        if( !isset($this->winos) ) {
            if( function_exists('php_uname') && ($tmp = php_uname('s')) ) { //might return null (undocumented)
                $this->winos = stripos($tmp,'windo') !== false;// running on some flavour of Windows
            }
            else {
                $this->winos = (PATH_SEPARATOR == ';');
            }
        }
        $flags = FNM_PATHNAME | FNM_PERIOD;
        if( $this->winos ) { $flags |= FNM_CASEFOLD; }

        if( $profile->exclude_prefix && fnmatch($profile->exclude_prefix.'*', $basename, $flags) ) {
            return FALSE;
        }

        switch( $profile->type ) {
        case FileType::TYPE_IMAGE:
            if( !$this->_typehelper->is_image($fullpath) ) { return FALSE; }
            break;

        case FileType::TYPE_AUDIO:
            if( !$this->_typehelper->is_audio($fullpath) ) { return FALSE; }
            break;

        case FileType::TYPE_VIDEO:
            if( !$this->_typehelper->is_video($fullpath) ) { return FALSE; }
            break;

        case FileType::TYPE_MEDIA:
            if( !$this->_typehelper->is_media($fullpath) ) { return FALSE; }
            break;

        case FileType::TYPE_XML:
            if( !$this->_typehelper->is_xml($fullpath) ) { return FALSE; }
            break;

        case FileType::TYPE_DOCUMENT:
            if( !$this->_typehelper->is_document($fullpath) ) { return FALSE; }
            break;

        case FileType::TYPE_ARCHIVE:
            if( !$this->_typehelper->is_archive($fullpath) ) { return FALSE; }
            break;

        default:
            if( $this->_typehelper->is_executable($fullpath) ) {
                $config = cms_config::get_instance();
                if( empty($config['developer_mode']) ) {
                    return FALSE;
                }
            }
        }

        if( $profile->match_prefix && !fnmatch($profile->match_prefix.'*', $basename, $flags) ) {
            return FALSE;
        }
        return TRUE;
    }
} // class
