<?php

namespace __appbase\tests;

use cms_autoinstaller\utils;

class version_range_test extends test_base
{
  public function execute()
  {
    // make sure we have all of the information.
    // do the test
    // set the result.
    if( $this->minimum ) {
      if( utils::cms_version_compare($this->value,$this->minimum) < 0 ) return parent::TEST_FAIL;
    }
    if( $this->maximum ) {
      if( utils::cms_version_compare($this->value,$this->maximum) > 0 ) return parent::TEST_FAIL;
    }
    if( $this->recommended ) {
      if( utils::cms_version_compare($this->value,$this->recommended) < 0 ) return parent::TEST_WARN;
    }
    return parent::TEST_PASS;
  }
}

?>
