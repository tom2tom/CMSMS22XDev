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
#along with this program. If not, read the licence online at
#https://www.gnu.org/licences/old-licences/gpl-2.0.html

if (!function_exists('cmsms')) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

$current = (int)$this->GetPreference('advancedmode',0);
$now = (!empty($params['advancedmode'])) ? 1 : 0;
if ($now != $current) {
    $this->SetPreference('advancedmode',$now);
    if ($now == 0) {
        $pref = substr($config['uploads_path'],strlen(CMS_ROOT_PATH)); // or mebbe DIRECTORY_SEPARATOR.$config['uploads_dir']
        $list = UserOperations::get_instance()->GetList();
        foreach ($list as $uid => $uname) {
            $val = cms_userprefs::get_for_user($uid,'filemanager_cwd');
            if (!startswith($val,$pref)) { // directory now inaccessible
                cms_userprefs::remove_for_user($uid,'filemanager_cwd',true);
            }
        }
    }
}
$this->SetPreference('showhiddenfiles',((!empty($params['showhiddenfiles']))?1:0));
$this->SetPreference('showthumbnails',((!empty($params['showthumbnails']))?1:0));
$this->SetPreference('create_thumbnails',((!empty($params['create_thumbnails']))?1:0));
$this->SetPreference('iconsize',(int)$params['iconsize']); //16, 24 or 32 (px)
$this->SetPreference('permissionstyle',$params['permissionstyle']); //string 'xxx' etc

$this->SetMessage($this->Lang('settingssaved'));
$this->Redirect($id,'admin_settings',$returnid);
?>
