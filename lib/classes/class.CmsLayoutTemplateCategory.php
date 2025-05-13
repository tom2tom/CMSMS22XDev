<?php
#CMS Made Simple class CmsLayoutTemplateCategory
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
 * A class representing a template category.
 *
 * Templates can be optionally organized into categories, this class manages the category itself.
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell
 */
class CmsLayoutTemplateCategory
{
    /**
     * @ignore
     */
    const TABLENAME = 'layout_tpl_categories';

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
     'item_order' => 0,
     'modified' => 0
    ];

    // no cloning for this object
    private function __clone() {}

    /**
     * Get the category id
     *
     * @return int category id or 0 if this object has no id.
     */
    public function get_id()
    {
        return (int)$this->_data['id'];
    }

    /**
     * Get the category name
     *
     * @return string
     */
    public function get_name()
    {
        return (string)$this->_data['name'];
    }

    /**
     * Set the category name.
     *
     * The category name must be unique, and can only contain certain characters.
     *
     * @throws CmsInvalidDataException
     * @param sting $str The template type name.
     */
    public function set_name($str)
    {
        $str = trim((string)$str);
        if( !$str ) throw new CmsInvalidDataException('Name cannot be empty');
        if( !CmsAdminUtils::is_valid_itemname($str) ) {
            throw new CmsInvalidDataException('Invalid characters in name');
        }
        $this->_data['name'] = $str;
        $this->_dirty = TRUE;
    }

    /**
     * Get the category description
     *
     * @return string
     */
    public function get_description()
    {
        return (string)$this->_data['description'];
    }

    /**
     * Set the category description
     *
     * @param string $str The description, maybe empty
     */
    public function set_description($str)
    {
        $this->_data['description'] = trim((string)$str);
        $this->_dirty = TRUE;
    }

    /**
     * Get the category order
     *
     * @return int possibly 0
     */
    public function get_item_order()
    {
        if( isset($this->_data['item_order']) ) return (int)$this->_data['item_order'];
        return 0;
    }

    /**
     * Set the item order.
     *
     * The item order must be unique and incremental
     * Only '> 0' validation is done in this method.
     *
     * @param int $idx
     */
    public function set_item_order($idx)
    {
        // description is allowed to be empty.
        $idx = (int)$idx;
        if( $idx < 1 ) return;
        $this->_data['item_order'] = $idx;
        $this->_dirty = TRUE;
    }

    /**
     * Get the timestamp representing when this category was last saved to the database
     *
     * @return int possibly 0
     */
    public function get_modified()
    {
        return (int)$this->_data['modified'];
    }

    /**
     * Validate the correctness of this object
     * @throws CmsInvalidDataException
     */
    protected function validate()
    {
        if( !$this->get_name() ) throw new CmsInvalidDataException('A Template Categoy must have a name');
        if( !CmsAdminUtils::is_valid_itemname($this->get_name()) ) {
            throw new CmsInvalidDataException('Name must contain only letters, numbers and underscores.');
        }

        $db = cmsms()->GetDb();
        $tmp = 0;
        if( !$this->get_id() ) {
            $query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
            $tmp = $db->GetOne($query,array($this->get_name()));
        }
        else {
            $query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
            $tmp = $db->GetOne($query,array($this->get_name(),$this->get_id()));
        }
        if( $tmp ) {
            throw new CmsInvalidDataException('A Template Categoy with the same name already exists');
        }
    }

    /**
     * @ignore
     */
    protected function _insert()
    {
        if( !$this->_dirty ) return;
        $this->validate();

        $db = cmsms()->GetDb();
        $query = 'SELECT max(item_order) FROM '.CMS_DB_PREFIX.self::TABLENAME;
        $item_order = $db->GetOne($query);
        if( !$item_order ) $item_order=0;
        $item_order++;
        $this->_data['item_order'] = $item_order;

        $query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,description,item_order,modified) VALUES (?,?,?,?)';
        $dbr = $db->Execute($query,array($this->get_name(),$this->get_description(),
                                         $this->get_item_order(),time()));
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        $this->_data['id'] = $db->Insert_ID();
        $this->_dirty = FALSE;
        audit($this->_data['id'],'Template category',"Created: {$this->get_name()}");
    }

    /**
     * @ignore
     */
    protected function _update()
    {
        if( !$this->_dirty ) return;
        $this->validate();

        $db = cmsms()->GetDb();
        $query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET name = ?, description = ?, item_order = ?, modified = ? WHERE id = ?';
        $db->Execute($query,array($this->get_name(),
                                  $this->get_description(),
                                  $this->get_item_order(),
                                  time(),(int)$this->get_id()));
        if( $db->Affected_Rows() != 1 || $db->ErrorNo() != 0 ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        $this->_dirty = FALSE;
        audit($this->get_id(),'Template category',"Updated: {$this->get_name()}");
    }

    /**
     * Save this object to the database
     * @throws CmsSQLErrorException
     * @throws CmsInvalidDataException
     */
    public function save()
    {
        if( $this->get_id() == 0 ) return $this->_insert();
        return $this->_update();
    }

    /**
     * Delete this object from the database
     *
     * This method will delete the object from the database, and erase the item order and id values
     * from this object, suitable for re-saving
     *
     * @throw CmsSQLErrorException
     */
    public function delete()
    {
        if( !$this->get_id() ) return;

        $db = cmsms()->GetDb();
        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
        $dbr = $db->Execute($query,array($this->get_id()));
        if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        $query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET item_order = item_order - 1 WHERE item_order > ?';
        $db->Execute($query,array($this->_data['item_order']));

        audit($this->get_id(),'Template category',"Deleted: {$this->get_name()}");
        $this->_data['item_order'] = 0;
        $this->_data['id'] = 0;
        $this->_dirty = TRUE;
    }

    /**
     * @ignore
     */
    private static function _load_from_data($row)
    {
        foreach( [
         'id' => 0,
         'name' => '',
         'description' => '',
         'item_order' => 0,
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
        $ob = new self();
        $ob->_data = $row;
        return $ob;
    }

    /**
     * Load a category object from the database
     *
     * @throws CmsDataNotFoundException
     * @param int|string $val Either the integer category id, or the category name
     * @return self
     */
    public static function load($val)
    {
        $db = cmsms()->GetDb();
        $row = [];
        if( is_numeric($val) && $val > 0 ) {
            $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
            $row = $db->GetRow($query,array((int)$val));
        }
        else {
            $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
            $row = $db->GetRow($query,array($val));
        }
        if( !is_array($row) || count($row) == 0 ) throw new CmsDataNotFoundException('Could not find template category identified by '.$val);

        return self::_load_from_data($row);
    }

    /**
     * Load a set of categories from the database
     *
     * @param string $prefix An optional category name prefix.
     * @return array CmsLayoutTemplateCategory objects ordered by their item_order, or maybe empty
     */
    public static function get_all($prefix = '')
    {
        $db = cmsms()->GetDb();
        if( $prefix ) {
            $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name LIKE ? ORDER BY item_order ASC';
            $res = $db->GetArray($query,array($prefix.'%'));
        }
        else {
            $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY item_order ASC';
            $res = $db->GetArray($query);
        }
        $out = array();
        if( $res ) {
            foreach( $res as $row ) {
                $out[] = self::_load_from_data($row);
            }
        }
        return $out;
    }
} // end of class

#
# EOF
#
?>
