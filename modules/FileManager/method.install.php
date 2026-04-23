<?php
#FileManager module installation script
#(c) 2006-8 Morten Poulsen <morten@poulsen.org>
#(c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#
#You should have received a copy of the GNU General Public License
#along with this program; if not, read the license online at:
#https://www.gnu.org/licenses/#LicenseURLs

if (!isset($gCms)) exit;
if (!($gCms->test_state(CmsApp::STATE_INSTALL) || $this->CheckPermission('Modify Modules'))) exit;

$this->CreatePermission('Use FileManager Advanced',$this->Lang("permissionadvanced"));

$this->SetPreference('advancedmode',0);
$this->SetPreference('iconsize',24); // aka medium
$this->SetPreference('showhiddenfiles',0);
$this->SetPreference('showthumbnails',1);
$this->SetPreference('create_thumbnails',1);
$this->SetPreference('permissionstyle','xxx');

$this->CreateEvent('OnFileUploaded');
$this->CreateEvent('OnFileDeleted');
$this->RegisterModulePlugin(true);

?>
