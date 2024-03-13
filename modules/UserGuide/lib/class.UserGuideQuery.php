<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/
namespace UserGuide;

use CmsApp;
use CmsDbQueryBase;
use CmsSQLErrorException;
use const CMS_DB_PREFIX;

class UserGuideQuery extends CmsDbQueryBase
{
    //mixed $args string | array query parameters
    public function __construct($args = '')
    {
        parent::__construct($args);
        if (isset($this->_args['limit'])) {
            $this->_limit = (int) $this->_args['limit'];
        }
    }

    public function execute()
    {
        if ($this->_rs) {
            return;
        }
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.'module_userguide';
        if (isset($this->_args['active'])) {
            // retrieve only active or inactive rows
            $tmp = $this->_args['active'];
            if ($tmp === 0) {
                $sql .= ' WHERE active = 0';
            } elseif ($tmp === 1) {
                $sql .= ' WHERE active = 1';
            }
        }
        $sql .= ' ORDER BY position';
        $db = CmsApp::get_instance()->GetDb();
        $this->_rs = $db->SelectLimit($sql, $this->_limit, $this->_offset);
        if ($db->ErrorMsg()) {
            throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
        }
        $sql2 = str_replace(['SELECT *', 'ORDER BY position'], ['SELECT COUNT(*)', ''], $sql);
        $this->_totalmatchingrows = $db->GetOne($sql2);
    }

    public function GetObject()
    {
        $obj = new UserGuideItem();
        $obj->fill_from_array($this->fields);
        return $obj;
    }

    public function updatePositions()
    {
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SET @rownumber = 0';
        $dbr = $db->Execute($sql);
        $sql = 'UPDATE '.CMS_DB_PREFIX.'module_userguide
SET position = (@rownumber:=@rownumber+1)
ORDER BY position';
        $dbr = $db->Execute($sql);
        if ($db->ErrorMsg()) {
            throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg(). '(updatePositions)');
        }
        return $dbr;
    }
}
