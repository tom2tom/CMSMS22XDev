<?php

class microtiny_profile implements ArrayAccess
{
  private static $_keys = array(
  'allowcssoverride',
  'allowimages',
  'allowresize',
  'allowtables',
  'dfltstylesheet',
  'formats', //is this used?
  'label',
  'menubar',
  'name',
  'showstatusbar',
  'styler',
  'system',
  'theme',
  );
  private static $_module;
  private $_data = array();

  public function __construct($data = [])
  {
      if( $data && is_array($data) ) {
          foreach( $data as $key => $value ) {
              $this[$key] = $value;
          }
      }
  }

  #[\ReturnTypeWillChange]
  public function offsetGet($key)
  {
    switch( $key ) {
    case 'menubar':
    case 'allowimages':
    case 'allowtables':
    case 'showstatusbar':
    case 'allowresize':
    case 'allowcssoverride':
    case 'system':
      if( isset($this->_data[$key]) ) return (bool)$this->_data[$key];
      return false;

    case 'formats':
      if( isset($this->_data[$key]) ) return $this->_data[$key];
      return [];

    case 'name':
    case 'dfltstylesheet':
    case 'styler':
    case 'theme':
      if( isset($this->_data[$key]) ) return trim($this->_data[$key]);
      return '';

    case 'label':
      if( isset($this->_data[$key]) ) return trim($this->_data[$key]);
      if( isset($this->_data['name']) ) return trim($this->_data['name']);
      return '';

    default:
      throw new CmsInvalidDataException('invalid key '.$key.' for '.__CLASS__.' object');
    }
  }

  #[\ReturnTypeWillChange]
  public function offsetSet($key,$value)
  {
    switch( $key ) {
    case 'menubar':
    case 'allowtables':
    case 'allowimages':
    case 'showstatusbar':
    case 'allowresize':
    case 'allowcssoverride':
    case 'system':
      $this->_data[$key] = cms_to_bool($value);
      break;

    case 'formats':
      if( is_array($value) ) $this->_data[$key] = $value;
      break;

    case 'name':
    case 'label':
    case 'dfltstylesheet':
    case 'styler':
    case 'theme':
      $value = trim($value);
      if( $value ) $this->_data[$key] = $value;
      break;

    default:
      throw new CmsInvalidDataException('invalid key '.$key.' for '.__CLASS__.' object');
    }
  }

  #[\ReturnTypeWillChange]
  public function offsetExists($key)
  {
    switch( $key ) {
    case 'allowcssoverride':
    case 'allowimages':
    case 'allowresize':
    case 'allowtables':
    case 'dfltstylesheet':
    case 'formats':
    case 'label':
    case 'menubar':
    case 'name':
    case 'showstatusbar':
    case 'styler':
    case 'system':
    case 'theme':
      return isset($this->_data[$key]);

    default:
      throw new CmsInvalidDataException('invalid key '.$key.' for '.__CLASS__.' object');
    }
  }

  #[\ReturnTypeWillChange]
  public function offsetUnset($key)
  {
    switch( $key ) {
    case 'allowcssoverride':
    case 'allowimages':
    case 'allowresize':
    case 'allowtables':
    case 'dfltstylesheet':
    case 'formats':
    case 'label':
    case 'menubar':
    case 'showstatusbar':
    case 'styler':
    case 'theme':
      unset($this->_data[$key]);
      break;

    case 'name':
    case 'system':
      throw new CmsLogicException('Cannot unset '.$key.' for '.__CLASS__);

    default:
      throw new CmsInvalidDataException('invalid key '.$key.' for '.__CLASS__.' object');
    }
  }

  public function save()
  {
    if( !isset($this->_data['name']) || $this->_data['name'] == '' ) {
      throw new CmsInvalidDataException('Invalid microtiny profile name');
    }

    $data = serialize($this->_data);
    self::_get_module()->SetPreference('profile_'.$this->_data['name'],$data);
  }

  public function delete()
  {
      if( $this['name'] == '' ) return;
      self::_get_module()->RemovePreference('profile_'.$this['name']);
      unset($this->_data['name']);
  }

  /**
   * @param $data
   *
   * @return \microtiny_profile
   * @throws \CmsInvalidDataException
   * @todo: make sure this method is used or needed at all JoMorg
   */
  private static function _load_from_data($data)
  {
    if( !is_array($data) || !count($data) ) throw new CmsInvalidDataException('Invalid data passed to '.__CLASS__.'::'.__METHOD__);

    $obj = new microtiny_profile();
    foreach( $data as $key => $value ) {
      if( !in_array($key,self::$_keys) ) throw new CmsInvalidDataException('Invalid key '.$key.' for data in .'.__CLASS__);
      $obj->_data[$key] = trim($value);
    }
    return $obj;
  }

  public static function set_module(MicroTiny $module)
  {
    self::$_module = $module;
  }

  private static function _get_module()
  {
    if( !is_object(self::$_module) ) self::$_module = cms_utils::get_module('MicroTiny');
    return self::$_module;
  }

  public static function load($name)
  {
    if( $name == '' ) return null; // no object
    $data = self::_get_module()->GetPreference('profile_'.$name);
    if( !$data ) throw new CmsInvalidDataException('Unknown microtiny profile '.$name);

    $obj = new self();
    $obj->_data = unserialize($data);
    return $obj;
  }

  public static function list_all()
  {
    $prefix = 'profile_';
    return self::_get_module()->ListPreferencesByPrefix($prefix);
  }

} // end of class

?>
