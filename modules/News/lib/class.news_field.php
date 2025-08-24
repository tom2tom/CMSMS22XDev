<?php
#CMSMS News module class: news_field
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

final class news_field
{
  private $_data = array();
  private $_displayvalue = null; // aka unset

  public function _get_data($key)
  {
    if( isset($this->_data[$key]) ) return $this->_data[$key];
    return null;
  }

  #[\ReturnTypeWillChange]
  public function __get($key)
  {
    $fielddefs = news_ops::get_fielddefs(FALSE); //ensure populated
//  $mod = cms_utils::get_module('News');

    switch( $key ) {
    case 'alias':
      return munge_string_to_url($this->name); // i.e. not tolower = inconsistent

    case 'id':
    case 'name':
    case 'type':
    case 'create_date':
    case 'modified_date':
    case 'item_order':
    case 'public':
      if( isset($this->_data[$key]) ) return $this->_data[$key];
      break;

    case 'value':
      if( isset($this->_data[$key]) ) {
        if( $this->type == 'linkedfile' ) {
          //{file_url} tag expects uploads-dir-relative filepath
          //so relativize to there if needed
          if( !isset($config) ) { $config = cms_config::get_instance(); }
          $td = basename($config['uploads_path']);
          $p = strpos($this->_data[$key],$td);
          if( $p !== false ) {
            return substr($this->_data[$key], $p + strlen($td));
          }
        }
        return $this->_data[$key];
      }
      break;

    case 'max_length':
      $arr = $this->extra; // unserialized value
      if( $arr && isset($arr['max_length']) ) {
        return (int)$arr['max_length'];
      }
      break;

    case 'extra':
      if( isset($this->_data['extra']) ) {
        if( !is_array($this->_data['extra']) ) {
          // unserialize on-demand
          $this->_data['extra'] = unserialize($this->_data['extra'],array('allowed_classes'=>FALSE));
          if( $this->_data['extra'] === FALSE ) {
            unset($this->_data['extra']);
            break;
          }
        }
        return $this->_data['extra'];
      }
      break;

    case 'displayvalue':
      if( isset($this->_displayvalue) ) {
        return $this->_displayvalue;
      }
      if( isset($this->_data['value']) ) {
        $value = $this->_data['value'];
        switch( $this->type ) {
        case 'dropdown':
          // dropdowns typically have a displayvalue different from their actual/key value
          $choices = $this->options;
          if( is_array($choices) && isset($choices[$value]) ) {
            $this->_displayvalue = $choices[$value];
          }
          else {
            allow_admin_lang();
            if( $value == -1 ) { // nothing selected
              $this->_displayvalue = lang('notspecified'); // or 'unknown' or $mod->Lang()
            }
            else {
              $this->_displayvalue = lang('unknown'); // or $mod->Lang()
            }
          }
        break;
        case 'checkbox':
          allow_admin_lang();
          $this->_displayvalue = ($value) ? lang('yes') : lang('no'); // or $mod->Lang()
          break;
        case 'linkedfile':
          $this->_displayvalue = basename($value); // for displaying alt or title attribute, no encoding
          break;
        case 'file':
          $this->_displayvalue = rawurlencode($value); // for displaying 'current' info
          break;
        default:
          $this->_displayvalue = $value;
        }
      }
      else {
        allow_admin_lang();
        $this->_displayvalue = lang('unknown'); // or $mod->Lang()
      }
      return $this->_displayvalue;
      // no break here
    case 'fielddef_id':
      return $this->_data['id'];
      // no break here
    default:
      $arr = $this->extra; // unserialized value
      if( $arr && isset($arr[$key]) ) return $arr[$key];
    }
    return null; // no value for unsupported or unavailable property
  }

  #[\ReturnTypeWillChange]
  public function __isset($key)
  {
    switch( $key ) {
    case 'alias':
    case 'id':
    case 'name':
    case 'type':
    case 'create_date':
    case 'modified_date':
    case 'item_order':
    case 'public':
      return TRUE;

    case 'value':
    case 'extra':
      return isset($this->_data[$key]);

    default:
      if( isset($this->_data['extra']) ) {
        if( !is_array($this->_data['extra']) ) {
          $this->_data['extra'] = unserialize($this->_data['extra'],array('allowed_classes'=>FALSE));
          if( $this->_data['extra'] === FALSE ) {
            unset($this->_data['extra']);
            return FALSE;
          }
        }
        return isset($this->_data['extra'][$key]);
      }
      return FALSE;
    }
  }

