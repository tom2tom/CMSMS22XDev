<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: CmsLayoutTemplateType
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# A class to manage template types.
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE

/**
 * This file contains classes and functions that define a template type.
 * @package CMS
 * @license GPL
 */

use CMSMS\HookManager;

/**
 * A class to manage template types
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell
 */
class CmsLayoutTemplateType
{
    /**
     * This constant indicates a core template type
     */
    const CORE = '__CORE__';

    /**
     * @ignore
     */
    const TABLENAME = 'layout_tpl_type';

    /**
     * @ignore
     */
    private $_dirty = FALSE;

    /**
     * @ignore
     * @internal should be private but _load_from_data expects public
     */
    public $_data = [
     'id' => 0,
     'description' => '',
     'dflt_contents' => '',
     'content_callback' => null, //no callable aka unset
     'help_callback' => null,
     'lang_callback' => null,
     'has_dflt' => 0,
     'created' => 0,
     'modified' => 0,
     'name' => '',
     'one_only' => 0,
     'originator' => '',
     'owner' => 0,
     'requires_contentblocks' => 0
    ];

    /**
     * @ignore
     */
    private static $_cache;

    /**
     * @ignore
     */
    private static $_name_cache;

    /**
     * @ignore
     */
    private $_assistant;

    // no cloning for this object
    private function __clone() {}

    /**
     * Get the template type id
     *
     * @return int type id or 0 if this object has no id.
     */
    public function get_id()
    {
        return (int)$this->_data['id'];
    }

    /**
     * Get the template originator (this is usually a module name)
     *
     * @param  bool $viewable Should the originator name be the viewable (friendly) string?
     * @return string maybe empty
     */
    public function get_originator($viewable = FALSE)
    {
        $out = (string)$this->_data['originator'];
        if( $viewable && $out == self::CORE ) $out = 'Core';
        return $out;
    }

    /**
     * Set the template originator
     *
     * @throws CmsInvalidDataException
     * @param string $str The originator, usually a module name.
     */
    public function set_originator($str)
    {
        $str = trim((string)$str);
        if( !$str ) throw new CmsInvalidDataException('Template-type originator cannot be empty');
        $this->_data['originator'] = $str;
        $this->_dirty = TRUE;
    }

    /**
     * Return the template type name
     *
     * @return string maybe empty
     */
    public function get_name()
    {
        return (string)$this->_data['name'];
    }

    /**
     * Set the template type name
     *
     * @throws CmsInvalidDataException
     * @param string $str The template type name.
     */
    public function set_name($str)
    {
        $str = trim((string)$str);
        if( !$str ) throw new CmsInvalidDataException('Template-type name cannot be empty');
        $this->_data['name'] = $str;
        $this->_dirty = TRUE;
    }

    /**
     * Get the flag indicating whether this template type can have a 'default'
     *
     * @return bool
     */
    public function get_dflt_flag()
    {
        return (bool)$this->_data['has_dflt'];
    }

    /**
     * Set the flag indicating whether this template type can have a 'default'
     *
     * @param mixed $flag Something recognised by cms_to_bool(). Default true
     */
    public function set_dflt_flag($flag = TRUE)
    {
        $flag = cms_to_bool($flag);
        $this->_data['has_dflt'] = ($flag) ? 1 : 0;
        $this->_dirty = TRUE;
    }

    /**
     * Get the default contents used when creating a new template of this type.
     *
     * @return string
     */
    public function get_dflt_contents()
    {
        return (string)$this->_data['dflt_contents'];
    }

    /**
     * Set the default content used when creating a new template of this type.
     *
     * @param string $str The default template contents.
     */
    public function set_dflt_contents($str)
    {
        //TODO deal with OS-incompatible line-breaks?
        //$str = (strncasecmp(PHP_OS, 'WIN', 3) != 0) ? strtr($str, ["\r\n"=>"\n", "\r"=>"\n"]) : str_replace(["\r", "\n", "\r\r\n"], ["\r\n", "\r\n", "\r\n"], $str);
        $this->_data['dflt_contents'] = $str;
        $this->_dirty = TRUE;
    }

