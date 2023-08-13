<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: ModuleManager
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# An addon module for CMS Made Simple to allow browsing remotely stored
# modules, viewing information about them, and downloading or upgrading
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE
if (!isset($gCms)) exit;

define('MINIMUM_REPOSITORY_VERSION','1.5');

class ModuleManager extends CMSModule
{
  const _dflt_request_url = 'https://www.cmsmadesimple.org/ModuleRepository/request/v2/';

  public function GetName() { return get_class($this); }
  public function GetFriendlyName() { return $this->Lang('friendlyname'); }
  public function GetVersion() { return '2.1.9'; }
  public function GetHelp() { return $this->Lang('help'); }
  public function GetAuthor() { return 'Robert Campbell'; }
  public function GetAuthorEmail() { return ''; }
  public function GetChangeLog() { return file_get_contents(__DIR__.'/changelog.inc'); }
  public function IsPluginModule() { return FALSE; }
  public function HasAdmin() { return TRUE; }
  public function IsAdminOnly() { return TRUE; }
  public function GetAdminSection() { return 'siteadmin'; }
  public function GetAdminDescription() { return $this->Lang('admindescription'); }
  public function LazyLoadAdmin() { return TRUE; }
  public function MinimumCMSVersion() { return '2.2.3'; }
  public function InstallPostMessage() { return $this->Lang('postinstall'); }
  public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
  public function UninstallPreMessage() { return $this->Lang('really_uninstall'); }
  public function VisibleToAdminUser() { return ($this->CheckPermission('Modify Site Preferences') || $this->CheckPermission('Modify Modules')); }

  protected function _DisplayErrorPage($id, &$params, $returnid, $message='')
  {
    $this->smarty->assign('title_error', $this->Lang('error'));
    $this->smarty->assign('message', $message);
    $this->smarty->assign('link_back',$this->CreateLink($id,'defaultadmin',$returnid, $this->Lang('back_to_module_manager')));

    // Display the populated template
    echo $this->ProcessTemplate('error.tpl');
  }

  public function Install()
  {
    $this->SetPreference('module_repository',ModuleManager::_dflt_request_url);
  }

  public function Upgrade($oldversion, $newversion)
  {
    $this->SetPreference('module_repository',ModuleManager::_dflt_request_url);
  }

  public function DoAction($action, $id, $params, $returnid=-1)
  {
    $smarty = cmsms()->GetSmarty();
    $smarty->assign($this->GetName(), $this);
    $smarty->assign('mod', $this);
    @set_time_limit(9999);
    parent::DoAction( $action, $id, $params, $returnid );
  }

} // end of class

#
# EOF
#
?>
