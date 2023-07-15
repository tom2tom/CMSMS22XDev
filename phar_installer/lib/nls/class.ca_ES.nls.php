<?php

namespace __appbase;

class ca_ES_nls extends nls
{
  public function __construct()
  {
    $this->_fullname = 'Catalan';
    $this->_display = 'Catal&agrave;';
    $this->_isocode = 'ca';
    $this->_locale = 'ca_ES';
    $this->_encoding = 'UTF-8';
    $this->_aliases = 'ca_ES.utf8,ca_ES.utf8@valencia,ca_ES.utf-8,ca_ES.UTF-8,ca_ES@euro,ca_ES@valencia,catalan,Catalan_Spain';
  }  

  public function foo() { return 1; }
} // end of class

?>
