<?php

namespace __appbase;

class da_DK_nls extends nls
{
  public function __construct()
  {
    $this->_fullname = 'Danish';
    $this->_display = 'Dansk';
    $this->_isocode = 'da';
    $this->_locale = 'da_DK';
    $this->_encoding = 'UTF-8';
    $this->_aliases = 'da_DK.utf8,da_DK.utf-8,da_DK.UTF-8,danish,Danish_Denmark.1252';
  }

  public function foo() { return 1; }
} // end of class

?>
