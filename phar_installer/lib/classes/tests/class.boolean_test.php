<?php

namespace __appbase\tests;

use __appbase\utils;

class boolean_test extends test_base
{
  public function execute()
  {
    $val = utils::to_bool($this->value);
    if( $val ) return parent::TEST_PASS;
    return parent::TEST_FAIL;
  }
}
