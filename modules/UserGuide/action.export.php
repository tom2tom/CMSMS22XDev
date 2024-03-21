<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

use UserGuide\UserGuideXML;

if (!isset($gCms)) {
    exit;
}
if (!$this->CheckPermission(UserGuide::MANAGE_PERM)) {
    $this->Redirect($id, 'defaultadmin', $returnid);
}

$done = false;
if (class_exists('SimpleXMLElement')) {
    $doer = new UserGuideXML($this);
    $done = $doer->export();
}
// tarball export goes here somewhere ... must be in a separate request
if ($done) {
    $this->SetMessage($this->Lang('export_completed'));
} else {
    $this->SetError($this->Lang('err_export'));
}
$this->RedirectToAdminTab('list');
