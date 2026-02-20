<?php
/*
CMSMS CmsJobManager module action: test2
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CmsJobManager.module.php applies to this file.
*/
// This action is for use when developing, debugging async processing
// Normally there's no UI engagement with this

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

$newjob = new CmsJobManager\Test1Cron();
$newjob->save();

$this->SetMessage('Cron Job created');
$this->Redirect($id, 'defaultadmin');
