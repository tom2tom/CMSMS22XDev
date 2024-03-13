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

$errors = [];

$xmlfield = $id . 'xmlfile';
if (empty($_FILES[$xmlfield]['name']) || $_FILES[$xmlfield]['type'] != 'text/xml') {
    $errors[] = $this->Lang('err_nofile');
}

$doer = new UserGuideXML($this);
if (!$doer->import($_FILES[$xmlfield]['tmp_name'])) {
    $errors[] = $this->Lang('err_import');
}

if ($errors) {
    $this->SetError($errors);
} else {
    $this->SetMessage($this->Lang('import_completed'));
}

$this->RedirectToAdminTab('list');
