<?php
/*
CMSMS FilePicker module class: Profile
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file FilePicker.module.php applies to this file.
*/

namespace FilePicker;

use cms_config;
use cms_utils;
use CMSMS\FilePickerProfile;
use CmsDataException;
use Exception;
use function cms_join_path;
use function endswith;
use function is_absolute_path;
use function startswith;

class ProfileException extends Exception {}

// no merit in this class being distinct from its parent, unless to
// support multiple picker-modules or multiple roles for the parent

class Profile extends FilePickerProfile
{
    /**
     * Constructor
     *
     * @param array $params Optional property-names and their values
     */
    public function __construct(array $params = [])
    {
        $this->_data += [
         'id'=>0,
         'name'=>'',
         'created'=>0,
         'modified'=>0,
         'file_extensions'=>''
        ];

        if( $params ) {
            $this->_controls['setall'] = TRUE;
            foreach( $params as $key => $value ) {
                switch( $key ) {
                case 'id':
                case 'created':
                    $this->_data[$key] = (int)$value;
                    break;
                default:
                    $this->__set($key, $value);
                    break;
                }
            }
            $this->_controls['setall'] = FALSE;
        }
        if( empty($this->_data['created']) ) {
            $this->_data['created'] = $this->_data['modified'] = time();
        }
        if( empty($this->_data['modified']) || $this->_data['modified'] < $this->_data['created'] ) {
            $this->_data['modified'] = $this->_data['created'];
        }
    }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __get($key)
    {
        switch( $key ) {
        case 'id':
        case 'created':
        case 'modified':
            return (int) $this->_data[$key];

        case 'create_date':
            return (int) $this->_data['created'];
        case 'modified_date':
            return (int) $this->_data['modified'];

        case 'name':
        case 'file_extensions':
            return trim((string)$this->_data[$key]);

        case 'relative_top':
        case 'reltop':
            // check parent 'top' for relative or absolute
            // if relative, return relative to uploads path
            $val = parent::__get('top');
            if( $val && is_absolute_path($val) ) {
                $config = cms_config::get_instance();
                $uploads_path = $config['uploads_path'];
                if( startswith($val, $uploads_path) ) {
                    $val = substr($val, strlen($uploads_path));
                    $val = ltrim($val, ' \\/');
                }
            }
            return $val; //empty or non-absolute

        case 'top':
            // check parent 'top' for relative or absolute
            // if relative, prepend uploads path
            $val = parent::__get('top');
            if( !$val || !is_absolute_path($val) ) {
                $config = cms_config::get_instance();
                if( $val ) {
                    $val = $config['uploads_path'].DIRECTORY_SEPARATOR.$val;
                }
                else {
                    $val = $config['uploads_path'];
                }
            }
            return $val; // absolute

        default:
            return parent::__get($key);
        }
    }

    /**
     * Set a property of this object
     *
     * @param string $key The property name
     * @param mixed $val The property value
     */
    #[\ReturnTypeWillChange]
    public function __set($key, $val)
    {
        switch( $key ) {
          case 'name':
          case 'file_extensions':
            $this->_data[$key] = trim((string)$val);
            break;
          case 'id': //TODO special-case value-check?
          case 'created': // ditto
          case 'modified':
            $this->_data[$key] = (int)$val;
            break;
          case 'modified_date': // deprecated
            $this->_data['modified'] = (int)$val;
            break;
          case 'create_date': // deprecated
            $this->_data['created'] = (int)$val;
            break;
          default:
            parent::__set($key, $val);
            break;
        }
    }

    /**
     * Set a property of this object
     * This is a deprecated alias of __set()
     *
     * @param string $key The property name
     * @param mixed $val The property value
     */
    protected function setValue($key, $val)
    {
        $this->__set($key, $val);
    }

    /**
     * @throws ProfileException upon error
     */
    public function validate()
    {
        if( !$this->_data['name'] ) { throw new ProfileException('err_profile_name'); }
        // like munge_string_to_url without space etc, with utf8 numbers
        $tmp = preg_replace(['/[^\pL_\p{Nd}]/u', '/_{2,}/'], ['', ''], trim($this->name));
        if( $tmp != $this->_data['name'] ) { throw new ProfileException('err_profile_name'); }
        if( $this->reltop ) {
            $config = cms_config::get_instance();
            $tmp = cms_join_path($config['uploads_path'],$this->_data['top']);
            if( !is_dir($tmp) ) { throw new ProfileException('err_profile_topdir'); }
        }
        if( $this->_data['file_extensions'] && preg_match('/[ A-Z]/', $this->_data['file_extensions']) ) {
            throw new ProfileException('err_profile_extensions');
        }
    }

    /**
     * Change the numeric id of this object
     * Also sets its created and modified properties to now
     *
     * @param int $new_id Optional numeric id. Default 0.
     *  If supplied, it must be >= 0.
     * @return self for API back-compatibility (or chaining)
     * @throws CmsDataException if $new_id < 0
     */
    public function withNewId($new_id = 0)
    {
        if( $new_id ) {
            $new_id = (int) $new_id;
            if( $new_id < 1 ) throw new CmsDataException('Invalid id passed to '.__METHOD__);
        }
        $this->_data['id'] = $new_id;
        $this->_data['created'] = $this->_data['modified'] = time();
        return $this;
    }

    /**
     * Change some or all properties of this object in accord with the
     * supplied $params
     * Changing the 'id' property to a non-0 value is not allowed.
     * Changing the 'created' property is not allowed if 'id' is absent or non-0.
     *
     * @param array $params Optional property-names and their values
     * @return self for API back-compatibility (or chaining)
     */
    public function overrideWith(array $params = [])
    {
        if( $params ) {
            $useparms = array_intersect_key($params, $this->_data);
            if( array_key_exists('id', $useparms) ) {
                $id = (int)$useparms['id'];
                if( $id > 0 ) {
                    unset($useparms['id']); // no id-change allowed this way
                    unset($useparms['created']);
                    unset($useparms['create_date']); // deprecated
                }
                else {
                    $useparms['id'] = 0;
                    $useparms['created'] = $useparms['modified'] = time();
                }
            }
            if( array_key_exists('created', $useparms) ) {
                if( !isset($useparms['id']) || $useparms['id'] > 0 ) {
                    unset($useparms['created']);
                    unset($useparms['create_date']); // deprecated
                }
            }
            foreach( $useparms as $key => $val ) {
                $this->__set($key,$val);
            }
        }
        return $this;
    }

    /**
     * Change the 'modified' property of this object to the current timestamp
     * @deprecated since instead use $obj->modified = time()
     * @return self for API back-compatibility (or chaining)
     */
    public function markModified()
    {
        $this->_data['modified'] = time();
        return $this;
    }

    /**
     * Check the supplied filepath using FilePicker::is_acceptable_filename()
     * and then, if this object's file_extensions property is not empty,
     * check the extension of the filepath basename against that property
     *
     * @param string $fullpath
     * @return bool
     */
    public function is_filename_acceptable($fullpath)
    {
        $fullpath = (string)$fullpath;
        if( $fullpath === '' ) return FALSE;
        $mod = cms_utils::get_module('FilePicker');
        if( !$mod->is_acceptable_filename($this, $fullpath) ) return FALSE;
        if( empty($this->_data['file_extensions']) ) return TRUE; // nothing more to check

        $lcf = strtolower($fullpath);
        $list = explode(',', $this->_data['file_extensions']);
        foreach( $list as $one ) {
            if( endswith($lcf, $one) ) return TRUE;
        }
        return FALSE;
    }
} // class
