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

$doer = new UserGuideXML($this);
if ($doer->export()) {
    $this->SetMessage($this->Lang('export_completed'));
} else {
    $this->SetError($this->Lang('err_export'));
}
$this->RedirectToAdminTab('list');
