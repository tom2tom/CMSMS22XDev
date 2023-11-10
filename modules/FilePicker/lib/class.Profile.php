<?php
namespace FilePicker;

use cms_config;
use cms_utils;
use CMSMS\FilePickerProfile;
use Exception;
use LogicException;
use function endswith;
use function startswith;

class ProfileException extends Exception {}

class Profile extends FilePickerProfile
{
    private $_data = [ 'id'=>0, 'name'=>'', 'create_date'=>0, 'modified_date'=>0, 'file_extensions'=>'', 'prefix'=>'' ];

    protected function setValue( $key, $val )
    {
        switch( $key ) {
          case 'name':
          case 'prefix':
          case 'file_extensions':
            $this->_data[$key] = trim((string)$val);
            break;
          case 'create_date':
          case 'modified_date':
            $this->_data[$key] = (int)$val;
            break;
          default:
            parent::setValue( $key, $val );
            break;
        }
    }

    public function __construct(array $in = [])
    {
        if( !$in ) return;

        parent::__construct($in);
        foreach( $in as $key => $value ) {
            switch( $key ) {
            case 'id':
                $this->_data[$key] = (int)$value;
                break;
            default:
                $this->setValue( $key, $value );
                break;
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function __get($key)
    {
        switch( $key ) {
        case 'id':
        case 'create_date':
        case 'modified_date':
            return (int) $this->_data[$key];

        case 'name':
        case 'file_extensions':
        case 'prefix':
            return trim((string)$this->_data[$key]);

        case 'relative_top':
        case 'reltop':
            // parent top is checked for relative or absolute
            // if relative, return relative to uploads path
            $val = parent::__get('top');
            if( $val && preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~',$val) ) { //general test for absolute path
                $config = cms_config::get_instance();
                $uploads_path = $config['uploads_path'];
                if( startswith( $val, $uploads_path ) ) {
                    $val = substr($val,strlen($uploads_path));
                    $val = ltrim($val, ' \\/');
                }
            }
            return $val; //empty or non-absolute

        case 'top':
            // parent top is checked for relative or absolute
            // if relative, prepend uploads path
            $val = parent::__get('top');
            if( !$val || !preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~',$val) ) { //general test for non-absolute path
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

    public function validate()
    {
        if( !$this->name ) throw new ProfileException( 'err_profile_name' );
        if( $this->reltop && !is_dir($this->top) ) throw new ProfileException('err_profile_topdir');
    }

    public function withNewId( $new_id = 0 )
    {
        if( $new_id ) {
            $new_id = (int) $new_id;
            if( $new_id < 1 ) throw new LogicException('Invalid id passed to '.__METHOD__);
        }
        $obj = clone $this;
        $obj->_data['id'] = $new_id;
        $obj->_data['create_date'] = $obj->_data['modified_date'] = time();
        return $obj;
    }

    public function overrideWith( array $params )
    {
        $obj = clone( $this );
        foreach( $params as $key => $val ) {
            switch( $key ) {
            case 'id':
                // cannot set a new id this way
                break;

            default:
                $obj->setValue($key,$val);
                break;
            }
        }
        return $obj;
    }

    public function markModified()
    {
        $obj = clone $this;
        $obj->_data['modified_date'] = time();
        return $obj;
    }

    public function getRawData()
    {
        $data = parent::getRawData();
        $data = array_merge($data,$this->_data);
        return $data;
    }

  /**
   * This can impose extra checks on top of FilePicker::is_acceptable_filename()
   * if this object's file_extensions property is not empty
   *
   * @param $fullpath
   *
   * @return bool
   */
    public function is_filename_acceptable( $fullpath )
    {
        $mod = cms_utils::get_module('FilePicker');
        if( !$mod->is_acceptable_filename($this, $fullpath) ) return FALSE;
        if( !$this->file_extensions ) return TRUE; // nothing more to check

        $lcf = strtolower($fullpath);
        $list = explode(',',$this->file_extensions);
        foreach( $list as $one ) {
            if( endswith($lcf,$one) ) return TRUE;
        }
        return FALSE;
    }
} // end of class
