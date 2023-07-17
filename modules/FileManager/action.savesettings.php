<?php
#FileManager module action
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
if (!$this->CheckPermission("Modify Site Preferences") && !$this->AdvancedAccessAllowed()) exit;

$this->SetPreference('advancedmode',(int)$params['advancedmode']);
$this->SetPreference('showhiddenfiles',(int)$params['showhiddenfiles']);
$this->SetPreference('showthumbnails',(int)$params['showthumbnails']);
$this->SetPreference('create_thumbnails',(int)$params['create_thumbnails']);
$this->SetPreference("iconsize",$params["iconsize"]);
$this->SetPreference("permissionstyle",$params["permissionstyle"]);

$this->SetMessage($this->Lang('settingssaved'));
$this->SetCurrentTab('settings');
$this->Redirect($id,'admin_settings',$returnid);
?>
