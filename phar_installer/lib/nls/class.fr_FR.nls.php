<?php

namespace __appbase;

class fr_FR_nls extends nls
{
  public function __construct()
  {
    $this->_fullname = 'French';
    $this->_display = 'Fran&#231;ais';
    $this->_isocode = 'fr';
    $this->_locale = 'fr_FR';
    $this->_encoding = 'UTF-8';
    $this->_aliases = 'french,fre,fr_BE,fr_CA,fr_LU,fr_CH,fr_FR.ISO8859-1';
  }

  public function foo() { return 1; }
} // end of class

?>
