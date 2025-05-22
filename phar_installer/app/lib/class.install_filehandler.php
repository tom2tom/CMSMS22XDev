<?php

namespace cms_autoinstaller;

use Exception;
use PharFileInfo;
use function __appbase\lang;

class install_filehandler extends filehandler
{
  //the intended topmost dirpath for themes data
  //same as or different from the installer-sources place (...assets/themes)
  private $_themesdir;

  public function set_themesdir($topdir)
  {
    if( !is_dir($topdir) ) {
      @mkdir($topdir,0777,true);
    }
    if( !is_dir($topdir) || !is_writable($topdir) ) throw new Exception(lang('error_dirnotvalid',$topdir));
    touch($topdir.'/index.html');
    $this->_themesdir = $topdir;
  }

  public function handle_file($filespec,$srcspec,PharFileInfo $fi)
  {
    if( $this->is_excluded($filespec) ) return;
    $res = $this->is_langfile($filespec);
    if( $res ) {
      if( !$this->is_accepted_lang($filespec,$res) ) {
        //cleanup dest file corresponding to $srcspec
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
    elseif( $this->is_themefile($filespec) ) {
      if( empty($this->_themesdir) ) throw new Exception(lang('error_nothemedir'));
      $tp = $this->_themesdir;
      $fp = $this->_destdir.'/assets/themes'; //default top-folder among installer files
      $sp = substr($filespec,14); //ignore '/assets/themes' prefix
      if( $tp != $fp ) {
        $X = $fp.$sp;
        if( is_file($X) ) {
          unlink($X);
        }
      }
      // if same file in some former place, also delete that
      $X = $this->_destdir.'/uploads'.$sp;
      if( is_file($X) ) {
        unlink($X);
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

  private function is_themefile($filespec)
  {
    $filespec = ltrim($filespec);
    if( !$filespec ) throw new Exception(lang('error_invalidparam','filespec'));
    return strncmp($filespec,'/assets/themes/',15) == 0;
  }
}

?>
