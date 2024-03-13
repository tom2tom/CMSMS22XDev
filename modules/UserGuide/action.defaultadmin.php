<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

use UserGuide\UserGuideQuery;

if (!isset($gCms)) {
    exit;
}
$pmod = $this->CheckPermission(UserGuide::MANAGE_PERM);
$pset = $pmod || $this->CheckPermission(UserGuide::SETTINGS_PERM);
$tab = (!empty($params['active_tab'])) ? $params['active_tab'] : '';

$tpl = $smarty->CreateTemplate($this->GetTemplateResource('defaultadmin.tpl'), null, null, $smarty);
$tpl->assign('pmod', $pmod)
 ->assign('pset', $pset)
 ->assign('tab', $tab);

// All current guides
$query = new UserGuideQuery();
$guides = $query->GetMatches(); //sorted by position-field value
if ($guides) {
    // TODO setup to process item->restricted as [flattened, encoded|crypted]
    foreach ($guides as $key => $item) {
        if ($item->restricted) {
            switch ($item->restricted) {
                default:
                    if (!isset($prest)) {
                        $prest = $this->CheckPermission(UserGuide::RESTRICT_PERM);
                    }
                    if (!$prest) {
                        unset($guides[$key]);
                    }
            }
        }
    }
}
$tpl->assign('guides', $guides);

if ($pmod) {
    $url = $this->create_url($id, 'reorder_guides', $returnid, ['idlist'=>'XXX']);
    $tpl->assign('reorder_url', str_replace('&amp;', '&', $url));
    $tpl->assign('have_xml', class_exists('SimpleXMLElement', false));
    $tpl->assign('iconsbase', $this->GetModuleURLPath().'/images');
    // Imports
    $modops = ModuleOperations::get_instance();
    $modules = $modops->GetInstalledModules(true);
    $tpl->assign('have_UserGuide2', in_array('UserGuide2', $modules));
    $tpl->assign('have_UsersGuide', in_array('UsersGuide', $modules));
}

if ($pset) {
    $tpl->assign('adminSection', $this->GetPreference('adminSection', 'content'));
    // Depending on the type of guidance provided, the module label might
    // be positioned in any part of the menu. In some of the sections, it
    // really should have a compatible custom label (per customLabel setting)
    $tpl->assign('sectionChoices', [
     'main' => lang('main'),
     'content' => lang('content'),
     'layout' => lang('layout'),
     'usersgroups' => lang('usersgroups'),
     'extensions' => lang('extensions'),
     'siteadmin' => lang('admin'),
     'myprefs' => lang('myprefs')
    ]);
    $tpl->assign('customCSS', $this->GetPreference('customCSS', false));
    $tpl->assign('customLabel', $this->GetPreference('customLabel'));
    $tpl->assign('filesFolder', $this->GetPreference('filesFolder'));
    $tpl->assign('useSmarty', $this->GetPreference('useSmarty', false));

    $gdata = [];
    $ldata = [];
    $data = $db->GetArray('SELECT id,name,description FROM '.CMS_DB_PREFIX."layout_stylesheets WHERE name LIKE 'UserGuide_%' ORDER BY name");
    if ($data) {
        $last = [];
        foreach ($data as $row) {
            if (stripos($row['name'], 'list') !== false || stripos($row['description'], 'list') !== false) {
                $ldata[$row['id']] = $row['name'];
            } elseif (stripos($row['name'], 'guide') !== false || stripos($row['description'], 'guide') !== false) {
                $gdata[$row['id']] = $row['name'];
            } else {
                $last[$row['id']] = $row['name'];
            }
        }
        if ($last) {
            $gdata = array_merge($gdata, $last);
            $ldata = array_merge($ldata, $last);
        }
    }
    $gdata = [0 => lang('none')] + $gdata;
    $tpl->assign('guideChoices', $gdata);
    $def =  $this->GetPreference('guideStyles');
    $tpl->assign('guideStyles', (int)array_search($def, $gdata));

    $ldata = [0 => lang('none')] + $ldata;
    $tpl->assign('listChoices', $ldata);
    $def =  $this->GetPreference('listStyles');
    $tpl->assign('listStyles', (int)array_search($def, $ldata));

/*  TODO access-restriction-type processing
    any no. of individual N's
    $this->SetPreference('restrictionN', 'user:USERID'); particular non-super user
    $this->SetPreference('restrictionN', 'group:Name'); member of some non-super group
    $this->SetPreference('restrictionN', 'perm:Name'); some perm
    $this->SetPreference('restrictionN', 'status:Desc'); some site parameter
    $this->SetPreference('restrictionN', 'until:When'); finish timestamp
    $this->SetPreference('restrictionN', 'after:When'); defer timestamp
*/
}

$tpl->display();

?>
