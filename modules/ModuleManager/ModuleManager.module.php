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
  const _dflt_request_url = 'https://www.cmsmadesimple.org/ModuleRepository/request/v2'; // no trailing '/'

  public function GetName() { return 'ModuleManager'; }
  public function GetFriendlyName() { return $this->Lang('friendlyname'); }
  public function GetVersion() { return '2.1.11'; }
  public function GetHelp() { return $this->Lang('help'); }
  public function GetAuthor() { return 'Robert Campbell'; }
  public function GetAuthorEmail() { return ''; }
  public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
  public function IsPluginModule() { return FALSE; }
  public function HasAdmin() { return TRUE; }
  public function IsAdminOnly() { return TRUE; }
  public function GetAdminSection() { return 'siteadmin'; }
  public function GetAdminDescription() { return $this->Lang('admindescription'); }
  public function LazyLoadAdmin() { return TRUE; }
  public function MinimumCMSVersion() { return '2.2.4'; }
  public function InstallPostMessage() { return $this->Lang('postinstall'); }
  public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
  public function UninstallPreMessage() { return $this->Lang('really_uninstall'); }
  public function VisibleToAdminUser() { return ($this->CheckPermission('Modify Site Preferences') || $this->CheckPermission('Modify Modules')); }

  protected function _DisplayErrorPage($id, &$params, $returnid, $message='')
  {
    $smarty = cmsms()->GetSmarty();
    $modname = $this->GetName();
    $tpl = $smarty->createTemplate("module_file_tpl:$modname;error.tpl",null,null); //no parent
    $tpl->assign('title_error',$this->Lang('error'));
    $tpl->assign('message',$message);
    $tpl->assign('link_back',$this->CreateLink($id,'defaultadmin',$returnid,$this->Lang('back_to_module_manager')));
    $tpl->display();
  }

  public function Install()
  {
    $this->SetPreference('module_repository',self::_dflt_request_url);
  }

  public function Upgrade($oldversion, $newversion)
  {
    $this->SetPreference('module_repository',self::_dflt_request_url);
  }

  public function DoAction($action, $id, $params, $returnid=-1)
  {
    @set_time_limit(1999);
    return parent::DoAction($action,$id,$params,$returnid);
  }

} // end of class

#
# EOF
#
?>
