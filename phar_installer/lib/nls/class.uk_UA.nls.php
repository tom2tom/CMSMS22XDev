<?php

namespace __appbase;

class uk_UA_nls extends nls
{
  public function __construct()
  {
    $this->_fullname = 'Ukrainian';
    $this->_display = 'Українська';
    $this->_isocode = 'uk';
    $this->_locale = 'uk_UA';
    $this->_encoding = 'UTF-8';
    $this->_aliases = 'uk_UA.cp1251,uk_UA.CP1251,uk_UA.CP866,uk_UA.koi8r,uk_UA.koi8ru,uk_UA.koi8u,uk_UA.utf8,uk_UA.utf.8,uk_ua.UTF-8,uk_UA.iso88595,ukrainian,ukrainian_Ukraine.1251';
  }

  public function foo() { return 1; }
} // end of class

?>
