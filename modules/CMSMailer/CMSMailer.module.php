<?php
#-------------------------------------------------------------------------
# Module: CMSMailer - a simple wrapper around cms_mailer class and PHPMailer
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005 by Ted Kulp (wishy@cmsmadesimple.org)
# Visit our homepage at: http://www.cmsmadesimple.org
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
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#-------------------------------------------------------------------------

class CMSMailer extends CMSModule
{
  protected $the_mailer;

  public function __construct()
  {
    parent::__construct();
    $this->the_mailer = new cms_mailer(FALSE);
  }

  #[\ReturnTypeWillChange]
  public function __call($method,$args)
  {
    if( method_exists($this->the_mailer,$method) ) {
      return call_user_func_array(array($this->the_mailer,$method),$args);
    }
    // cms_mailer class methods accessible via this module using an alternate name
    // these too were deprecated in May 2013, when the cms_mailer class was released
    $aliases = array(
    'GetHost' => 'GetSMTPHost',
    'SetHost' => 'SetSMTPHost',
    'GetPort' => 'GetSMTPPort',
    'SetPort' => 'SetSMTPPort',
    'GetTimeout' => 'GetSMTPTimeout',
    'SetTimeout' => 'SetSMTPTimeout',
    'GetUsername' => 'GetSMTPUsername',
    'SetUsername' => 'SetSMTPUsername',
    'GetPassword' => 'GetSMTPPassword',
    'SetPassword' => 'SetSMTPPassword',
    'GetSecure' => 'GetSMTPSecure',
    'SetSecure' => 'SetSMTPSecure'
    );
    if( isset($aliases[$method]) ) {
        return call_user_func_array(array($this->the_mailer,$aliases[$method]),$args);
    }
    if( is_callable('parent::__call') ) {
      return parent::__call($method,$args);
    }
    throw new CmsException('Call to invalid method '.$method.' on '.get_class($this->the_mailer).' object');
  }

  function GetName() { return 'CMSMailer'; }
  function GetFriendlyName() { return $this->Lang('friendlyname'); }
  function GetVersion() { return '6.2.15'; }
  function MinimumCMSVersion() { return '1.99-alpha0'; }
  function GetHelp() { return $this->Lang('help'); }
  function GetAuthor() { return 'Calguy1000'; }
  function GetAuthorEmail() { return ''; }
  function GetChangeLog() { return file_get_contents(__DIR__.'/changelog.inc'); }
  function IsPluginModule() { return FALSE; }
  function HasAdmin() { return FALSE; }
  function GetAdminSection() { return 'extensions'; }
  function GetAdminDescription() { return $this->Lang('moddescription'); }
  function VisibleToAdminUser() { return FALSE; }
  function InstallPostMessage() { return $this->Lang('postinstall'); }
  function LazyLoadFrontend() { return TRUE; }
  function LazyLoadAdmin() { return TRUE; }
  function UninstallPostMessage() { return $this->Lang('postuninstall'); }
} // end of class

?>
