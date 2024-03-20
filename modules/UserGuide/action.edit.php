<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

use UserGuide\UserGuideItem;
use UserGuide\UserGuideQuery;
use UserGuide\UserGuideUtils;

if (!isset($gCms)) {
    exit;
}
if (!$this->CheckPermission(UserGuide::MANAGE_PERM)) {
    return;
}
if (isset($params['cancel'])) {
    $this->SetMessage($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab('list');
}

if (isset($params['gid']) && $params['gid'] > 0) {
    $item = UserGuideItem::load_by_id((int)$params['gid']);
    if (is_object($item)) {
        $newitem = false;
    } else {
        //TODO report error
        $item = new UserGuideItem();
        $newitem = true;
    }
} else {
    $item = new UserGuideItem();
    $newitem = true;
}

$userid = get_userid();

if (isset($params['submit']) || isset($params['apply'])) {
    $errors = [];
    $item->name = trim(cleanValue($params['name']));
    if ($item->name) {
        // prevent name-duplication
        $sql = 'SELECT name FROM '.CMS_DB_PREFIX.'module_userguide WHERE id!=?';
        $data = $db->GetCol($sql, [$params['gid']]);
        if ($data) {
            $item->name = UserGuideUtils::uniquename($item->name, $data);
        }
    } else {
        $errors[] = $this->Lang('err_noname');
    }
    $item->active = !empty($params['active']);
    $item->admin = !empty($params['admin']);
    $item->author_id = $userid;
    $val = (!empty($params['author'])) ? trim(cleanValue($params['author'])) : '';
    if ($val) {
        $sql = 'SELECT username,first_name,last_name FROM '.CMS_DB_PREFIX.'users';
        $data = $db->GetArray($sql);
        foreach ($data as &$row) {
            $row['fullname'] = trim($row['first_name'].' '.$row['last_name']);
        }
        unset($row);
        if (!(in_array($val, array_column($data, 'fullname')) || in_array($val, array_column($data, 'username')))) {
            $item->author = $val;
        } else {
            $item->author = ''; //TODO sensible?
        }
    } else {
        $item->author = '';
    }

    if (isset($params['sheets'])) {
        $val = implode(',', $params['sheets']);
    } else {
        $val = '';
    }
    if (!$val) {
      //TODO handle absence i.e. no sheet is recorded, or none selected
    }
    $item->sheets = $val;

    $item->content = UserGuideUtils::cleanContent($params['content']);
    $item->modified_date = date('Y-m-d H:i:s');
    $item->revision = trim(cleanValue($params['revision']));
    // TODO setup to process ['restricted'] as ECB2-like multi-input
    // having value(s) like
    // user:* or group:* or perm:* or status:* or until:* or after:*
    $val = (int)$params['restricted'];
    if ($val > 0) {
        $item->restricted = (string)$val;
        $item->admin = 1;
        $item->search = 0;
    } else {
        $item->restricted = '';
        $item->search = !empty($params['search']);
    }
    $item->smarty = !empty($params['smarty']);
    $item->template_id = (int)$params['template_id']; // TODO handle 0/none
    $item->wysiwyg = !empty($params['wysiwyg']);
    if ($newitem) {
        // set a new item's position to last
        $sql = 'SELECT MAX(position) FROM '.CMS_DB_PREFIX.'module_userguide';
        $maxposition = $db->GetOne($sql);
        if ($maxposition) {
            $item->position = $maxposition + 1;
        } else {
            $item->position = 1;
        }
    }

    // display error(s) or save
    if ($errors) {
        //TODO SetError and redirect if submitted ?
        if (count($errors) > 1) {
            $this->ShowErrors('<ul><li>'.implode('</li><li>', $errors).'</li></ul>'); //TODO does ul/li tree still work? is it needed?
        } else {
            $this->ShowErrors(reset($errors));
        }
        // re-populate some $item values which might have been altered here from request params
        // arguably revert to $params['content'] possibly without surrounding <p></p>
        foreach (['name', 'author'] as $key) {
            $item->$key = $params[$key];
        }
    } elseif ($item->save()) {
        if ($newitem) {
            $query = new UserGuideQuery();
            $updated = $query->updatePositions(); //$updated unused
        }
        if (isset($params['submit'])) {
            $this->SetMessage($this->Lang('item_saved'));
            $this->RedirectToAdminTab('list');
        } else { // apply
            $this->ShowMessage($this->Lang('item_saved'));
        }
    } elseif (isset($params['submit'])) {
         //TODO ShowErrors and no redirect even if submitted ?
         $this->SetError($this->Lang('item_notsaved'));
         $this->RedirectToAdminTab('list');
    } else {
        $this->ShowErrors($this->Lang('item_notsaved2'));
        foreach (['name', 'author'] as $key) { //arguably, $params['content'] possibly without surrounding <p></p>
            $item->$key = $params[$key];
        }
    }
}

$tab = !empty($params['active_tab']) ? $params['active_tab'] : '';

$templates = [];
$ttype = CmsLayoutTemplateType::load('UserGuide::oneguide');
$data = CmsLayoutTemplate::load_all_by_type($ttype);
if ($data) {
    foreach ($data as $onetpl) {
        $tid = $onetpl->get_id();
        $templates[$tid] = $onetpl->get_name();
    }
    asort($templates, SORT_NATURAL);
}
$templates = [0 => lang('default')] + $templates;

$sheets = [];
$data = $db->GetArray('SELECT id,name,description FROM '.CMS_DB_PREFIX."layout_stylesheets WHERE name LIKE 'UserGuide_%' ORDER BY name");
if ($data) {
    $last = [];
    foreach ($data as $row) {
        if (stripos($row['name'], 'list') !== false || stripos($row['description'], 'list') !== false) {
            continue;
        } elseif (stripos($row['name'], 'guide') !== false || stripos($row['description'], 'guide') !== false) {
            unset($row['description']);
            $sheets[] = $row;
        } else {
            unset($row['description']);
            $last[] = $row;
        }
    }
    if ($last) {
        $sheets = array_merge($sheets, $last);
    }
}
if ($sheets) {
    if ($item->sheets) {
        $current = explode(',', $item->sheets);
        foreach ($sheets as &$row) {
            $row['checked'] = in_array($row['id'], $current);
        }
        unset($row);
        usort($sheets, function($a, $b) use($current) {
            $apos = array_search($a['id'], $current);
            if ($apos === false) return 1;
            $bpos = array_search($b['id'], $current);
            if ($bpos === false) return -1;
            return $apos - $bpos;
        });
    } else {
        foreach ($sheets as &$row) {
            $row['checked'] = false;
        }
        unset($row);
    }
} else {
    //TODO handle problem
}

$restrictions = [
    '' => $this->Lang('restrict_none'),
    '1' => $this->Lang('restrict_perm')
];
//TODO process item->restriction possibly representing contraint(s) like
//user:* or group:* or perm:* or status:* or until:* or after:*

if ($newitem && !(isset($params['submit']) || isset($params['apply']))) {
    $sql = 'SELECT first_name,last_name FROM '.CMS_DB_PREFIX.'users WHERE user_id=?';
    $row = $db->GetRow($sql, [$userid]);
    $item->author = trim($row['first_name'].' '.$row['last_name']);
    $item->smarty = $this->GetPreference('useSmarty', 0);
}
// the WYSIWYG will be hidden by js in the template, if it's not wanted
//element class will default to 'cms_textarea m1_content MicroTiny'
$input = create_textarea(true, $item->content, $id.'content', '', 'editarea', '', '', 50, 30);

$tpl = $smarty->CreateTemplate($this->GetTemplateResource('edit.tpl'), null, null, $smarty);
$tpl->assign('item', $item)
 ->assign('input_content', $input)
 ->assign('newitem', $newitem)
 ->assign('restrictions_list', $restrictions)
 ->assign('sheets_list', $sheets)
 ->assign('templates_list', $templates)
 ->assign('tab', $tab);
$tpl->display();
