<?php
# FileManager module class
# (c) 2006-08 Morten Poulsen <morten@poulsen.org>
# (c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

namespace FileManager;

use cms_config;
use cms_utils;
use CMSMS\FileTypeHelper;
use CMSMS\HookManager;
use filemanager_utils;
use jquery_upload_handler;
use function audit;

require_once __DIR__.DIRECTORY_SEPARATOR.'class.jquery_upload_handler.php';

class UploadHandler extends jquery_upload_handler
{
  private $helper;

  function __construct($options=array())
  {
    if( !is_array($options) ) $options = array();

    // remove image handling, we're gonna handle this another way
    $options['orient_image'] = false;  // turn off auto image rotation
    $options['image_versions'] = array();

    $options['upload_dir'] = filemanager_utils::get_full_cwd().DIRECTORY_SEPARATOR;
    $options['upload_url'] = filemanager_utils::get_cwd_url().'/';

    // set everything up.
    parent::__construct($options);
  }

  protected function is_file_acceptable($file)
  {
    $config = cms_config::get_instance();
    if( $config['developer_mode'] ) return TRUE;
    if( !isset($this->helper) ) {
      $this->helper = new FileTypeHelper($config);
    }
    return !$this->helper->is_executable($file);
  }

  protected function after_uploaded_file($fileobject)
  {
    // here we may do image handling, and other cruft.
    if( is_object($fileobject) && $fileobject->name != '' ) {

      $mod = cms_utils::get_module('FileManager');
      $file = filemanager_utils::join_path(filemanager_utils::get_full_cwd(),$fileobject->name);
      $parms['file'] = $file;
      $parms = HookManager::do_hook( 'FileManager::OnFileUploaded', $parms );
      if( is_array($parms) && isset($parms['file']) ) $file = $parms['file']; // file name could have changed.

      $thumb = FALSE;
      if( $mod->GetPreference('create_thumbnails') ) {
          $thumb = filemanager_utils::create_thumbnail($file, NULL, TRUE);
      }

      $str = basename($file).' uploaded to '.filemanager_utils::get_full_cwd();
      if( $thumb ) $str .= ' and a thumbnail was generated';
      audit('',$mod->GetName(),$str);
    }
  }
}

