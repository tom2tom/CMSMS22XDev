<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/
use UserGuide\UserGuideXML;

if (!defined('CMS_VERSION')) exit;
if (!$this->CheckPermission('Modify Modules')) exit;

//$current_version = $oldversion;
if (version_compare($oldversion, '2.0' < 0)) {
    if (class_exists('SimpleXMLElement')) {
        $doer = new UserGuideXML($this);
        if (!$doer->import(__DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'UserGuide_Default.xml')) {
            audit('', $this->GetName(), 'Default content installation failed');
        }
    } else {
        audit('', $this->GetName(), 'Default content not installed: no SimpleXMLElement');
    }
}
