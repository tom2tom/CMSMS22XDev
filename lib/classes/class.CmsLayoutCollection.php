<?php
#CMS Made Simple class CmsLayoutCollection
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

use CMSMS\HookManager;

/**
 * A class to manage a collection (or design) of Templates and Stylesheets
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell
 */
class CmsLayoutCollection
{
    /**
     * @ignore
     */
    const TABLENAME = 'layout_designs';

    /**
     * @ignore
     */
    const CSSTABLE  = 'layout_design_cssassoc';

    /**
     * @ignore
     */
    const TPLTABLE  = 'layout_design_tplassoc';

    /**
     * @ignore
     */
    private $_dirty = FALSE;

    /**
     * @ignore
     * @internal should be private but _load_from_data needs public
     */
    public $_data = [
     'id' => 0,
     'name' => '',
     'description' => '',
     'version' => '',
     'requires' => '',
     'dflt' => 0,
     'created' => 0,
     'modified' => 0
    ];

    /**
     * @ignore
     * @internal should be private but _load_from_data needs public
     */
    public $_css_assoc = [];

    /**
     * @ignore
     * @internal should be private but _load_from_data needs public
     */
    public $_tpl_assoc = [];

    /**
     * @ignore
     */
    private static $_raw_cache;

    /**
     * @ignore
     */
    private static $_dflt_id;

    // no cloning for this object
    private function __clone() {}

    /**
     * Get the design id
     * Only designs that have been saved to the database have an id.
     * @return int maybe 0
     */
    public function get_id()
    {
        return (int)$this->_data['id'];
    }

    /**
     * Get the design name
     * @return string
     */
    public function get_name()
    {
        return (string)$this->_data['name'];
    }

    /**
     * Set the design name
     * This marks the design as dirty
     *
     * @throws CmsInvalidDataException
     * @param string $str
     */
    public function set_name($str)
    {
        if( !CmsAdminUtils::is_valid_itemname($str)) throw new CmsInvalidDataException("Invalid characters in name: $str");
        $this->_data['name'] = $str;
        $this->_dirty = TRUE;
    }

    /**
     * Get the default flag
     * Note, only one design can be the default.
     *
     * @return bool
     */
    public function get_default()
    {
        return (bool)$this->_data['dflt'];
    }

    /**
     * Sets this design as the default design.
     * Sets the dirty flag.
     * Note, only one design can be the default.
     *
     * @param mixed $flag bool|int\string supported by cms_to_bool() Default true
     */
    public function set_default($flag = TRUE)
    {
        $flag = cms_to_bool($flag);
        $this->_data['dflt'] = ($flag) ? 1 : 0;
        $this->_dirty = TRUE;
    }

    /**
     * Get the design description
     *
     * @return string
     */
    public function get_description()
    {
        return (string)$this->_data['description'];
    }

    /**
     * Set the design description
     *
     * @param string $str
     */
    public function set_description($str)
    {
        $this->_data['description'] = trim($str);
        $this->_dirty = TRUE;
    }

    /**
     * Get the timestamp representing when this design was first recorded.
     * The creation timestamp is specified automatically on the first save
     *
     * @return int maybe 0
     */
    public function get_created()
    {
        return (int)$this->_data['created'];
    }

    /**
     * Get the timestamp representing the latest modification datetime of this design
     *
     * @return int maybe 0
     */
    public function get_modified()
    {
        return (int)$this->_data['modified'];
    }

    /**
     * Get the version of this design
     * @since 2.2.23F2
     * @return string
     */
    public function get_version()
    {
        return (string)$this->_data['version'];
    }

    /**
     * Set or clear the version of this design
     * @since 2.2.23F2
     * @param string $vers
     */
    public function set_version($vers)
    {
        $tmp = trim((string)$vers);
        if( $tmp ) {
            $tmp = str_replace([' ',','],['','.'],$tmp);
        }
        $this->_data['version'] = $tmp;
    }

