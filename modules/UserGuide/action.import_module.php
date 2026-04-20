<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/
// import directly from UserGuide2 module or legacy UsersGuide module

use UserGuide\UserGuideImportGuide2;
use UserGuide\UserGuideImportOldGuides;

if (!isset($gCms)) {
    exit;
}
if (!$this->CheckPermission(UserGuide::MANAGE_PERM)) {
    $this->Redirect($id, 'defaultadmin', $returnid);
}

$modname = $params['source'];
$ops = ModuleOperations::get_instance();
$srcmod = $ops->get_module_instance($modname);
if (!is_object($srcmod)) {
    $this->SetError(lang('errormodulenotfound'));
    $this->RedirectToAdminTab('list');
}

$errors = [];
if ($modname == 'UserGuide2') {
    $handler = new UserGuideImportGuide2($this);
    $errors = $handler->migrate();
} elseif ($modname == 'UsersGuide') {
    $handler = new UserGuideImportOldGuides($this);
    $errors = $handler->migrate();
}

if ($errors) {
    $this->SetError($errors);
} else {
    $this->SetMessage($this->Lang('import_completed'));
}
$this->RedirectToAdminTab('list');
