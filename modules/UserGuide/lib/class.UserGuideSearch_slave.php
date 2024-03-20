<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/
namespace UserGuide;

use AdminSearch_slave;
use cms_utils;
use CmsApp;
use const CMS_DB_PREFIX;
//use function check_permission;
//use function get_userid;

final class UserGuideSearch_slave extends AdminSearch_slave
{
    public function get_name()
    {
        $mod = cms_utils::get_module('UserGuide');
        return $mod->Lang('adminsearch_lbl');
    }

    public function get_description()
    {
        $mod = cms_utils::get_module('UserGuide');
        return $mod->Lang('adminsearch_desc');
    }

    public function check_permission()
    {
/* any user may search       $userid = get_userid();
        return check_permission($userid,UserGuide::USE_PERM)||
               check_permission($userid,UserGuide::MANAGE_PERM);
*/
        return true;
    }

    public function get_matches()
    {
        $mod = cms_utils::get_module('UserGuide');
        if (!is_object($mod)) return [];
//      $userid = get_userid();
        $all = $this->include_inactive_items();
        $output = [];

        $sql = 'SELECT id,name,content FROM '.CMS_DB_PREFIX.'module_userguide'; //TODO revision field too ?
        if (!$all) { $sql .= ' WHERE active!=0'; }
        $sql .= ' ORDER BY name';
        $db = CmsApp::get_instance()->GetDb();
        $results = $db->GetArray($sql);
        if ($results && is_array($results)) {
            $needle = $this->get_text();
            foreach ($results as $row) {
                if ($this->check_match($row, $needle)) {
                    $res = $this->get_match_info($row, $mod);
                    $output[] = json_encode($res);
                }
            }
        }
        return $output;
    }

    private function check_match($row, $needle)
    {
        static $findfunc = null;
        if ($findfunc === null) {
            $findfunc = $this->search_casesensitive() ? 'strpos' : 'stripos'; // too bad if UTF8 char(s) in there with stripos!
        }
        if ($findfunc($row['name'], $needle) !== false) {
            return true;
        }
        return ($findfunc($row['content'], $needle) !== false);
    }

    private function get_match_info($row, $mod)
    {
        $resultSet = $this->get_resultset($row['name'], '',
            $mod->create_url('m1_', 'view', '', ['gid'=>$row['id']]));
        $from = $row['name'];
        $num = $this->get_number_of_occurrences($from);
        if ($num > 0) {
            $resultSet->count += $num;
            if ($this->show_snippets()) {
                $resultSet->locations[lang('name')] = $this->generate_snippets($from);
            }
        }
        $from = $row['content'];
        $num = $this->get_number_of_occurrences($from);
        if ($num > 0) {
            $resultSet->count += $num;
            if ($this->show_snippets()) {
                $resultSet->locations[lang('content')] = $this->generate_snippets($from);
            }
        }
        //TODO revision field also?
        return $resultSet;
    }
} // class