    /**
     * Get the requirements/dependencies of this design, if any
     * @since 2.2.23F2
     * @param int Optional format 1-4
     * 1 for comma-separated string Default
     * 2 for <br>-separated string
     * 3 for newline-separated string
     * 4 for array each member an array like depname => [] or [op,depversion]
     * @return mixed array | string
     */
    public function get_requires($format = 1)
    {
        $res = (string)$this->_data['requires'];
        if( $res ) {
            switch ($format) {
                case 4:
                $arr = [];
                foreach(explode(',',$res) as $dep) {
                    if( preg_match('~^\s*([^<>=!\s]+)\s*([<>=!]{1,2})?\s*([a-zA-Z0-9.]+)?\s*$~',$dep,$matches) ) {
                        if( isset($matches[2]) && isset($matches[3]) ) {
                            $arr[$matches[1]] = [$matches[2],$matches[3]];
                        }
                        else {
                            $reqs[$matches[1]] = [];
                        }
                    }
                }
                return $arr;
                case 3:
                return str_replace(',',"\n",$res);
                case 2:
                return str_replace(',',"<br>\n",$res);
                default:
                return $res;
            }
            return (string)$this->_data['requires']; //TODO format
        }
        return ($format != 4) ? '' : [];
    }

    /**
     * Set or clear the dependencies of this design
     * @since 2.2.23F2
     * @param mixed $deps array | string
     */
    public function set_requires($deps)
    {
        if( $deps) {
            if( is_array($deps) ) {
                $out = [];
                foreach( $deps as $name => $detail) {
                    if( $detail ) {
                        $out[] = $name.implode('',$detail);
                    }
                    else {
                        $out[] = $name;
                    }
                }
                $deps = implode(',',$out);
            }
            else {
                $deps = str_replace(
                [' ','"',"'","\r\n","\n",'<br>',',,'],
                ['', '', '', ',',   ',', ',',   ','],
                trim($deps));
            }
        }
        else {
            $deps = '';
        }
        $this->_data['requires'] = $deps;
    }

    /**
     * Test if this design has stylesheets attached to it
     *
     * @return bool
     */
    public function has_stylesheets()
    {
        return ( is_array($this->_css_assoc) && $this->_css_assoc );
    }

    /**
     * Get the list of stylesheets (if any) associated with this design.
     *
     * @return array of integers
     */
    public function get_stylesheets()
    {
        return $this->_css_assoc;
    }

    /**
     * Set the list of stylesheets associated with this design
     *
     * @throws CmsLogicException
     * @param array $id_array Array of integer stylesheet ids.
     */
    public function set_stylesheets($id_array)
    {
        if( !is_array($id_array) ) return;

        foreach( $id_array as $one ) {
            if( !is_numeric($one) || $one < 1 ) throw new CmsLogicException('CmsLayoutCollection::set_stylesheets expects an array of integers');
        }

        $this->_css_assoc = $id_array;
        $this->_dirty = TRUE;
    }

    /**
     * Add a stylesheet to this design
     *
     * @throws CmsLogicException
     * @param mixed $css Either an integer stylesheet id, or a CmsLayoutStylesheet object
     */
    public function add_stylesheet($css)
    {
        $css_t = 0;
        if( is_object($css) && is_a($css,'CmsLayoutStylesheet') ) {
            $css_t = $css->get_id();
        }
        else if( is_numeric($css) && $css > 0 ) {
            $css_t = (int) $css;
        }
        if( $css_t < 1 ) throw new CmsLogicException('Invalid css id specified to CmsLayoutCollection::add_stylesheet');

        if( !in_array($css_t,$this->_css_assoc) ) {
            $this->_css_assoc[] = (int) $css_t;
            $this->_dirty = TRUE;
        }
    }

    /**
     * Remove a stylesheet from this design
     *
     * @throws CmsLogicException
     * @param mixed $css Either an integer stylesheet id, or a CmsLayoutStylesheet object
     */
    public function delete_stylesheet($css)
    {
        $css_t = 0;
        if( is_object($css) && is_a($css,'CmsLayoutStylesheet') ) {
            $css_t = $css->id;
        }
        else if( is_numeric($css) ) {
            $css_t = (int) $css;
        }
        if( $css_t < 1 ) throw new CmsLogicException('Invalid css id specified to CmsLayoutCollection::delete_stylesheet');

        if( !in_array($css_t,$this->_css_assoc) ) return;
        $t = array();
        foreach( $this->_css_assoc as $one ) {
            if( $css_t != $one ) {
                $t[] = $one;
            }
            else {
                // do we want to delete this css from the database?
            }
        }
        $this->_css_assoc = $t;
        $this->_dirty = TRUE;
    }

