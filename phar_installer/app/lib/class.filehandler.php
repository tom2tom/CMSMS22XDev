<?php

namespace cms_autoinstaller;

use Exception;
use PharFileInfo;
use function __appbase\get_app;
use function __appbase\lang;

abstract class filehandler
{
  private $_destdir;
  private $_output_fn;
  private $_languages;

  protected function get_config()
  {
    return get_app()->get_config();
  }

  public function set_destdir($destdir)
  {
    if( !is_dir($destdir) ) throw new Exception(lang('error_dirnotvalid',$destdir));
    if( !is_writable($destdir) ) throw new Exception(lang('error_dirnotvalid',$destdir));
    $this->_destdir = $destdir;
  }

  public function get_destdir()
  {
    if( !$this->_destdir ) throw new Exception(lang('error_nodestdir'));
    return $this->_destdir;
  }

  public function set_languages($lang)
  {
    if( !is_array($lang) ) return;
    $this->_languages = $lang;
  }

  public function get_languages()
  {
    return $this->_languages;
  }

  public function set_output_fn($fn)
  {
    if( !is_callable($fn) ) throw new Exception(lang('error_internal','fh100'));
    $this->_output_fn = $fn;
  }

  public function output_string($txt)
  {
    if( $this->_output_fn ) call_user_func($this->_output_fn,$txt);
  }

  protected function is_excluded($filespec)
  {
    $filespec = trim($filespec);
    if( !$filespec ) throw new Exception(lang('error_internal','fh110'));
    $config = $this->get_config();
    if( !isset($config['install_excludes']) ) return FALSE;

    $excludes = explode('||',$config['install_excludes']);
    foreach( $excludes as $excl ) {
      if( preg_match($excl,$filespec) ) return TRUE;
    }
    return FALSE;
  }

  protected function dir_exists($filespec)
  {
    $filespec = trim($filespec);
    if( !$filespec ) throw new Exception(lang('error_invalidparam','filespec'));

    $tmp = $this->get_destdir().'/'.dirname($filespec);
    return is_dir($tmp);
  }

  protected function create_directory($filespec)
  {
    $filespec = trim($filespec);
    if( !$filespec ) throw new Exception(lang('error_invalidparam','filespec'));

    $dn = dirname($filespec);
    $tmp = $this->get_destdir()."/$dn";
    return @mkdir($tmp,0777,TRUE);
  }

  protected function is_imagefile($filespec)
  {
      // this method uses (ugly) extensions because we cannot rely on finfo_open being available.
      $image_exts = ['bmp','jpg','jpeg','gif','png','svg','webp','ico'];
      $ext = strtolower(substr(strrchr($filespec, '.'), 1));
      return in_array($ext,$image_exts);
  }

  //returns array 0, 1 or 2 members
  //[1] if present && non-falsy is locale identifier
  //[2] if present is 'related' (non-CMSMS e.g. TinyMCE) locale identifier
  protected function is_langfile($filespec)
  {
    $filespec = trim($filespec);
    if( !$filespec ) throw new Exception(lang('error_invalidparam','filespec'));
    $pchk = substr_compare($filespec,'.php',-4,4) === 0;
    if( !($pchk || substr_compare($filespec,'.js',-3,3) === 0) ) {
      return [];
    }
    $bn = basename($filespec);
    if( $pchk ) {
      //CMSMS-used locale identifiers have all been like ab_CD
      //valid identifiers are not confined to that pattern
      if( preg_match('/^[a-zA-Z]{2}_[a-zA-Z]{2}\.nls\.php$/',$bn) ) {
        return [substr($bn,0,-8)];
      }
      if( preg_match('/^[a-zA-Z]{2}_[a-zA-Z]{2}\.php$/',$bn) ) {
        //(lazily) confirm it's a CMSMS translation
        if( preg_match('~[\\/]lang[\\/]en_US.php$~',$filespec) ) {
          return ['en_US'];
        }
        if( preg_match('~[\\/]lib[\\/]lang[\\/]\w+[\\/]en_US.php$~',$filespec) ) {
          return ['en_US'];
        }
        if( preg_match('~[\\/]lang[\\/]ext[\\/]'.$bn.'$~',$filespec) ) {
          return [substr($bn,0,-4)];
        }
        if( preg_match('~[\\/]lib[\\/]lang[\\/]\w+[\\/]ext[\\/]'.$bn.'$~',$filespec) ) {
          return [substr($bn,0,-4)];
        }
      }
    }
//TODO process PHPMailer translations named like .../phpmailer.lang-pt.php
    $nls = get_app()->get_nls(); // all possbible locales
    if( !is_array($nls) ) return []; // problem

    $bn = substr($bn,0,strpos($bn,'.'));
    if( !preg_match('/^[a-zA-Z]{2}(_[a-zA-Z]{2})?$/',$bn)) {
      return [];
    }
    foreach( $nls['alias'] as $alias => $code ) {
      if( strcasecmp($bn,$alias) == 0 ) { //caseless since 2.2.19
        return [FALSE,$bn];
      }
    }
    foreach( $nls['htmlarea'] as $code => $short ) {
      if( strcasecmp($bn,$short) == 0 ) { //caseless since 2.2.19
        return [FALSE,$bn];
      }
    }
    return [$bn];
  }

  //$res optional, if non-null is the array value returned by is_langfile()
  protected function is_accepted_lang($filespec,$res=null)
  {
    if( $res === null) { $res = $this->is_langfile($filespec); }
    if( !$res ) {
      return FALSE;
    }
    $langs = $this->get_languages(); // wanted locales
    if( !$langs || !is_array($langs) ) {
      return TRUE;
    }
    if( $res[0] ) {
      return in_array($res[0],$langs);
    }
    return in_array($res[1],$langs);
  }

  abstract public function handle_file($filespec,$srcspec,PharFileInfo $fi);
}

?>
