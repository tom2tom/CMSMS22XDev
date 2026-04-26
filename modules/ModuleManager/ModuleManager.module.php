<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: ModuleManager
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# An addon module for CMS Made Simple to allow browsing remotely stored
# modules, viewing information about them, and downloading or upgrading
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
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
#END_LICENSE

if (!isset($gCms)) exit;

define('MINIMUM_REPOSITORY_VERSION','1.5');

class ModuleManager extends CMSModule
{
  const _dflt_request_url = 'https://www.cmsmadesimple.org/ModuleRepository/request/v2'; // no trailing '/'

  public function GetAdminDescription() { return $this->Lang('admindescription'); }
  public function GetAdminSection() { return 'siteadmin'; }
  public function GetAuthor() { return 'Robert Campbell'; }
  public function GetAuthorEmail() { return ''; }
  public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
  public function GetFriendlyName() { return $this->Lang('friendlyname'); }
  public function GetHelp() { return $this->Lang('help'); }
  public function GetName() { return 'ModuleManager'; }
  public function GetVersion() { return '2.1.12'; }
  public function HasAdmin() { return TRUE; }
  public function InstallPostMessage() { return $this->Lang('postinstall'); }
  public function IsAdminOnly() { return TRUE; }
  public function LazyLoadAdmin() { return TRUE; }
  public function MinimumCMSVersion() { return '2.2.23F2'; } // for related-class autoloading
  public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
  public function UninstallPreMessage() { return $this->Lang('really_uninstall'); }
  public function VisibleToAdminUser() { return ($this->CheckPermission('Modify Site Preferences') || $this->CheckPermission('Modify Modules')); }

  public function GetAdminMenuItems()
  {
    if ($this->VisibleToAdminUser()) {
      $obj = CmsAdminMenuItem::from_module($this);
      $obj->section = 'siteadmin';
      $obj->title = lang('modules');
      $obj->description = $this->Lang('admindescription');
      return [$obj];
    }
    return [];
  }

  public function HasCapability($capability, $params=[])
  {
    switch ($capability) {
      case CmsCoreCapabilities::TASKS:
        return TRUE;
      default:
        return FALSE;
    }
  }

  public function get_tasks()
  {
    $fp = cms_join_path(__DIR__,'lib','class.ClearCacheJob.php');
    if (is_file($fp)) {
      if (func_num_args() > 0 && func_get_arg(0)) { // want filepath(s)
        return [$fp];
      } else {
        require_once $fp;
        return [new ModuleManager\ClearCacheJob()];
      }
    }
    return [];
  }

  public function DoAction($action, $id, $params, $returnid=-1)
  {
    @set_time_limit(1999);
    return parent::DoAction($action,$id,$params,$returnid);
  }

  protected function _DisplayErrorPage($id, $params=[], $returnid='', $message='')
  {
    $smarty = cmsms()->GetSmarty();
    $modname = $this->GetName();
    $tpl = $smarty->createTemplate("module_file_tpl:$modname;error.tpl",null,null); //no parent
    $tpl->assign('title_error',$this->Lang('error'));
    $tpl->assign('message',$message);
    $tpl->assign('link_back',$this->CreateLink($id,'defaultadmin',$returnid,$this->Lang('back_to_module_manager'),$params));
    $tpl->display();
  }
} // class

?>