    /**
     * Test if this design has templates associated with it
     *
     * @return bool
     */
    public function has_templates()
    {
        if( count($this->_tpl_assoc) == 0 ) return FALSE;
        return TRUE;
    }

    /**
     * Return a list of the template ids associated with this template
     *
     * @return array of integers
     */
    public function get_templates()
    {
        if( $this->get_id() == 0 ) return [];
        if( !$this->has_templates() ) return [];

        return $this->_tpl_assoc;
    }

    /**
     * Set the list of templates associated with this design
     *
     * @throws CmsLogicException
     * @param array $id_array Array of integer template ids
     */
    public function set_templates($id_array)
    {
        if( !is_array($id_array) ) return;

        foreach( $id_array as $one ) {
            if( !is_numeric($one) && $one < 1 ) throw new CmsLogicException('CmsLayoutCollection::set_templates expects an array of integers');
        }

        $this->_tpl_assoc = $id_array;
        $this->_dirty = TRUE;
    }

    /**
     * Add a template to the list of templates associated with this design.
     *
     * @throws CmsLogicException
     * @param mixed $tpl Accepts either an integer template id, or an instance of a CmsLayoutTemplate object
     */
    public function add_template($tpl)
    {
        $tpl_id = 0;
        if( is_object($tpl) && is_a($tpl,'CmsLayoutTemplate') ) {
            $tpl_id = $tpl->get_id();
        }
        else if( is_numeric($tpl) ) {
            $tpl_id = (int) $tpl;
        }
        if( $tpl_id < 1 ) throw new CmsLogicException('Invalid template id specified to CmsLayoutCollection::add_template');

        if( !is_array($this->_tpl_assoc) ) $this->_tpl_assoc = array();
        if( !in_array($tpl_id,$this->_tpl_assoc) ) $this->_tpl_assoc[] = (int) $tpl_id;
        $this->_dirty = TRUE;
    }

    /**
     * Delete a template from the list of templates associated with this design
     *
     * @throws CmsInvalidDataException
     * @param mixed $tpl Either an integer template id, or a CmsLayoutTemplate object
     */
    public function delete_template($tpl)
    {
        $tpl_id = 0;
        if( is_object($tpl) && is_a($tpl,'CmsLayoutTemplate') ) {
            $tpl_id = $tpl->get_id();
        }
        else if( is_numeric($tpl) ) {
            $tpl_id = (int) $tpl;
        }
        if( $tpl_id <= 0 ) throw new CmsLogicException('Invalid template id specified to CmsLayoutCollection::add_template');

        if( !in_array($tpl_id,$this->_tpl_assoc) ) return;
        $t = array();
        foreach( $this->_tpl_assoc as $one ) {
            if( $tpl_id != $one ) {
                $t[] = $one;
            }
            else {
                // do we want to delete this css from the database?
            }
        }
        $this->_tpl_assoc = $t;
        $this->_dirty = TRUE;
    }

    /**
     * Validate this object before saving.
     *
     * @throws CmsInvalidDataException
     */
    protected function validate()
    {
        if( $this->get_name() == '' ) throw new CmsInvalidDataException('A Design must have a name');
        if( !CmsAdminUtils::is_valid_itemname($this->get_name()) ) {
            throw new CmsInvalidDataException('There are invalid characters in the design name.');
        }

        if( count($this->_css_assoc) ) {
            $t1 = array_unique($this->_css_assoc);
            if( count($t1) != count($this->_css_assoc) ) throw new CmsInvalidDataException('Duplicate CSS Ids exist in design.');
        }

        $db = CmsApp::get_instance()->GetDb();
        $did = $this->get_id();
        if( $did > 0 ) {
            $query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
            $tmp = $db->GetOne($query,array($this->get_name(),$did));
        }
        else {
            $query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
            $tmp = $db->GetOne($query,array($this->get_name()));
        }
        if( $tmp > 0 ) throw new CmsInvalidDataException('Collection/Design with the same name already exists.');
    }

