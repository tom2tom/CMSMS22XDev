<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/
namespace UserGuide;

use cms_utils;
use CmsApp;
use CmsApp;
use Exception;
use UserGuide; //module class in global space
use UserGuide\UserGuideUtils; //not strictly needed here
use const CMS_DB_PREFIX;
use function cleanValue;

class UserGuideImportOldGuides
{
    private $tomod;
    private $frommod;

    public function __construct(UserGuide $mod = null)
    {
        $this->frommod = cms_utils::get_module('UsersGuide');
        if (!$this->frommod) {
            throw new Exception('UsersGuide module not present');
        }
        if ($mod) {
            $this->tomod = $mod;
        } else {
            $this->tomod = cms_utils::get_module('UserGuide');
        }
    }

    /**
     *
     * @return error string(s) array, maybe empty
     */
    public function migrate()
    {
/*    CMS_DB_PREFIX.module_UsersGuide has
      man_title >> sanit >> name
      man_content >> sanit >> content
      man_smarty >> smarty
   No XML
   Preference('module_name' irrelevant
   Preference('admin_section' irrelevant
   Preference('style_css' irrelevant
*/
        $errors = [];
        $db = CmsApp::get_instance()->GetDb();
        $data = $db->GetArray('SELECT man_title,man_smarty,man_content FROM '.CMS_DB_PREFIX.'module_UsersGuide ORDER BY man_title');
        if ($data) {
            $rev = 'Imported from UsersGuide';
            $when = '2014-07-14 09:25:00'; //date-time of latest UsersGuide release
            $totable = CMS_DB_PREFIX.'module_userguide';
            $existing = $db->GetCol("SELECT name FROM $totable");
            if ($existing) {
                $pos = (int)$db->GetOne("SELECT MAX(position) FROM $totable");
            } else {
                $pos = 0;
            }
            //no placeholders for restricted, template_id, styles ?
            $sql = "INSERT INTO $totable
(name,revision,position,active,admin,search,smarty,create_date,modified_date,content)
VALUES (?,'$rev',?,1,1,1,?,'$when',null,?)";
            foreach ($data as $row) {
                $name = trim(cleanValue($row['man_title']));
                if ($existing) {
                    $name = UserGuideUtils::uniquename($name, $existing);
                } else {
                    $existing[] = $name;
                }
                $content = UserGuideUtils::cleanContent($row['man_content']);
                $db->Execute($sql, [$name, ++$pos, (int)$row['man_smarty'], $content]);
            }
        } elseif ($db->ErrorNo() == 0) {
            $errors[] = 'No UsersGuide data are available for import.';
        } else {
            $errors[] = 'Database import failed: '.$db->ErrorMsg();
        }
        return $errors;
    }
} // class
