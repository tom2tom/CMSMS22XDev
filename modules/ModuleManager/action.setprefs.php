<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module ModuleManager action: setprefs
# (c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#-------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
#END_LICENSE

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences' ) ) return;

$this->SetCurrentTab('prefs');

if( !empty($config['developer_mode']) ) {
    if( !empty($params['reseturl']) ) {
        $this->SetPreference('module_repository',ModuleManager::_dflt_request_url);
        $this->SetMessage($this->Lang('msg_urlreset'));
        $this->RedirectToAdminTab();
    }
    if( !empty($params['url']) ) {
        $tmp = ltrim($params['url']);
        $this->SetPreference('module_repository',rtrim($tmp,"/ \t"));
    }
    $this->SetPreference('allowuninstall',(int)get_parameter_value($params,'allowuninstall'));
    $this->SetPreference('disable_caching',(int)get_parameter_value($params,'disable_caching'));
}
else {
    $this->SetPreference('allowuninstall',0);
}

if( isset($params['dl_chunksize']) ) {
    $size = (int)get_parameter_value($params,'dl_chunksize');
    $this->SetPreference('dl_chunksize',max(1, $size));
}
$this->SetPreference('latestdepends',(int)get_parameter_value($params,'latestdepends'));

$this->SetMessage($this->Lang('msg_prefssaved'));
$this->RedirectToAdminTab();
?>
