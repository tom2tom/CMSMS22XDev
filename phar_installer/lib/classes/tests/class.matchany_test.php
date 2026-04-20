<?php

namespace __appbase\tests;

class matchany_test extends test_base
{
  private $_children;

  public function add_child(test_base $obj)
  {
    if( !isset($this->_children) ) $this->_children = array();
    $this->_children[] = $obj;
  }

  public function execute()
  {
    if( !empty($this->_children) ) {
      $n = count($this->_children);
      for( $i = 0; $i < $n; $i++ ) {
        $res = $this->_children[$i]->execute();
        if( $res == parent::TEST_PASS ) {
          return $res;
        }
      }
    }
    return parent::TEST_FAIL;
  }
}

?>
