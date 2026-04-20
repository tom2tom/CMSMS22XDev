<?php

use __appbase\langtools;
use __appbase\wizard;

function ilang(...$args)
{
  return langtools::get_instance()->translate($args);
}

function step_object()
{
  static $obj = null;
  if( $obj === null) {
    $obj = wizard::get_instance()->get_step();
  }
  return $obj;
}

function verbose_msg($str)
{
  $obj = step_object();
  if( method_exists($obj,'verbose') ) { $obj->verbose($str); }
}

function status_msg($str)
{
  $obj = step_object();
  if( method_exists($obj,'message') ) { $obj->message($str); }
}

function error_msg($str)
{
  $obj = step_object();
  if( method_exists($obj,'error') ) { $obj->error($str); }
}

?>
