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
$key = $id . 'imported';
if (empty($_FILES[$key]['name']) ||
    !$_FILES[$key]['tmp_name'] ||
    !$_FILES[$key]['type'] ||
    $_FILES[$key]['size'] == 0 ||
    $_FILES[$key]['error'] != 0) {
        $errors[] = $this->Lang('err_nofile');
} else {
    switch($_FILES[$key]['type']) {
        // tarball processing goes here ...
        case 'text/xml':
            //TODO confirm actual content
            $doer = new UserGuideXML($this);
            if (!$doer->import($_FILES[$key]['tmp_name'])) {
                $errors[] = $this->Lang('err_import');
            }
            break;
        default:
            $errors[] = lang('error_uploadproblem');
    }
}

if ($errors) {
    $this->SetError($errors);
} else {
    $this->SetMessage($this->Lang('import_completed'));
}
$this->RedirectToAdminTab('list');
