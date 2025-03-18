<?php

namespace __appbase\tests;

class informational_test extends test_base
{
  public function __construct($name,$value,$message = '',$key = '')
  {
    parent::__construct($name,$value,$key);
    if( $message )
    {
      $this->msg_key = $message;
    }
  }

  /**
   * Execute the test
   *
   * @return string always '-1'
   */
  public function execute()
  {
    return '-1';
  }
} // end of class

?>
