<?php
# BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: FilePicker - a CMSMS addon module to provide file picking capabilities.
# (c) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
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
//use CMSMS\FilePickerProfile;
use CMSMS\FileType;
use CMSMS\FileTypeHelper;
use FilePicker\Profile;
use FilePicker\ProfileDAO;
use FilePicker\TemporaryProfileStorage;

require_once cms_join_path(__DIR__, 'lib', 'class.ProfileDAO.php');

final class FilePicker extends CMSModule implements FilePickerInterface
{
    protected $_dao;
    protected $_typehelper;

    public function __construct()
    {
        parent::__construct();
        $this->_dao = new ProfileDAO( $this );
        $this->_typehelper = new FileTypeHelper( cms_config::get_instance() );
    }

    private function _encodefilename($filename)
    {
        return str_replace('==', '', base64_encode($filename));
    }

    private function _decodefilename($encodedfilename)
    {
        return base64_decode($encodedfilename . '==');
    }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Modify Site Preferences');
    }

    private function _GetTemplateObject()
    {
        $ret = $this->GetActionTemplateObject();
        if( is_object($ret) ) return $ret;
        return cmsms()->GetSmarty();
    }

    public function GetAdminDescription() { return $this->Lang('moddescription'); }
//  public function GetAdminSection() { return 'extensions'; } default
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetDependencies() { return array('FileManager' => '0.4'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetVersion() { return '1.0.7'; }
    public function HasAdmin() { return TRUE; }
//  public function IsAdminOnly() { return FALSE; } default
//  public function IsPluginModule() { return FALSE; } default
    public function MinimumCMSVersion() { return '2.2'; }

    public function HasCapability( $capability, $params = array() )
    {
        switch( $capability ) {
            case 'contentblocks': // aka CmsCoreCapabilities::CONTENT_BLOCKS
            case 'filepicker':
            case 'upload':
                return TRUE;
            default:
                return FALSE;
        }
    }

    public function GetAdminMenuItems()
    {
        $out = array();
        if( $this->VisibleToAdminUser() ) {
            $out[] = CmsAdminMenuItem::from_module($this);
        }
        return $out;
    }

    public function GetContentBlockFieldInput($blockName, $value, $params, $adding, ContentBase $content_obj)
    {
        if( !$blockName ) return '';
        // workaround $adding not set in ContentManager v1.0 action admin_editcontent
        if (!$adding && version_compare(CMS_VERSION, '2.1') < 0 && $content_obj->Id() == 0) {
            $adding = true; //TODO no profile-relevance
        }
        $uid = get_userid(FALSE);
        $profile_name = get_parameter_value($params, 'profile');
        $profile = $this->get_profile_or_default($profile_name, '', $uid);
        if( $params ) {
            unset($params['profile']);
            unset($params['top']); // no top-folder change allowed here
/* TODO any other relevant limitations? e.g.
            if( !$this->CheckPermission('Modify Files') ) {
                $params['can_upload'] = FilePickerProfile::FLAG_NONE;
                $params['can_delete'] = FilePickerProfile::FLAG_NONE;
                $params['can_mkdir'] = FilePickerProfile::FLAG_NONE;
            }
*/
            $profile = $profile->overrideWith($params);
        }
        return $this->get_html($blockName, $value, $profile);
    }

    public function GetContentBlockFieldValue($blockName, $blockParams, $inputParams, ContentBase $content_obj)
    {
        if( $blockName && isset($inputParams[$blockName]) ) {
            return $inputParams[$blockName];
        }
        return ''; // should never happen
    }

    public function ValidateContentBlockFieldValue($blockName, $value, $blockparams, ContentBase $content_obj)
    {
        if( !$blockName || !$value ) {
            return lang('informationmissing');
        }
        //TODO additional relevant checks e.g. URL is valid
        return '';
    }

    public function RenderContentBlockField($blockName, $value, $blockparams, ContentBase $content_obj)
    {
        if( $blockName ) {
            //TODO is there any merit in a constant-format relative url, applied here?
            // or some specific format applied via extra arg(s) from upstream?
            return (string)$value;
        }
        return '';
    }

    public function GetFileList($path = '')
    {
        return filemanager_utils::get_file_list($path);
    }

    public function get_profile_or_default($profile_name, $dir = '', $uid = 0)
    {
        $profile_name = trim((string)$profile_name);
        $profile = ( $profile_name ) ? $this->_dao->loadByName( $profile_name ) : null;
        if( !$profile ) $profile = $this->get_default_profile( $dir, $uid );
        return $profile;
    }

    public function get_default_profile($dir = '', $uid = 0)
    {
        /* $dir is absolute */
        $profile = $this->_dao->loadDefault();
        if( $profile ) return $profile;

        $profile = new Profile();
        return $profile;
    }

    public function get_browser_url()
    {
        return $this->create_url('m1_', 'filepicker');
    }

    //TODO support optional tailored useprefix variable for template & backend js, without clobbering $profile
    public function get_html($name, $value, Profile $profile, $required = false)
    {
        $_instance = 'i'.uniqid();
        if( $value === '-1' ) $value = '';

        // store the profile as a 'useonce' and add its signature to the params on the url
        $sig = TemporaryProfileStorage::set( $profile );
        $smarty = cmsms()->GetSmarty(); // $this->_GetTemplateObject();
        $tpl_ob = $smarty->CreateTemplate($this->GetTemplateResource('contentblock.tpl'), null, null, $smarty);
        $tpl_ob->assign('mod', $this);
        $tpl_ob->assign('sig', $sig);
        $tpl_ob->assign('blockName', $name);
        $tpl_ob->assign('value', $value);
        $tpl_ob->assign('instance', $_instance);
        $tpl_ob->assign('profile', $profile);
        $tpl_ob->assign('required', $required);
        switch( $profile->type ) {
        case FileType::TYPE_IMAGE:
            $tpl_ob->assign('title', $this->Lang('select_an_image'));
            break;
        case FileType::TYPE_AUDIO:
            $tpl_ob->assign('title', $this->Lang('select_an_audio_file'));
            break;
        case FileType::TYPE_VIDEO:
            $tpl_ob->assign('title', $this->Lang('select_a_video_file'));
            break;
        case FileType::TYPE_MEDIA:
            $tpl_ob->assign('title', $this->Lang('select_a_media_file'));
            break;
        case FileType::TYPE_XML:
            $tpl_ob->assign('title', $this->Lang('select_an_xml_file'));
            break;
        case FileType::TYPE_DOCUMENT:
            $tpl_ob->assign('title', $this->Lang('select_a_document'));
            break;
        case FileType::TYPE_ARCHIVE:
            $tpl_ob->assign('title', $this->Lang('select_an_archive_file'));
            break;
        case FileType::TYPE_ANY:
        default:
            $tpl_ob->assign('title', $this->Lang('select_a_file'));
            break;
        }
        return $tpl_ob->fetch();
    }

    // INTERNAL UTILITY FUNCTION
    public function is_image($fullpath)
    {
        $fullpath = trim((string)$fullpath);
        if( !$fullpath ) return FALSE;

        return $this->_typehelper->is_image($fullpath);
    }

    // INTERNAL UTILITY FUNCTION
    public function is_acceptable_filename(Profile $profile, $filename)
    {
        $filename = trim((string)$filename);
        $filename = basename($filename);  // in case it's a path
        if( !$filename ) { return FALSE; }
        if( strncasecmp($filename, 'index.htm', 9) == 0 ) { return FALSE; }
        if( endswith($filename, '.') ) { return FALSE; }
        if( !$profile->show_hidden && (startswith($filename, '.') || startswith($filename, '_')) ) { return FALSE; }// TODO valid test for windows hidden files
        if( $profile->match_prefix && !startswith($filename, $profile->match_prefix) ) { return FALSE; }
        if( $profile->exclude_prefix && startswith($filename, $profile->exclude_prefix) ) { return FALSE; }

        switch( $profile->type ) {
        case FileType::TYPE_IMAGE:
            return $this->_typehelper->is_image($filename);

        case FileType::TYPE_AUDIO:
            return $this->_typehelper->is_audio($filename);

        case FileType::TYPE_VIDEO:
            return $this->_typehelper->is_video($filename);

        case FileType::TYPE_MEDIA:
            return $this->_typehelper->is_media($filename);

        case FileType::TYPE_XML:
            return $this->_typehelper->is_xml($filename);

        case FileType::TYPE_DOCUMENT:
            return $this->_typehelper->is_document($filename);

        case FileType::TYPE_ARCHIVE:
            return $this->_typehelper->is_archive($filename);

        default:
            if( $this->_typehelper->is_executable($filename) ) {
                $config = cms_config::get_instance();
                if( !$config['developer_mode'] ) {
                    return FALSE;
                }
            }
            break;
        }
        // passed
        return TRUE;
    }
} // end of class