    /**
     * Get the template type description.
     *
     * @return string
     */
    public function get_description()
    {
        return (string)$this->_data['description'];
    }


    /**
     * Set the description for this template.
     *
     * @param string $str The default template contents.
     */
    public function set_description($str)
    {
        $this->_data['description'] = $str;
        $this->_dirty = TRUE;
     }

    /**
     * Get the owner of this template type.
     *
     * @return int, 0 if no owner is set
     */
    public function get_owner()
    {
        return (int)$this->_data['owner'];
    }

    /**
     * Set the owner of this template type
     *
     * @throws CmsInvalidDataException
     * @param int $owner
     */
    public function set_owner($owner)
    {
        if( !is_numeric($owner) || (int)$owner == 0 ) throw new CmsInvalidDataException('value is invalid for owner in '.__METHOD__);
        $this->_data['owner'] = (int)$owner;
        $this->_dirty = TRUE;
    }

    /**
     * Get the timestamp representing when this object was first recorded.
     *
     * @return int Unix timestamp, or 0 if this object has not been saved.
     */
    public function get_create_date()
    {
        return (int)$this->_data['created'];
    }

    /**
     * Get timestamp representing when this object was last modified
     *
     * @return int Unix timestamp, or 0 if this object has not been saved.
     */
    public function get_modified_date()
    {
        return (int)$this->_data['modified'];
    }

    /**
     * Set a callback to be used to retrieve a translated version of the originator and name strings.
     *
     * This callback must be a static string representing a static function name, or an array
     * representing a class name and method name.  This callback (if set) will be used to translate
     * the originator string, and the name string to something suitable to users language.
     *
     * @param callable $data A static function name string, or an array of class name and member name.
     */
    public function set_lang_callback($data)
    {
        $this->_data['lang_callback'] = $data;
        $this->_dirty = TRUE;
    }

    /**
     * Return the callback used to translate the originator and name strings.
     *
     * @return mixed callable | null
     */
    public function get_lang_callback()
    {
        if( isset($this->_data['lang_callback']) ) return $this->_data['lang_callback'];
        return null;
    }

    /**
     * Set a callback to be used to display help for this template when editing.
     *
     * @param callable $callback A static function name, or an array of a class name and member name.
     */
    public function set_help_callback($callback)
    {
        $this->_data['help_callback'] = $callback;
        $this->_dirty = TRUE;
    }

    /**
     * Return the callback used to retrieve help for this template type.
     *
     * @return mixed callable | null
     */
    public function get_help_callback()
    {
        if( isset($this->_data['help_callback']) ) return $this->_data['help_callback'];
        return null;
    }

    /**
     * Set the flag indicating that a maximum of one template of this type is permitted.
     *
     * @param mixed $flag Something recognised by cms_to_bool() Default true.
     */
    public function set_oneonly_flag($flag = TRUE)
    {
        $flag = cms_to_bool($flag);
        $this->_data['one_only'] = ($flag) ? 1 : 0;
        $this->_dirty = TRUE;
    }

    /**
     * Get the flag indicating that a maximum of one template of this type is permitted.
     *
     * @return bool
     */
    public function get_oneonly_flag()
    {
        return (bool)$this->_data['one_only'];
    }

    /**
     * Set a callback to be used when restoring the 'default content' to system default values.
     *
     * Modules typically distribute sample templates.  This callback function is used when the
     * user clicks on a button to reset the selected template type to it's factory default values.
     *
     * @param callable $data A static function name string, or an array of class name and member name.
     */
    public function set_content_callback($data)
    {
        $this->_data['content_callback'] = $data;
        $this->_dirty = TRUE;
    }

    /**
     * Return the callback used to reset a template to its factory default values.
     *
     * @return mixed
     */
    public function get_content_callback()
    {
        if( isset($this->_data['content_callback']) ) return $this->_data['content_callback'];
        return null;
    }

    /**
     * Get the content block flag
     * The content block flag indicates that this template type requires content blocks
     *
     * @return bool
     */
    public function get_content_block_flag()
    {
        return (bool)$this->_data['requires_contentblocks'];
    }

