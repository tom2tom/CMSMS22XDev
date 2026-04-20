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
  $l = strlen($needle);
  if( $l > 0 ) {
    return (strncmp($haystack, $needle, $l) == 0);
  }
  return false;
}

function endswith($haystack,$needle)
{
  $l = strlen($needle);
  if( $l > 0 ) {
    return (substr_compare($haystack, $needle, -$l, $l) == 0);
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

}
?>