    /**
     * @ignore
     */
    private function _insert()
    {
        if( !$this->_dirty ) return;
        $this->validate();

        $pd = ($this->get_default()) ? 1 : 0;
        $now = time();
        $db = CmsApp::get_instance()->GetDb();
        $query = 'INSERT INtO '.CMS_DB_PREFIX.self::TABLENAME.' (name,description,dflt,created,modified) VALUES (?,?,?,?,?)';
        $dbr = $db->Execute($query,array($this->get_name(),$this->get_description(),$pd,$now,$now));
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        $this->_data['id'] = $db->Insert_ID();
        $did = (int)$this->_data['id'];

        if( $this->get_default() ) {
            $query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET dflt = 0 WHERE id != ?';
            $db->Execute($query,array($did)); //unreliable return value after UPDATE
            if( $db->ErrorNo() != 0 ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        }

        if( count($this->_css_assoc) ) {
            $query = 'INSERT INTO '.CMS_DB_PREFIX.self::CSSTABLE.' (design_id,css_id,item_order) VALUES (?,?,?)';
            for( $i = 0, $n = count($this->_css_assoc); $i < $n; $i++ ) {
                $css_id = $this->_css_assoc[$i];
                $dbr = $db->Execute($query,array($did,$css_id,$i+1));
            }
        }
        if( count($this->_tpl_assoc) ) {
            $query = 'INSERT INTO '.CMS_DB_PREFIX.self::TPLTABLE.' (design_id,tpl_id) VALUES(?,?)';
            for( $i = 0, $n = count($this->_tpl_assoc); $i < $n; $i++ ) {
                $tpl_id = $this->_tpl_assoc[$i];
                $dbr = $db->Execute($query,array($did,$tpl_id));
            }
        }

        $this->_dirty = FALSE;
        audit($did,'Design',"Created: {$this->get_name()}");
    }

    /**
     * @ignore
     */
    private function _update()
    {
        if( !$this->_dirty ) return;
        $this->validate();

        $did = $this->get_id();
        $pd = ($this->get_default()) ? 1 : 0;
        $db = CmsApp::get_instance()->GetDb();
        $query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET name = ?, description = ?, dflt = ?, modified = ? WHERE id = ?';
        $db->Execute($query,array($this->get_name(), $this->get_description(), $pd, time(), $did)); //unreliable return value after UPDATE
        if( $db->ErrorNo() != 0 ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        if( $this->get_default() ) {
            $query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET dflt = 0 WHERE id != ?';
            $db->Execute($query,array($did)); //unreliable return value after UPDATE
            if( $db->ErrorNo() != 0 ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        }

        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id = ?';
        $db->Execute($query,array($did));

        if( count($this->_css_assoc) ) {
            $query = 'INSERT INTO '.CMS_DB_PREFIX.self::CSSTABLE.' (design_id,css_id,item_order) VALUES (?,?,?)';
            for( $i = 0, $n = count($this->_css_assoc); $i < $n; $i++ ) {
                $css_id = $this->_css_assoc[$i];
                $dbr = $db->Execute($query,array($did,$css_id,$i+1));
            }
        }

        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id = ?';
        $db->Execute($query,array($did));

        if( count($this->_tpl_assoc) ) {
            $query = 'INSERT INTO '.CMS_DB_PREFIX.self::TPLTABLE.' (design_id,tpl_id) VALUES (?,?)';
            for( $i = 0, $n = count($this->_tpl_assoc); $i < $n; $i++ ) {
                $tpl_id = $this->_tpl_assoc[$i];
                $dbr = $db->Execute($query,array($did,$tpl_id));
            }
        }

        $this->_dirty = FALSE;
        audit($did,'Design',"Updated: {$this->get_name()}");
    }

    /**
     * Save this design
     * This method will send the AddDesignPre and AddDesignPost events before and after saving a new design
     * and the EditDesignPre and EditDesignPost events before and after saving an existing design.
     */
    public function save()
    {
        if( $this->get_id() > 0 ) {
            HookManager::do_hook('Core::EditDesignPre', [ get_class($this) => $this ]);
            $this->_update();
            HookManager::do_hook('Core::EditDesignPost', [ get_class($this) => $this ]);
            return;
        }
        HookManager::do_hook('Core::AddDesignPre', [ get_class($this) => $this ]);
        $this->_insert();
        HookManager::do_hook('Core::AddDesignPost', [ get_class($this) => $this ]);
    }

    /**
     * Delete this design
     * This class will not allow deleting designs that have templates associated with them.
     *
     * @throws CmsLogicException
     * @param bool $force Force deleting the design even if there are templates attached
     */
    public function delete($force = FALSE)
    {
        $did = $this->get_id();
        if( $did < 1 ) return;

        if( !$force && $this->has_templates() ) throw new CmsLogicException('Cannot Delete a Design that has Templates Attached');

        HookManager::do_hook('Core::DeleteDesignPre', [ get_class($this) => $this ]);
        $db = CmsApp::get_instance()->GetDb();
        if( count($this->_css_assoc) ) {
            $query = 'DELETE FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id = ?';
            $dbr = $db->Execute($query,array($did));
            $this->_css_assoc = array();
            $this->_dirty = TRUE;
        }

        if( count($this->_tpl_assoc) ) {
            $query = 'DELETE FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id = ?';
            $dbr = $db->Execute($query,array($did));
            $this->_tpl_assoc = array();
            $this->_dirty = TRUE;
        }

        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
        $dbr = $db->Execute($query,array($did));

        audit($did,'Design',"Deleted: {$this->get_name()}");
        HookManager::do_hook('Core::DeleteDesignPost', [ get_class($this) => $this ]);
        $this->_data['id'] = 0;
        $this->_dirty = TRUE;
    }

    /**
     * @ignore
     */
    protected static function _load_from_data($row)
    {
        foreach( [
            'id' => 0,
            'name' => '',
            'dflt' => 0,
            'description' => '',
            'created' => 0,
            'modified' => 0
            ] as $fld => $val ) {
            if( !isset($row[$fld]) ) {
                $row[$fld] = $val;
            }
            elseif( $val === '' ) {
                $row[$fld] = (string)$row[$fld];
            }
            else {
                $row[$fld] = (int)$row[$fld];
            }
        }
        $css = ( !empty($row['css']) ) ? $row['css'] : [];
        $tpls = ( !empty($row['templates']) ) ? $row['templates'] : [];
        unset($row['css'],$row['templates']);

        $ob = new CmsLayoutCollection();
        $ob->_data = $row;
        if( is_array($css) && count($css) ) $ob->_css_assoc = $css;
        if( is_array($tpls) && count($tpls) ) $ob->_tpl_assoc = $tpls;

        return $ob;
    }

    /**
     * Load a Design object
     *
     * @throws CmsDataNotFoundException
     * @param mixed $x - Accepts either an integer design id, or a design name,
     * @return CmsLayoutCollection
     */
    public static function load($x)
    {
        if ($x == 0) return null; //adding, nothing to load
        $db = CmsApp::get_instance()->GetDb();
        $row = [];
        if( is_numeric($x) && $x > 0 ) {
            if( is_array(self::$_raw_cache) && count(self::$_raw_cache) ) {
                if( isset(self::$_raw_cache[$x]) ) return self::_load_from_data(self::$_raw_cache[$x]);
            }
            $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
            $row = $db->GetRow($query,array((int)$x));
        }
        else if( is_string($x) && strlen($x) > 0 ) {
            if( is_array(self::$_raw_cache) && count(self::$_raw_cache) ) {
                foreach( self::$_raw_cache as $row ) {
                    if( $row['name'] == $x ) return self::_load_from_data($row);
                }
            }

            $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
            $row = $db->GetRow($query,array(trim($x)));
        }

        if( !is_array($row) || count($row) == 0 ) throw new CmsDataNotFoundException('Could not find design row identified by '.$x);

        // get attached css
        $query = 'SELECT css_id FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id = ? ORDER BY item_order';
        $tmp = $db->GetCol($query,array((int) $row['id']));
        if( is_array($tmp) && count($tmp) ) $row['css'] = $tmp;

        // get attached templates
        $query = 'SELECT tpl_id FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id = ?';
        $tmp = $db->GetCol($query,array((int) $row['id']));
        if( is_array($tmp) && count($tmp) ) $row['templates'] = $tmp;

        self::$_raw_cache[$row['id']] = $row;
        return self::_load_from_data($row);
    }

    /**
     * Load all designs
     *
     * @param string $quick Do not load the templates and stylesheets.
     * @return array CmsLayoutCollection objects ordered by their design_id, or maybe empty
     */
    public static function get_all($quick = FALSE)
    {
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY name ASC';
        $db = CmsApp::get_instance()->GetDb();
        $dbr = $db->GetArray($query);
        if( is_array($dbr) && count($dbr) ) {
            $ids = array();
            $cache = array();
            foreach( $dbr as $row ) {
                $ids[] = $row['id'];
                $cache[$row['id']] = $row;
            }

            if( !$quick ) {
                $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id IN ('.implode(',',$ids).') ORDER BY design_id,item_order';
                $dbr2 = $db->GetArray($query);
                if( is_array($dbr2) && count($dbr2) ) {
                    foreach( $dbr2 as $row ) {
                        if( !isset($cache[$row['design_id']]) ) continue; // orphaned entry, bad.
                        $design = &$cache[$row['design_id']];
                        if( !isset($design['css']) ) $design['css'] = array();
                        if( !in_array($row['css_id'],$design['css']) ) $design['css'][] = $row['css_id'];
                    }
                }

                $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id IN ('.implode(',',$ids).') ORDER BY design_id';
                $dbr2 = $db->GetArray($query);
                if( is_array($dbr2) && count($dbr2) ) {
                    foreach( $dbr2 as $row ) {
                        if( !isset($cache[$row['design_id']]) ) continue; // orphaned entry, bad.
                        $design = &$cache[$row['design_id']];
                        if( !isset($design['templates']) ) $design['templates'] = array();
                        $design['templates'][] = $row['tpl_id'];
                    }
                }
            }

            self::$_raw_cache = $cache;

            $out = array();
            foreach( $cache as $row ) {
                $out[] = self::_load_from_data($row);
            }
            return $out;
        }
        return [];
    }

    /**
     * Get a list of designs
     *
     * @param array each member design_id=>design name, or maybe empty
     */
    public static function get_list()
    {
        $designs = self::get_all(TRUE);
        if( is_array($designs) && count($designs) ) {
            $out = array();
            foreach( $designs as $one ) {
                $out[$one->get_id()] = $one->get_name();
            }
            return $out;
        }
        return [];
    }

    /**
     * Load the default design
     *
     * @throws CmsInvalidDataException
     * @return CmsLayoutCollection
     */
    public static function load_default()
    {
        if( self::$_dflt_id == '' ) {
            $query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE dflt = 1';
            $db = CmsApp::get_instance()->GetDb();
            $tmp = (int) $db->GetOne($query);
            if( $tmp > 0 ) self::$_dflt_id = $tmp;
        }

        if( self::$_dflt_id > 0 ) return self::load(self::$_dflt_id);

        throw new CmsInvalidDataException('There is no default design selected');
    }

    /**
     * Given a base name, suggest a name for a copied design
     * @see also: CmsAdminUtils::ITEMNAME_REGEX
     *
     * @param string $newname Optional name-prefix
     * @return string possibly with numeric suffix (2+) , or maybe empty
     */
    public static function suggest_name($newname = '')
    {
        if( $newname == '' ) $newname = 'New Design';
        $list = self::get_list();
        $origname = $newname;
        for( $n = 2; $n <= 100 && in_array($newname,$list); $n++ ) {
            $newname = $origname.' '.$n;
        }
        if( $n < 101 ) return $newname;
        return '';
    }
} // end of class

?>
