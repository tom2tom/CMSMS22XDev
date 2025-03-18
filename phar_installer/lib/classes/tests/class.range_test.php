<?php

namespace __appbase\tests;

class range_test extends test_base
{
  public function execute()
  {
      if( $this->minimum )
      {
          $min = $this->returnBytes($this->minimum);
          $val = $this->returnBytes($this->value);
          if( $val < $min ) return parent::TEST_FAIL;
      }
      if( $this->recommended )
      {
          $rec = $this->returnBytes($this->recommended);
          $val = $this->returnBytes($this->value);
          if( $val < $rec ) return parent::TEST_WARN;
      }
      if( $this->maximum )
      {
          $max = $this->returnBytes($this->maximum);
          $val = $this->returnBytes($this->value);
          if( $val > $max ) return parent::TEST_FAIL;
      }
      return parent::TEST_PASS;
  }
}

?>
