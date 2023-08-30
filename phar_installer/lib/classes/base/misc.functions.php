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

function joinpath()
{
  $segs = func_get_args();
  if( is_array($segs[0]) ) {
    $segs = $segs[0];
 }
 $path = implode(DIRECTORY_SEPARATOR, $segs);
 return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
}

}
?>