    /**
     * Set the content block flag to indicate that this template type requires content blocks
     *
     * @param mixed $flag Something recognised by cms_to_bool(). Default true.
     */
    public function set_content_block_flag($flag = TRUE)
    {
        $flag = cms_to_bool($flag);
        $this->_data['requires_contentblocks'] = ($flag) ? 1 : 0;
    }

    /**
     * Validate the integrity of this type object.
     *
     * This method will check the contents of the object for validity, and ensure that
     * the originator/name combination are unique.
     *
     * This method throws an exception if an error is found in the integrity of the object.
     *
     * @throws CmsInvalidDataException
     * @param bool $is_insert Whether this is a new insert, or an update. Default true.
     */
    protected function validate($is_insert = TRUE)
    {
        if( !$this->get_originator() ) throw new CmsInvalidDataException('Invalid template-type originator');
        if( !$this->get_name() ) throw new CmsInvalidDataException('Invalid template-type name');
        if( !preg_match('/[a-zA-Z0-9_,. ]/',$this->get_name()) ) {
            throw new CmsInvalidDataException('Template-type name can contain only letters, numbers and/or underscores.');
        }

        if( !$is_insert ) {
            if( (int)$this->_data['id'] < 1 ) throw new CmsInvalidDataException('Template-type numeric id is not set');

            // check for item with the same name
            $db = CmsApp::get_instance()->GetDb();
            $query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE originator = ? AND name = ? AND id != ?';
            $dbr = $db->GetOne($query,array($this->get_originator(),$this->get_name(),$this->get_id()));
            if( $dbr ) throw new CmsInvalidDataException('Template type having the same name already exists.');
        }
        else {
            // check for item with the same name
            $db = CmsApp::get_instance()->GetDb();
            $query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE originator = ? AND name = ?';
            $dbr = $db->GetOne($query,array($this->get_originator(),$this->get_name()));
            if( $dbr ) throw new CmsInvalidDataException('Template type having the same name already exists.');
        }
    }

