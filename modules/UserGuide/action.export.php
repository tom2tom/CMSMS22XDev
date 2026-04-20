<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

use UserGuide\UserGuideIO;
use UserGuide\UserGuideXML;

if (!isset($gCms)) {
    exit;
}
if (!$this->CheckPermission(UserGuide::MANAGE_PERM)) {
    $this->Redirect($id, 'defaultadmin', $returnid);
}
if (empty($params['type'])) {
    $this->SetError($this->Lang('err_export')); // TODO better feedback
    $this->RedirectToAdminTab('list');
}

if ($params['type'] == 'xml' && class_exists('SimpleXMLElement')) {
    $doer = new UserGuideXML($this);
} elseif ($params['type'] == 'gzip' && class_exists('PharData') && function_exists('readgzfile')) {
    $doer = new UserGuideIO($this);
} else {
    $this->SetError($this->Lang('err_export'));
    $this->RedirectToAdminTab('list');
}
if (!$doer->export()) { //normally this does not return
    $this->SetError($this->Lang('err_export')); // TODO better feedback
}
$this->RedirectToAdminTab('list');
