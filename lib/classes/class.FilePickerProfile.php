<?php

namespace CMSMS;

use CmsInvalidDataException;
use CMSMS\FileType;

//This class might be better conceived as and more useful as something
//not picker-specific e.g. 'FileSystemProfile' or 'ControlsSet'
/**
 * A class that defines a suite of properties to dictate behavior and
 * available functionality.
 *
 * The constructor and overrideWith methods of this class accept an
 * associative array of parameters (see the properties below) to
 * establish or alter a profile object.
 *
 * ```php
 * $obj = new \CMSMS\FilePickerProfile([
 *  'type'=>FileType::TYPE_IMAGE,
 *  'exclude_prefix'=>'foo'
 * ]);
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell
 * @since  2.2
 *
 * Default properties:
 * @property int $can_delete The user can remove files.
 * @property int $can_mkdir The user can create new directories.
 * @property int $can_upload The user can upload files (of the specified type)
 * @property string $exclude_prefix Exclude any files/items that have the specified prefix. fnmatch() wildcards allowed.
 * @property string $match_prefix Include only files/items that have the specified prefix (and also match $type). fnmatch() wildcards allowed.
 * @property bool $show_hidden Whether to show hidden files.
 * @property bool $show_thumbs Whether to show thumbnail images instead of normal icons for images.
 * @property bool $sort Whether to sort files before showing.
 * @property string $top The top directory (relative to the CMSMS uploads directory).
 * @property FileType $type The CMSMS FileType representing displayable files.
 * Other property(ies) may be added during construction but not after.
 */
class FilePickerProfile
{
    const FLAG_NONE = 0;
    const FLAG_YES = 1;
    const FLAG_BYGROUP = 2; // group-authority to avoid backend-js permission-check ? if so, which group(s) for each relevant property ?

    /**
     * @ignore
     */
    protected $_data = [
     'can_delete' => self::FLAG_YES, // override to NONE when user doesn't have 'Modify Files' permission
     'can_mkdir' => self::FLAG_YES, // ditto
     'can_upload' => self::FLAG_YES, // ditto
     'exclude_prefix' => '',
     'match_prefix' => '',
     'show_hidden' => FALSE,
     'show_thumbs' => TRUE,
     'sort' => TRUE,
     'top' => '',
     'type' => FileType::TYPE_ANY];

    /**
     * @ignore
     */
    protected $_controls = [
     'setall' => FALSE,
     'userid' => 0 // override if/when group-specific properties are deployed
    ];

    /**
     * Constructor
     *
     * @param array $params Optional property-names and their values
     */
    public function __construct(array $params = [])
    {
        if( $params ) {
            $this->_controls['setall'] = TRUE;
            foreach( $params as $key => $val ) {
                $this->__set($key, $val);
            }
            $this->_controls['setall'] = FALSE;
        }
    }

    /**
     * @ignore
     * @param string $key The property name
     * @param mixed $val The property value
     */
    #[\ReturnTypeWillChange]
    public function __set($key, $val)
    {
        switch( $key ) {
        case 'top':
        case 'match_prefix':
        case 'exclude_prefix':
            $this->_data[$key] = trim((string)$val);
            break;

        case 'type':
            $val = trim((string)$val);
            switch( $val ) {
            case FileType::TYPE_IMAGE:
            case FileType::TYPE_AUDIO:
            case FileType::TYPE_VIDEO:
            case FileType::TYPE_MEDIA:
            case FileType::TYPE_XML:
            case FileType::TYPE_DOCUMENT:
            case FileType::TYPE_ARCHIVE:
            case FileType::TYPE_ANY:
                $this->_data[$key] = $val;
                break;
            case '':
            case 'file':
                $this->_data[$key] = FileType::TYPE_ANY;
                break;
            default:
                throw new CmsInvalidDataException("$val is an invalid value for type in ".__CLASS__);
            }
            break;

        case 'can_mkdir':
        case 'can_delete':
        case 'can_upload':
            switch( $val ) {
            case self::FLAG_NONE: // or false
            case self::FLAG_YES: // or true
            case self::FLAG_BYGROUP:
                $this->_data[$key] = $val;
                break;
            default:
                throw new CmsInvalidDataException("$val is an invalid value for $key in ".__CLASS__);
            }
            break;

        case 'show_thumbs':
        case 'show_hidden':
        case 'sort':
            $this->_data[$key] = (bool) $val; // OR cms_to_bool($val)
            break;

        default:
            if( $this->_controls['setall'] ) { //anything else allowed only during construction
                $this->_data[$key] = $val;
            }
            else {
                throw new CmsInvalidDataException("$key is not a post-creation-settable property in ".__CLASS__);
            }
        }
    }

    /**
     * @ignore
     * @param string $key The key to get
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function __get($key)
    {
        switch( $key ) {
        case 'top':
        case 'type':
        case 'match_prefix':
        case 'exclude_prefix':
            return trim((string)$this->_data[$key]);

        case 'can_mkdir':
        case 'can_upload':
        case 'can_delete':
            return (int)$this->_data[$key];

        case 'show_thumbs':
        case 'show_hidden':
        case 'sort':
            return (bool)$this->_data[$key];

        default:
            return array_key_exists($key, $this->_data) ? $this->_data[$key] : null;
        }
    }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __serialize()
    {
         return $this->_data;
    }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __unserialize(array $params)
    {
        $this->__construct($params);
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
     * Change the specified properties of this object.
     *
     * @param array $params Optional property-names and their values
     * @return self for API back-compatibility (or chaining)
     */
    public function overrideWith(array $params = [])
    {
        foreach( $params as $key => $val ) {
            $this->__set($key, $val);
        }
        return $this;
    }

    /**
     * Get all the properties of this object.
     *
     * @internal
     * @return array
     */
    public function getRawData()
    {
        return $this->_data;
    }
} // class
