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

use FilePicker\TemporaryProfileStorage;
//use CMSMS\FilePickerProfile as Profile;
use CMSMS\FileType;

require_once(__DIR__.'/lib/class.ProfileDAO.php');

final class FilePicker extends \CMSModule implements CMSMS\FilePickerInterface
{
    protected $_dao;
    protected $_typehelper;

    public function __construct()
    {
        parent::__construct();
        $this->_dao = new \FilePicker\ProfileDAO( $this );
        $this->_typehelper = new \CMSMS\FileTypeHelper( \cms_config::get_instance() );
    }

    private function _encodefilename($filename)
    {
        return str_replace('==', '', base64_encode($filename));
    }

    private function _decodefilename($encodedfilename)
    {
        return base64_decode($encodedfilename . '==');
    }

    function VisibleToAdminUser()
    {
        return $this->CheckPermission('Modify Site Preferences');
    }

    private function _GetTemplateObject()
    {
        $ret = $this->GetActionTemplateObject();
        if( is_object($ret) ) return $ret;
        return cmsms()->GetSmarty();
    }

    /**
     * end of private methods
     */

    function GetFriendlyName() { return $this->Lang('friendlyname');  }
    function GetVersion() { return '1.0.5'; }
    function GetHelp() { return $this->Lang('help'); }
    function IsPluginModule() { return FALSE; }
    function HasAdmin() { return TRUE; }
    function GetAdminSection() { return 'extensions'; }

    function HasCapability( $capability, $params = array() )
    {
        switch( $capability ) {
        case 'contentblocks':
        case 'filepicker':
        case 'upload':
            return TRUE;
        default:
            return FALSE;
        }
    }

    function GetContentBlockFieldInput($blockName, $value, $params, $adding, ContentBase $content_obj)
    {
        if( empty($blockName) ) return FALSE;
        $uid = get_userid(FALSE);
        //$adding = (bool)( $adding || ($content_obj->Id() < 1) ); // hack for the core. Have to ask why though (JM)

        $profile_name = get_parameter_value($params,'profile');
        $profile = $this->get_profile_or_default($profile_name);

        // todo: optionally allow further overriding the profile
        $out = $this->get_html($blockName, $value, $profile);
        return $out;
    }

    public function GetFileList($path = '')
    {
        return filemanager_utils::get_file_list($path);
    }

    public function get_profile_or_default( $profile_name, $dir = '', $uid = 0 )
    {
        $profile_name = trim($profile_name);
        $profile = null; // no object
        if( $profile_name ) $profile = $this->_dao->loadByName( $profile_name );
        if( !$profile ) $profile = $this->get_default_profile( $dir, $uid );
        return $profile;
    }

    public function get_default_profile( $dir = '', $uid = 0 )
    {
        /* $dir is absolute */
        $profile = $this->_dao->loadDefault();
        if( $profile ) return $profile;

        $profile = new \CMSMS\FilePickerProfile();
        return $profile;
    }

    public function get_browser_url()
    {
        return $this->create_url('m1_','filepicker');
    }

    public function get_html( $name, $value, \CMSMS\FilePickerProfile $profile, $required = false )
    {
        $_instance = 'i'.uniqid();
        if( $value === '-1' ) $value = '';

        // store the profile as a 'useonce' and add it's signature to the params on the url
        $sig = TemporaryProfileStorage::set( $profile );
        $smarty = cmsms()->GetSmarty(); // $this->_GetTemplateObject();
        $tpl_ob = $smarty->CreateTemplate($this->GetTemplateResource('contentblock.tpl'),null,null,$smarty);
        $tpl_ob->assign('mod',$this);
        $tpl_ob->assign('sig',$sig);
        $tpl_ob->assign('blockName',$name);
        $tpl_ob->assign('value',$value);
        $tpl_ob->assign('instance',$_instance);
        $tpl_ob->assign('profile',$profile);
        $tpl_ob->assign('required',$required);
        switch( $profile->type ) {
        case FileType::TYPE_IMAGE:
            $tpl_ob->assign('title',$this->Lang('select_an_image'));
            break;
        case FileType::TYPE_AUDIO:
            $tpl_ob->assign('title',$this->Lang('select_an_audio_file'));
            break;
        case FileType::TYPE_VIDEO:
            $tpl_ob->assign('title',$this->Lang('select_a_video_file'));
            break;
        case FileType::TYPE_MEDIA:
            $tpl_ob->assign('title',$this->Lang('select_a_media_file'));
            break;
        case FileType::TYPE_XML:
            $tpl_ob->assign('title',$this->Lang('select_an_xml_file'));
            break;
        case FileType::TYPE_DOCUMENT:
            $tpl_ob->assign('title',$this->Lang('select_a_document'));
            break;
        case FileType::TYPE_ARCHIVE:
            $tpl_ob->assign('title',$this->Lang('select_an_archive_file'));
            break;
        case FileType::TYPE_ANY:
        default:
            $tpl_ob->assign('title',$this->Lang('select_a_file'));
            break;
        }
        return $tpl_ob->fetch();
    }

    // INTERNAL UTILITY FUNCTION
    public function is_image( $filespec )
    {
        $filespec = trim($filespec);
        if( !$filespec ) return FALSE;

        return $this->_typehelper->is_image( $filespec );
    }


    // INTERNAL UTILITY FUNCTION
    public function is_acceptable_filename( \CMSMS\FilePickerProfile $profile, $filename )
    {
        $filename = trim($filename);
        $filename = basename($filename);  // in case it's a path
        if( !$filename ) return FALSE;
        if( endswith( $filename, '.' ) ) return FALSE;

        if( !$profile->show_hidden && (startswith($filename,'.') || startswith($filename,'_') || $filename == 'index.html') ) return FALSE;
        if( $profile->match_prefix && !startswith( $filename, $profile->match_prefix) ) return FALSE;
        if( $profile->exclude_prefix && startswith( $filename, $profile->exclude_prefix) ) return FALSE;

        switch( $profile->type ) {
        case \CMSMS\FileType::TYPE_IMAGE:
            return ( $this->_typehelper->is_image( $filename ) );

        case \CMSMS\FileType::TYPE_AUDIO:
            return ( $this->_typehelper->is_audio( $filename ) );

        case \CMSMS\FileType::TYPE_VIDEO:
            return ( $this->_typehelper->is_video( $filename ) );

        case \CMSMS\FileType::TYPE_MEDIA:
            return ( $this->_typehelper->is_media( $filename) );

        case \CMSMS\FileType::TYPE_XML:
            return ( $this->_typehelper->is_xml( $filename) );

        case \CMSMS\FileType::TYPE_DOCUMENT:
            return ( $this->_typehelper->is_document( $filename) );

        case \CMSMS\FileType::TYPE_ARCHIVE:
            return ( $this->_typehelper->is_archive( $filename ) );

        default:
            if( $this->_typehelper->is_executable( $filename ) ) {
                $config = \cms_config::get_instance();
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
