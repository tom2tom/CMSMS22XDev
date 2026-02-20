<?php
/*
CMSMS CmsJobManager module action: test1
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CmsJobManager.module.php applies to this file.
*/
// This action is for use when developing, debugging async processing
// Normally there's no UI engagement with this

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

$newjob = new CmsJobManager\Test1Job();
$newjob->save();

$this->SetMessage('Job created');
$this->Redirect($id, 'defaultadmin');
