<?php
#CMS Made Simple class CmsNls
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$

/**
 * A class to provide data and methods for encapsulating a single language
 *
 * @author Robert Campbell
 * @since 1.11
 * @package CMS
 * @license GPL
 */
class CmsNls
{

  /**
   * @ignore
   */
  protected $_isocode;

  /**
   * @ignore
   */
  protected $_locale;

  /**
   * @ignore
   */
  protected $_fullname;

  /**
   * @ignore
   */
  protected $_encoding;

  /**
   * @ignore
   */
  protected $_aliases;

  /**
   * @ignore
   */
  protected $_display;

  /**
   * @ignore
   */
  protected $_key;

  /**
   * @ignore
   */
  protected $_direction;

  /**
   * @ignore
   */
  protected $_htmlarea;

  /**
   * Test if this NLS object matches the passed in string
   *
   * Matches are achieved by checking name, isocode, fullname, and then aliases
   *
   * @param string $str The test string
   * @return bool
   */
  public function matches($str)
  {
    if( $str == $this->name() ) return TRUE;
    if( $str == $this->isocode() ) return TRUE;
    if( $str == $this->fullname() ) return TRUE;
    $aliases = $this->aliases();
    if( !is_array($aliases) ) $aliases = explode(',',$aliases);
    if( is_array($aliases) && count($aliases) ) {
      foreach( $aliases as $alias ) {
        if( !strcasecmp($alias,$str) ) return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Return the name of this CmsNls object
   * @return string
   */
  public function name()
  {
    if( !empty($this->_key) ) return $this->_key;
    return '';
  }

  /**
   * Return this isocode of this CmsNls object
   * @return string
   */
  public function isocode()
  {
    if( !empty($this->_isocode) ) return $this->_isocode;
    if( !empty($this->_fullname) ) return substr($this->_fullname,0,2);
    return '';
  }

  /**
   * Return the display string for this CmsNls object
   * @return string
   */
  public function display()
  {
    if( !empty($this->_display) ) return $this->_display;
    return '';
  }

  /**
   * Return the locale string for this CmsNls object
   * @return string
   */
  public function locale()
  {
    if( !empty($this->_locale) ) return $this->_locale;
    return '';
  }

  /**
   * Return the encoding for this CmsNls object (or UTF-8)
   * @return string
   */
  public function encoding()
  {
    if( !empty($this->_encoding) ) return $this->_encoding;
    return 'UTF-8';
  }

  /**
   * Return the full name of this CmsNls object
   * @return string
   */
  public function fullname()
  {
    if( !empty($this->_fullname) ) return $this->_fullname;
    return '';
  }

  /**
   * Return the aliases associated with this CmsNls object
   * @return mixed array of aliases, or empty
   */
  public function aliases()
  {
    if( !empty($this->_aliases) ) {
      if( is_array($this->_aliases) ) return $this->_aliases;
      return explode(',',$this->_aliases);
    }
    return [];
  }

  /**
   * Return the key associated with this CmsNls object
   * @return string
   */
  public function key()
  {
    if( !empty($this->_key) )return $this->_key;
    return '';
  }

  /**
   * Return the direction of this CmsNls object (ltr or rtl)
   * @return string
   */
  public function direction()
  {
    if( !empty($this->_direction) ) return $this->_direction;
    return 'ltr';
  }

  /**
   * Return the first two characters of the isocode for this CmsNls Object
   * This is used typically for WYSIWYG text editors.
   *
   * @return string
   */
  public function htmlarea()
  {
    if( !empty($this->_htmlarea) ) return $this->_htmlarea;
    return substr($this->_fullname,0,2);
  }

  /**
   * Create a CmsNls object from a compatible array.
   *
   * @internal
   * @ignore
   * @param array $data
   */
  public static function from_array($data)
  {
    $obj = new CmsNls();

    // name and key
    if( isset($data['englishlang']) ) {
      foreach( $data['englishlang'] as $k => $v ) {
        $obj->_fullname = $v;
        $obj->_key = $k;
        break;
      }
    }

    // get the display value
    if( isset($data['language'][$obj->_key]) ) $obj->_display = $data['language'][$obj->_key];

    // get the isocode?
    if( isset($data['isocode'][$obj->_key]) ) {
      $obj->_isocode = $data['isocode'][$obj->_key];
    }
    else {
      $t = explode('_',$obj->_key);
      if( is_array($t) && count($t) ) $obj->_isocode = $t[0];
    }

    // get the locale
    if( isset($data['locale'][$obj->_key]) ) $obj->_locale = $data['locale'][$obj->_key];

    // get the encoding
    if( isset($data['encoding'][$obj->_key]) ) $obj->_encoding = $data['encoding'][$obj->_key];

    if( isset($data['htmlarea'][$obj->_key]) ) $obj->_htmlarea = $data['htmlarea'][$obj->_key];

    // get the direction
    if( isset($data['direction'][$obj->_key]) ) $obj->_direction = $data['direction'][$obj->_key];

    // get aliases
    if( isset($data['alias']) ) $obj->_aliases= array_keys($data['alias']);

    if( $obj->_key == '' ) {
      debug_display($data);
      debug_display($obj); die();
    }
    return $obj;
  }
} // end of class

#
# EOF
#
?>
