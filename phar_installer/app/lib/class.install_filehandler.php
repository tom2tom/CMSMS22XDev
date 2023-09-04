<?php

namespace cms_autoinstaller;

use Exception;
use PharFileInfo;
use function __appbase\lang;

class install_filehandler extends filehandler
{
  public function handle_file($filespec,$srcspec,PharFileInfo $fi)
  {
    if( $this->is_excluded($filespec) ) return;
    $res = $this->is_langfile($filespec);
    if( $res ) {
      if( !$this->is_accepted_lang($filespec,$res) ) {
        //cleanup (non-CMSMS at least) dest file corresponding to $srcspec
        //!$res[0] && $res[1] if a non-CMSMS alias etc was matched e.g. js for tinymce
        $destname = $this->get_destdir().$filespec;
        if( file_exists($destname) ) {
          if( is_writable($destname) ) {
            unlink($destname);
          }
          else {
            throw new Exception(lang('error_delete',$filespec));
          }
        }
        return;
      }
    }

    if( !$this->dir_exists($filespec) ) {
      $this->create_directory($filespec);
    }
    $destname = $this->get_destdir().$filespec;
    if( file_exists($destname) && !is_writable($destname) ) {
      throw new Exception(lang('error_overwrite',$filespec));
    }

    $cksum = md5_file($srcspec);
    @copy($srcspec,$destname);
    $cksum2 = md5_file($destname);
    if( $cksum != $cksum2 ) {
      throw new Exception(lang('error_checksum',$filespec));
    }
    $this->output_string(lang('file_installed',$filespec));
  }
}

?>
