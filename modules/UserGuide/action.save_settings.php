<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

use UserGuide\UserGuideUtils;

if (!isset($gCms)) exit;
if (!($this->CheckPermission(UserGuide::MANAGE_PERM) ||
      $this->CheckPermission(UserGuide::SETTINGS_PERM))) {
    $this->Redirect($id, 'defaultadmin', $returnid);
}
if (isset($params['cancel'])) {
    $this->SetMessage($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab('settings');
}

$errors = [];
// Save module settings
$val = $this->GetPreference('adminSection');
$valn = (!empty($params['adminSection'])) ? trim(cleanValue($params['adminSection'])) : 'content';
$dump = $valn != $val;
if ($dump) {
    $this->SetPreference('adminSection', $valn);
}
$this->SetPreference('customCSS', isset($params['customCSS']) ? (bool)$params['customCSS'] : false);
$val = $this->GetPreference('customLabel');
$valn = (!empty($params['customLabel'])) ? trim(cleanValue($params['customLabel'])) : '';
if ($valn != $val) {
    $dump = true;
    $this->SetPreference('customLabel', $valn);
}
$val = $this->GetPreference('filesFolder');
$valn = isset($params['filesFolder']) ? trim(cleanValue($params['filesFolder'])) : '';
if ($valn != $val) {
    $bp = $config['image_uploads_path'];
    $diro = cms_join_path($bp, strtr($val, '\\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR));
    if (is_dir($diro)) {
        $dirn = cms_join_path($bp, strtr($valn, '\\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR));
        if (!file_exists($dirn)) {
            rename($diro, $dirn);
            $this->SetPreference('filesFolder', $valn);
        } elseif (is_dir($dirn)) {
            $res = UserGuideUtils::recursiveCopy($diro, $dirn);
            if (!$res) {
                recursive_delete($diro);
                $this->SetPreference('filesFolder', $valn);
            } else {
                $errors = array_merge($errors, $res);
            }
        } else {
            $errors[] = "Name conflict: cannot replace file $dirn";
        }
    }
}
$val = isset($params['guideStyles']) ? (int)$params['guideStyles'] : '';
if (is_numeric($val) && $val > 0) {
    $obj = CmsLayoutStylesheet::load($val);
    $val = ($obj) ? $obj->get_name() : '';
} else {
    $val = '';
}
$this->SetPreference('guideStyles', $val);
$val = isset($params['listStyles']) ? (int)$params['listStyles'] : '';
if (is_numeric($val) && $val > 0) {
    $obj = CmsLayoutStylesheet::load($val);
    $val = ($obj) ? $obj->get_name() : '';
} else {
    $val = '';
}
$this->SetPreference('listStyles', $val);

$this->SetPreference('useSmarty', isset($params['useSmarty']) ? (bool)$params['useSmarty'] : false);

/*TODO access-restriction-type processing
any no. of individual N's
$this->SetPreference('restrictionN', 'user:USERID'); // particular non-super user (Admin(1) members have all permissions)
$this->SetPreference('restrictionN', 'group:Name'); // member of some non-Admin group (Admin(1) members have all permissions)
$this->SetPreference('restrictionN', 'perm:Name'); // some perm like Modify Site Preferences
$this->SetPreference('restrictionN', 'status:Desc'); // some site parameter
$this->SetPreference('restrictionN', 'until:When'); // some timeout threshold
$this->SetPreference('restrictionN', 'after:When'); // some defer threshold
*/

// Show saved parameters in debug mode
debug_display($params);

// Put mention into the admin log
audit('', 'UserGuide module', 'Settings saved');

if ($dump) {
    $gCms->clear_cached_files(); // TODO admin-cache only
}

if (!$errors) {
    $this->SetMessage($this->Lang('settings_saved'));
} else {
    $this->SetError($errors);
}
$this->RedirectToAdminTab('settings');
