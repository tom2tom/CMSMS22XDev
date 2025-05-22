<?php

namespace {

use __appbase\langtools;

function tr(...$args)
{
  static $tools = null;
  if( $tools === null ) {
    $tools = langtools::get_instance();
  }
  return $tools->translate($args);
}

}

namespace __appbase {

function startswith($haystack,$needle)
{
  return (strncmp($haystack,$needle,strlen($needle)) == 0);
}

function endswith($haystack,$needle)
{
  $o = strlen($needle);
  if( $o > 0 ) {
    return substr_compare($haystack, $needle, -$o, $o) == 0;
  }
  return false;
}

function joinpath(...$segs)
{
  if( is_array($segs[0]) ) {
    $segs = $segs[0];
  }
  $path = implode(DIRECTORY_SEPARATOR, $segs);
  return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
}

function rrmdir($dir)
{
  if( is_dir($dir) ) {
    $items = scandir($dir);
    if( $items ) {
      foreach( $items as $name ) {
        if( !($name == '.' || $name == '..') ) {
          $fp = "$dir/$name";
          if( is_dir($fp) ) {
            rrmdir($fp);
          }
          else {
            //TODO deal with links to dirs?
            unlink($fp);
          }
        }
      }
    }
    if( $items !== false ) {
      rmdir($dir);
    }
    else {
      //TODO handle error
    }
  }
}

}
?>
