<?php
#-------------------------------------------------------------------------
# Module: AdminSearch - A CMSMS addon module to provide admin side search capbilities.
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
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
if( !isset($gCms) ) exit;

final class AdminSearch extends CMSModule
{
  public function GetFriendlyName() { return $this->Lang('friendlyname'); }
  public function GetVersion() { return '1.0.8'; }
  public function MinimumCMSVersion() { return '2.2.15'; }
  public function LazyLoadAdmin() { return TRUE; }
  public function LazyLoadFrontend() { return TRUE; }
  public function IsPluginModule() { return FALSE; }
  public function GetAuthor() { return 'Robert Campbell'; }
  public function GetAuthorEmail() { return ''; }
  public function HasAdmin() { return true; }
  public function GetAdminSection() { return 'extensions'; }
  public function GetHelp() { return $this->Lang('help'); }
  public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
  public function GetAdminDescription() { return $this->Lang('moddescription'); }

  public function VisibleToAdminUser()
  {
    return $this->can_search();
  }

  protected function can_search()
  {
    return $this->CheckPermission('Use Admin Search');
  }

  public function InstallPostMessage()
  {
    return $this->Lang('postinstall');
  }

  public function UninstallPostMessage()
  {
    return $this->Lang('postuninstall');
  }

  public function DoAction($name,$id,$params,$returnid='')
  {
    $smarty = cmsms()->GetSmarty();
    $smarty->assign('mod',$this); //redundant? see CMSModule::DoActionBase()
    return parent::DoAction($name,$id,$params,$returnid);
  }

  protected function SetupHeadtext()
  {
    CMSMS\HookManager::add_hook('admin_add_headtext',function() {
      $baseurl = $this->GetModuleURLPath();
      return "<link rel=\"stylesheet\" href=\"$baseurl/lib/admin_search.css\">\n";
    });
  }

  public function HasCapability($capability,$params=array())
  {
    return ($capability == CmsCoreCapabilities::ADMINSEARCH);
  }

  public function get_adminsearch_slaves()
  {
    $files = glob(__DIR__.'/lib/class.AdminSearch*slave.php');
    if( $files ) {
      $output = array();
      foreach( $files as $onefile ) {
        $parts = explode('.',basename($onefile));
        $classname = implode('.',array_slice($parts,1,count($parts)-2));
        if( $classname == 'AdminSearch_slave' ) continue;
        $output[] = $classname;
      }
      return $output;
    }
  }
} // class