    /**
     * Insert this type object into the database.
     *
     * This method will ensure that the current object is valid, generate an id, and
     * insert the record into the database.  An exception will be thrown if errors occur.
     *
     * @throws CmsSQLErrorException
     */
    protected function _insert()
    {
        if( !$this->_dirty ) return;
        $this->validate();
        $db = CmsApp::get_instance()->GetDb();
        $now = time();
        $query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.
' (originator,name,has_dflt,one_only,dflt_contents,description,
lang_cb,help_content_cb,dflt_content_cb,requires_contentblocks,owner,created,modified)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $dbr = $db->Execute($query,array($this->get_originator(), $this->get_name(), $this->get_dflt_flag(), $this->get_oneonly_flag(),
                                         $this->get_dflt_contents(), $this->get_description(),
                                         serialize($this->get_lang_callback()),
                                         serialize($this->get_help_callback()),
                                         serialize($this->get_content_callback()), $this->get_content_block_flag() ? 1 : 0,
                                         $this->get_owner(), $now,$now));
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        $this->_data['id'] = $db->Insert_ID();
        CmsTemplateCache::clear_cache();
        audit($this->get_id(),'Template type', "Created: {$this->get_name()}");
        $this->_dirty = FALSE;
    }


    /**
     * Update the contents of the database to match this type object.
     *
     * This method will ensure that the current object is valid, generate an id, and
     * update the record in the database.  An exception will be thrown if errors occur.
     *
     * @throws CmsSQLErrorException
     */
    protected function _update()
    {
        if( !$this->_dirty ) return;
        $this->validate(FALSE);
        $db = CmsApp::get_instance()->GetDb();
        $now = time();

        $query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.
' SET originator = ?, name = ?, has_dflt = ?, one_only = ?, dflt_contents = ?, description = ?,
lang_cb = ?, help_content_cb = ?, dflt_content_cb = ?, requires_contentblocks = ?, owner = ?, modified = ?
WHERE id = ?';
        $db->Execute($query,array($this->get_originator(),$this->get_name(),$this->get_dflt_flag(),$this->get_oneonly_flag(),
                                  $this->get_dflt_contents(),$this->get_description(),
                                  serialize($this->get_lang_callback()),serialize($this->get_help_callback()),
                                  serialize($this->get_content_callback()),$this->get_content_block_flag() ? 1 : 0,
                                  $this->get_owner(), $now, $this->get_id()));
        if( $db->Affected_Rows() != 1 || $db->ErrorNo() != 0 ) throw new CmsSQLErrorException($db->ErrorMsg());
        CmsTemplateCache::clear_cache();
        $this->_dirty = FALSE;
        audit($this->get_id(),'Template type', "Updated: {$this->get_name()}");
    }

    /**
     * Save the current type object to the database.
     */
    public function save()
    {
        if( $this->get_id() == 0 ) {
            HookManager::do_hook('Core::AddTemplateTypePre', [ get_class($this) => $this ]);
            $this->_insert();
            HookManager::do_hook('Core::AddTemplateTypePost', [ get_class($this) => $this ]);
            return;
        }
        HookManager::do_hook('Core::EditTemplateTypePre', [ get_class($this) => $this ]);
        $this->_update();
        HookManager::do_hook('Core::EditTemplateTypePost', [ get_class($this) => $this ]);
    }

    /**
     * Get a list of templates having this template type.
     *
     * @see CmsLayoutTemplate::list_by_type
     * @return Array of CmsLayoutTemplate objects.  or null.
     */
    public function get_template_list()
    {
        return CmsLayoutTemplate::load_all_by_type($this);
    }

    /**
     * Delete the current object from the database (if it has been saved).
     *
     * @throws CmsInvalidDataException
     * @throws CmsSQLErrorException
     */
    public function delete()
    {
        if( !$this->get_id() ) return;

        HookManager::do_hook('Core::DeleteTemplateTypePre', [ get_class($this) => $this ]);
        $tmp = CmsLayoutTemplate::template_query(array('t:'.$this->get_id()));
        if( is_array($tmp) && count($tmp) ) throw new CmsInvalidDataException('Cannot delete a template type with existing templates');
        $db = CmsApp::get_instance()->GetDb();
        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
        $dbr = $db->Execute($query,array($this->_data['id']));
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        $this->_dirty = TRUE;
        CmsTemplateCache::clear_cache();
        audit($this->get_id(),'Template type', "Deleted: {$this->get_name()}");
        HookManager::do_hook('Core::DeleteTemplateTypePost', [ get_class($this) => $this ]);
        $this->_data['id'] = 0;
    }

    /**
     * Create a new template of this type
     *
     * This method will throw an exception if the template cannot be created.
     *
     * @param string $name The template name
     * @return CmsLayoutTemplate object, or null.
     */
    public function create_new_template($name = '')
    {
        $ob = new CmsLayoutTemplate();
        $ob->set_type($this);
        $ob->set_content($this->get_dflt_contents());
        if( $name ) $ob->set_name($ob);
        return $ob;
    }

    /**
     * Get the default template of this type
     *
     * This method will throw an exception if the template cannot be created.
     *
     * @see CmsLayoutTemplate::load_dflt_by_type()
     * @return CmsLayoutTemplate object, or null.
     */
    public function get_dflt_template()
    {
        return CmsLayoutTemplate::load_dflt_by_type($this);
    }

    /**
     * Get HTML text for help with respect to the variables available in this template type.
     */
    public function get_template_helptext()
    {
        $text = '';
        $cb = $this->get_help_callback();
        $originator = $this->get_originator();
        $name = $this->get_name();
        if( $cb && is_callable($cb) ) {
            $text = call_user_func($cb,$name);
            return $text;
        }

        // no callback specified, see if this originator is a loadable module.
        $name = $this->get_name();
        if( $originator == self::CORE ) {
            // it's a core page template, or generic
        } else {
            $module = \cms_utils::get_module($originator);
            if( $module ) {
                if( method_exists($module,'get_templatetype_help') ) {
                    $text = $module->get_templatetype_help($name);
                }
            }
        }
        return $text;
    }

    /**
     * Get a translated/pretty displayable name for this template type
     * including the originator.
     */
    public function get_langified_display_value()
    {
        $t = $this->get_lang_callback();
        $to = '';
        $tn = '';
        if( is_callable($t) ) {
            $to = call_user_func($t,$this->get_originator());
            $tn = call_user_func($t,$this->get_name());
        }
        if( !$to ) $to = $this->get_originator();
        if( $to == self::CORE ) $to = lang('core');
        if( !$tn ) $tn = $this->get_name();
        return $to.'::'.$tn;
    }

    /**
     * Reset the content of this template type back to factory default
     *
     * @throws CmsException
     * @throws CmsDataNotFoundException
     */
    public function reset_content_to_factory()
    {
        if( !$this->get_dflt_flag() ) {
            $name = (string)$this->_data['name'];
            if( !$name ) $name = '<anonymous>';
            throw new CmsException("Template type '$name' does not have default content");
        }
        $cb = $this->get_content_callback();
        if( !$cb || !is_callable($cb) ) {
            $name = (string)$this->_data['name'];
            if( !$name ) $name = '<anonymous>';
            throw new CmsDataNotFoundException("Template type '$name' has no callback to reset content");
        }
        $content = call_user_func($cb,$this);
        $this->set_dflt_contents($content);
    }

    /**
     * Given an array (typically read from the database) create a CmsLayoutTemplateType object
     *
     * @internal
     * @return CmsLayoutTemplateType
     */
    private static function _load_from_data($row)
    {
        if( !empty($row['lang_cb']) ) $row['lang_callback'] = unserialize($row['lang_cb']);
        if( !empty($row['help_content_cb']) ) $row['help_callback'] = unserialize($row['help_content_cb']);
        if( !empty($row['dflt_content_cb']) ) $row['content_callback'] = unserialize($row['dflt_content_cb']);
        unset($row['lang_cb'],$row['help_content_cb'],$row['dflt_content_cb']);

        foreach( [
         'id' => 0,
         'description' => '',
         'dflt_contents' => '',
         'content_callback' => null, //no callable aka unset
         'help_callback' => null,
         'lang_callback' => null,
         'has_dflt' => 0,
         'created' => 0,
         'modified' => 0,
         'name' => '',
         'one_only' => 0,
         'originator' => '',
         'owner' => 0,
         'requires_contentblocks' => 0
        ] as $fld => $val ) {
            if( !isset($row[$fld]) ) {
                $row[$fld] = $val;
            }
            elseif( $val === '' ) {
                $row[$fld] = (string)$row[$fld];
            }
            elseif( $val === 0 ) {
                $row[$fld] = (int)$row[$fld];
            }
        }

        $ob = new self();
        $ob->_data = $row;

        self::$_cache[$ob->get_id()] = $ob; // aka (int)$row['id']
        self::$_name_cache[$ob->get_originator().'::'.$ob->get_name()] = $ob->get_id();
        return $ob;
    }

    /**
     * Load a CmsLayoutTemplateType object from the database.
     *
     * This method throws an exception when the requested object cannot be found.
     *
     * @throws CmsDataNotFoundException
     * @param mixed $val An integer template type id, or a string in the form of Originator::Name
     * @return CmsLayoutTemplateType
     */
    public static function load($val)
    {
        $db = CmsApp::get_instance()->GetDb();
        $row = [];
        if( is_numeric($val) && (int)$val > 0 ) {
            $val = (int) $val;
            if( isset(self::$_cache[$val]) ) return self::$_cache[$val];

            $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
            $row = $db->GetRow($query,array($val));
        }
        elseif( strlen($val) > 0 ) {
            if( isset(self::$_name_cache[$val]) ) {
                $id = self::$_name_cache[$val];
                return self::$_cache[$id];
            }

            $tmp = explode('::',$val);
            if( count($tmp) == 2 ) {
                $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE originator = ? AND name = ?';
                if( $tmp[0] == 'Core' or $tmp[0] == 'core' ) $tmp[0] = self::CORE;
                $row = $db->GetRow($query,array(trim($tmp[0]),trim($tmp[1])));
            }
        }
        if( !is_array($row) || count($row) == 0 ) {
            throw new CmsDataNotFoundException('Could not find template type identified by '.$val);
        }
        return self::_load_from_data($row);
    }

    /**
     * Load all of the template types for a certain originator.
     *
     * This method will throw exceptions if an error is encountered.
     *
     * @throws CmsInvalidDataException
     * @param string $originator The originator name
     * @return array CmsLayoutTemplateType objects, empty if no match is found.
     */
    public static function load_all_by_originator($originator)
    {
        if( !$originator ) throw new CmsInvalidDataException('Template-type orignator is empty');

        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE originator = ?';
        if( isset(self::$_cache) && count(self::$_cache) ) $query .= ' AND id NOT IN ('.implode(',',array_keys(self::$_cache)).')';
        $query .= ' ORDER BY modified DESC';
        $list = $db->GetArray($query,array($originator));
        if( !is_array($list) || count($list) == 0 ) return [];

        foreach( $list as $row ) {
            self::_load_from_data($row);
        }

        $out = array();
        foreach( self::$_cache as $id => $one ) {
            if( $one->get_originator() == $originator ) $out[] = $one;
        }
        return $out;
    }

    /**
     * Load all template types
     *
     * @return array CmsLayoutTemplateType objects ordered by their modification time, or maybe empty
     */
    public static function get_all()
    {
        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME;
        if( self::$_cache && count(self::$_cache) ) $query .= ' WHERE id NOT IN ('.implode(',',array_keys(self::$_cache)).')';
        $query .= ' ORDER BY modified';
        $list = $db->GetArray($query);
        if( !is_array($list) || count($list) == 0 ) return [];

        foreach( $list as $row ) {
            self::_load_from_data($row);
        }

        return array_values(self::$_cache);
    }

    /**
     * Load template type objects by specifying an array of ids
     *
     * @param int[] $list Array of template type ids
     */
    public static function load_bulk($list)
    {
        if( !$list || !is_array($list) ) return [];

        $list2 = array();
        foreach( $list as $one ) {
            if( !is_numeric($one) || (int)$one < 1 ) continue;
            $one = (int)$one;
            if( isset(self::$_cache[$one]) ) continue;
            $list2[] = $one;
        }

        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.implode(',',$list).')';
        $list = $db->GetArray($query);
        if( !is_array($list) || count($list2) == 0 ) return [];

        $out = array();
        foreach( $list as $row ) {
            $out[] = self::_load_from_data($row);
        }
        return $out;
    }

    /**
     * Return the names of all loaded template types
     *
     * @return array of loaded type objects, maybe empty
     */
    public static function get_loaded_types()
    {
        if( is_array(self::$_cache) ) return array_keys(self::$_cache);
        return [];
    }

    /**
     * Get the assistant object with utility methods for this template type (if such an assistant object can be instantiated)
     *
     * @return \CMSMS\Layout\TemplateTypeAssistant
     * @since 2.2
     */
    public function get_assistant()
    {
        if( !$this->_assistant ) {
            $classnames = [];
            $classnames[] = '\CMSMS\internal\\'.$this->get_originator().$this->get_name().'_Type_Assistant';
            $classnames[] = '\CMSMS\Layout\\'.$this->get_originator().$this->get_name().'_Type_Assistant';
            $classnames[] = $this->get_originator().'_'.$this->get_name().'_Type_Assistant';
            foreach( $classnames as $cn ) {
                if( class_exists($cn) ) {
                    $tmp = new $cn();
                    if( is_a($tmp,'\CMSMS\Layout\TemplateTypeAssistant') ) {
                        $this->_assistant = $tmp;
                        break;
                    }
                }
            }
        }

        return $this->_assistant;
    }

    /**
     * Get a usage string for this template type.
     *
     * @since 2.2
     * @param string $name The name of the template object.
     * @return string
     */
    public function get_usage_string($name)
    {
        $name = trim($name);
        if( !$name ) return '';

        $assistant = $this->get_assistant();
        if( !$assistant ) return '';

        return $assistant->get_usage_string($name);
    }
} // end of class

#
# EOF
#
