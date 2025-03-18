<?php

namespace __appbase\tests;

use function __appbase\lang;

class matchall_test extends test_base
{
    private $_children;

    public function add_child(test_base $obj)
    {
        if( !isset($this->_children) ) $this->_children = array();
        $this->_children[] = $obj;
    }

    public function execute()
    {
        $out = parent::TEST_PASS;
        if( !empty($this->_children) ) {
            $n = count($this->_children);
            for( $i = 0; $i < $n; $i++ ) {
                $res = $this->_children[$i]->run();
                if( $res == parent::TEST_FAIL ) {
                    // test failed
                    if( $this->required ) return $res;
                    // not required, we can continue
                    $out = parent::TEST_WARN;
                }
            }
        }
        return $out;
    }

    public function msg()
    {
        if( !empty($this->_children) ) {
            $n = count($this->_children);
            switch( $this->status ) {
            case parent::TEST_FAIL:
                for( $i = 0; $i < $n; $i++ ) {
                    $obj = $this->_children[$i];
                    if( $obj->status == parent::TEST_FAIL ) {
                        if( $obj->fail_msg ) return $obj->fail_msg;
                        if( $obj->fail_key ) return lang($obj->fail_key);
                    }
                }
                break;

            case parent::TEST_WARN:
                for( $i = 0; $i < $n; $i++ ) {
                    $obj = $this->_children[$i];
                    if( $obj->status == parent::TEST_FAIL ) {
                        if( $obj->warn_msg ) return $obj->warn_msg;
                        if( $obj->warn_key ) return lang($obj->warn_key);
                    }
                }
            }
        }
        return parent::msg();
    }
} // end of class

?>
