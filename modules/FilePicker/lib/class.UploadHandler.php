<?php
namespace FilePicker;

use CMSMS\HookManager;
use FilePicker; //module class in global namespace
use FilePicker\Profile;
use cms_utils;
use filemanager_utils;

class UploadHandler extends jquery_upload_handler
{
    private $_path;
    private $_mod;
    private $_profile;

    public function __construct( FilePicker $mod, Profile $profile, $path )
    {
        $this->_mod = $mod;
        $this->_profile = $profile;
        if( !endswith( $path, '/' ) ) $path .= '/';
        $this->_path = $path;

        $opts = [ 'upload_dir'=>$path ];
        parent::__construct( $opts );
    }

    public function is_file_type_acceptable( $fileobject )
    {
        $complete_path = $this->_path.$fileobject->name;
        return $this->_mod->is_acceptable_filename( $this->_profile, $complete_path );
    }

    public function process_error( $fileobject, $error )
    {
        $fileobject = parent::process_error( $fileobject, $error );
        if( $fileobject->error ) {
            $fileobject->errormsg = $this->_mod->Lang('error_upload_'.$fileobject->error);
        }
        return $fileobject;
    }

    public function after_uploaded_file( $fileobject )
    {
        if( !is_object($fileobject) || !$fileobject->name ) return;
        $complete_path = $this->_path.$fileobject->name;

        $parms['file'] = $complete_path;
        $parms = HookManager::do_hook( 'FileManager::OnFileUploaded', $parms );
        if( is_array($parms) && isset($parms['file']) ) $file = $parms['file']; // file name could have changed.

        if( !is_file($complete_path) ) return;
        $str = basename($complete_path).' uploaded to '.filemanager_utils::get_full_cwd();
        if( !$this->_mod->is_image( $complete_path ) ) {
            audit('',$this->_mod->GetName(),$str);
            return;
        }

        if( !$this->_profile->show_thumbs ) return;

        $mod = cms_utils::get_module('FileManager');
        $thumb = filemanager_utils::create_thumbnail($complete_path, NULL, TRUE);

        if( $thumb ) $str .= ' and a thumbnail was generated';
        audit('',$this->_mod->GetName(),$str);
/*
        $info = getimagesize($complete_path);
        if( !$info || !isset($info['mime']) ) return;

        // gotta create a thumbnail
        $width = (int) \cms_siteprefs::get('thumbnail_width',96);
        $height = (int) \cms_siteprefs::get('thumbnail_height',96);
        if( $width < 1 || $height < 1 ) return;

        $complete_thumb = $this->_path.'thumb_'.$fileobject->name;
        $i_src = imagecreatefromstring(file_get_contents($complete_path));
        $i_dest = imagecreatetruecolor($width,$height);
        imagealphablending($i_dest,FALSE);
        $color = imageColorAllocateAlpha($i_src, 255, 255, 255, 127);
        imagecolortransparent($i_dest,$color);
        imagefill($i_dest,0,0,$color);
        imagesavealpha($i_dest,TRUE);
        imagecopyresampled($i_dest,$i_src,0,0,0,0,$width,$height,imagesx($i_src),imagesy($i_src));

        $res = false;
        switch( $info['mime'] ) {
        case 'image/gif':
            $res = imagegif($i_dest,$complete_thumb);
            break;
        case 'image/png':
            $res = imagepng($i_dest,$complete_thumb,9);
            break;
        case 'image/jpeg':
            $res = imagejpeg($i_dest,$complete_thumb,100);
            break;
        }
*/
    }
}