  #[\ReturnTypeWillChange]
  public function __set($key,$value)
  {
    switch( $key ) {
    case 'id':
    case 'name':
    case 'type':
    case 'item_order':
    case 'public':
    case 'value':
    case 'extra':
      $this->_data[$key] = $value;
      break;

    case 'max_length':
      if( isset($this->_data['extra']) ) {
        if( !is_array($this->_data['extra']) ) {
          $this->_data['extra'] = unserialize($this->_data['extra'],array('allowed_classes'=>FALSE));
          if( $this->_data['extra'] === FALSE ) {
            unset($this->_data['extra']);
            throw new Exception('Failed attempt to set data into field object: '.$key);
          }
        }
        $this->_data['extra']['max_length'] = (int)$value;
      }
      break;

    case 'alias':
      throw new Exception('Attempt to set invalid data into field object: '.$key);

    case 'create_date':
    case 'modified_date':
      break;

    default:
      if( isset($this->_data['extra']) ) {
        if( !is_array($this->_data['extra']) ) {
          $this->_data['extra'] = unserialize($this->_data['extra'],array('allowed_classes'=>FALSE));
          if( $this->_data['extra'] === FALSE ) {
            unset($this->_data['extra']);
            throw new Exception('Failed attempt to set data into field object: '.$key);
          }
        }
        $this->_data['extra'][$key] = $value;
      }
    }
  }

  private function _validate()
  {
    if( $this->name == '' ) throw new CmsException('Invalid field definition name');
    if( $this->type == 'dropdown' && count($this->options) == 0 ) throw new CmsException('No options for dropdown field');
    if( $this->id > 0 && $this->item_order < 1 ) throw new CmsException('Invalid item order');
  }

  private function _insert()
  {
    $db = cmsms()->GetDb();
    if( $this->item_order < 1 ) {
      $query = 'SELECT MAX(item_order) FROM '.CMS_DB_PREFIX.'module_news_fielddefs';
      $num = (int)$db->GetOne($query);
      $this->item_order = $num+1;
    }
    $pub = (!empty($this->public)) ? 1 : 0;
    $extra = ($this->extra) ? serialize($this->extra) : null;
    $query = 'INSERT INTO '.CMS_DB_PREFIX."module_news_fielddefs
(name,type,create_date,modified_date,item_order,public,extra)
VALUES (?,?,NOW(),NOW(),?,?,?)";
    $dbr = $db->Execute($query,array($this->name,$this->type,$this->item_order,$pub,$extra));
    $this->_data['id'] = $db->Insert_ID();
    $this->create_date = $this->modified_date = $db->DBTimeStamp(time());
  }

  private function _update()
  {
    $db = cmsms()->GetDb();
    $pub = (!empty($this->public)) ? 1 : 0;
    $extra = ($this->extra) ? serialize($this->extra) : null;
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET name = ?, type = ?, modified_date = NOW(), item_order = ?, public = ?, extra = ? WHERE id = ?';
    $dbr = $db->Execute($query,array($this->name,$this->type,$this->item_order,
                                     $pub,$extra,$this->id));
    $this->modified_date = $db->DBTimeStamp(time());
  }

  public function save()
  {
    $this->_validate();
    if( $this->_data['id'] ) {
      $this->_insert();
    }
    else {
      $this->_update();
    }
  }

  public static function load_by_id($id)
  {
    $id = (int)$id;
    if( $id < 1 ) return null; // no object

    $db = cmsms()->GetDb();
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE id = ?';
    $row = $db->GetRow($query,array($id));
    foreach( ['name','type'] as $fld ) {
        if( $row[$fld] === null ) $row[$fld] = '';
    }
    if( $row['extra'] ) { $row['extra'] = unserialize($row['extra'],array('allowed_classes'=>FALSE)); }
    if( !$row['extra'] ) { $row['extra'] = ''; }
    $obj = new news_field();
    $obj->_data = $row; // TODO any sanitisation hereabouts?
    return $obj;
  }

  public static function load_by_name($name)
  {
    $name = trim($name);
    if( !$name ) return null; // no object

    $db = cmsms()->GetDb();
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE name = ?';
    $row = $db->GetRow($query,array($name));
    if( $row['extra'] ) $row['extra'] = unserialize($row['extra'],array('allowed_classes'=>FALSE));
    $obj = new news_field();
    $obj->_data = $row; // TODO any sanitisation hereabouts?
    return $obj;
  }
} // end of class

?>
