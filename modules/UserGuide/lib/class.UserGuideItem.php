<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/
namespace UserGuide;

use CmsApp;
use const CMS_DB_PREFIX;

class UserGuideItem
{
    private $_data = [
    'active' => true,
    'admin' => false,
    'author' => '',
    'author_id' => 0,
    'content' => '',
    'create_date' => '',
    'id' => 0,
    'name' => '',
    'modified_date' => '',
    'position' => 0,
    'restricted' => '',
    'revision' => '',
    'search' => true,
    'sheets' => '',
    'smarty' => false,
    'template_id' => 0,
    'wysiwyg' => false
    ];

    #[\ReturnTypeWillChange]
    public function __get($key)//: mixed
    {
        if (array_key_exists($key, $this->_data)) {
           return $this->_data[$key];
        }
        return null; // oops!
    }

    #[\ReturnTypeWillChange]
    public function __set($key, $val)//: void
    {
        switch($key) {
            case 'name':
            case 'content':
            case 'revision':
            case 'restricted': //TODO somewhere, flatten encode etc $val
            case 'author':
            case 'sheets':
            case 'modified_date':
                $this->_data[$key] = trim((string)$val);
                break;
            case 'position':
            case 'author_id':
            case 'template_id':
                $this->_data[$key] = (int)$val;
                break;
            case 'active':
            case 'admin':
            case 'search':
            case 'smarty':
            case 'wysiwyg':
                $this->_data[$key] = (bool)$val; // OR cms_to_bool($val)
                break;
        }
    }

    public function save()
    {
        // TODO confirm $this->_data[] valid, before recording
        if ($this->id > 0) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    public function delete()
    {
        if ($this->id == 0) {
            return false;
        }
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.'module_userguide WHERE id = ?';
        $dbr = $db->Execute($sql, [$this->id]);
        if (!$dbr) {
            return false;
        }
        $this->_data['id'] = 0;
        return true;
    }

    public function toggle_active()
    {
        if ($this->id == 0) {
            return false;
        }
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'UPDATE '.CMS_DB_PREFIX.'module_userguide SET active = ? WHERE id = ?';
        $db->Execute($sql, [!(bool)$this->active, $this->id]);
        if ($db->Affected_Rows() == 1 && $db->ErrorNo() == 0) { //reliable success-check after UPDATE
            $this->active = !$this->active;
            return true;
        } else {
            return false;
        }
    }

    public function toggle_admin_only()
    {
        if ($this->id == 0) {
            return false;
        }
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'UPDATE '.CMS_DB_PREFIX.'module_userguide SET admin = ? WHERE id = ?';
        $db->Execute($sql, [!(bool)$this->admin, $this->id]);
        if ($db->Affected_Rows() == 1 && $db->ErrorNo() == 0) { //reliable success-check after UPDATE
            $this->admin = !$this->admin;
            return true;
        } else {
            return false;
        }
    }

    public function toggle_searchable()
    {
        if ($this->id == 0) {
            return false;
        }
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'UPDATE '.CMS_DB_PREFIX.'module_userguide SET search = ? WHERE id = ?';
        $db->Execute($sql, [!(bool)$this->search, $this->id]);
        if ($db->Affected_Rows() == 1 && $db->ErrorNo() == 0) { //reliable success-check after UPDATE
            $this->search = !$this->search;
            return true;
        } else {
            return false;
        }
    }

    /* internal */
    public function fill_from_array($row)
    {
        foreach ($row as $key => $val) {
            if (array_key_exists($key, $this->_data)) {
                if ($val !== null) {
                    $this->_data[$key] = $val;
                } else {
                    $this->$key = $val; // convert the type
                }
            }
        }
    }

    public static function load_by_id($id)
    {
        $id = (int) $id;
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.'module_userguide WHERE id = ?';
        $row = $db->GetRow($sql, [$id]);
        if (is_array($row)) {
            $obj = new self();
            $obj->fill_from_array($row);
            return $obj;
        }
        return null; // no object
    }

    protected function insert()
    {
        $db = CmsApp::get_instance()->GetDb();
        $now = trim($db->DBTimeStamp(time()), "'");
        $sql = 'INSERT INTO '.CMS_DB_PREFIX.'module_userguide
(name,
revision,
position,
active,
admin,
restricted,
search,
smarty,
author_id,
author,
template_id,
sheets,
create_date,
modified_date,
content)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $dbr = $db->Execute($sql, [
            $this->name,
            $this->revision,
            $this->position,
            $this->active,
            $this->admin,
            $this->restricted,
            $this->search,
            $this->smarty,
            $this->author_id,
            $this->author,
            $this->template_id,
            $this->sheets,
            $now,
            null,
            $this->content
        ]);
        $this->_data['create_date'] = $now;

        if (!$dbr) {
            return false;
        }
        $this->_data['id'] = (int)$db->Insert_ID();
        return true;
    }

    protected function update()
    {
        $db = CmsApp::get_instance()->GetDb();
        $now = trim($db->DBTimeStamp(time()), "'");
        $sql = 'UPDATE '.CMS_DB_PREFIX.'module_userguide SET
name=?,
revision=?,
position=?,
active=?,
admin=?,
restricted=?,
search=?,
smarty=?,
author_id=?,
author=?,
template_id=?,
sheets=?,
modified_date=?,
content=?
WHERE id=?';
        $db->Execute($sql, [
            $this->name,
            $this->revision,
            $this->position,
            $this->active,
            $this->admin,
            $this->restricted,
            $this->search,
            $this->smarty,
            $this->author_id,
            $this->author,
            $this->template_id,
            $this->sheets,
            $now,
            $this->content,
            $this->id]);
        $this->modified_date = $now;
        return $db->Affected_Rows() == 1 && $db->ErrorNo() == 0; //reliable success-check after UPDATE
    }

    //TODO support processing restricted property to/from array having having value(s) like
    // user:* or group:* or perm:* or status:* or until:* or after:*
    // possibly encoded, crypted
}
