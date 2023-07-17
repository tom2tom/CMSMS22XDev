<?php
#FileManager module upgrade script
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
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

if (!function_exists("cmsms")) exit;

$current_version = $oldversion;
$this->SetPreference("uploadboxes","5");
switch($current_version) {
	case "0.1.0":
	case "0.1.1":
	case "0.1.2":
	case "0.1.3":
	case "0.1.4": $this->Install(true);
}

if( version_compare($oldversion,'1.3.1') < 0 ) {
    $this->CreateEvent('OnFileUploaded');
}
if( version_compare($oldversion,'1.6.2') < 0 ) {
    $this->CreateEvent('OnFileDeleted');
}


// do this stuff for all upgrades.
$this->SetPreference('advancedmode',0);
$this->RemovePermission('Use Filemanager');
$this->RegisterModulePlugin(true);
$this->RemovePreference('uploadboxes');
