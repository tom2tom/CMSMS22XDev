<?php
#-------------------------------------------------------------------------
# Module: CMSMailer
# (c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# a simple wrapper around cms_mailer class and PHPMailer
#
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

  public function GetName() { return 'CMSMailer'; }
  public function GetFriendlyName() { return $this->Lang('friendlyname'); }
  public function GetVersion() { return '6.2.15'; }
  public function MinimumCMSVersion() { return '1.99-alpha0'; }
  public function GetHelp() { return $this->Lang('help'); }
  public function GetAuthor() { return 'Robert Campbell'; }
  public function GetAuthorEmail() { return ''; }
  public function GetChangeLog() { return file_get_contents(__DIR__.'/changelog.inc'); }
  public function IsPluginModule() { return FALSE; }
  public function HasAdmin() { return FALSE; }
  public function GetAdminSection() { return 'extensions'; }
  public function GetAdminDescription() { return $this->Lang('moddescription'); }
  public function VisibleToAdminUser() { return FALSE; }
  public function InstallPostMessage() { return $this->Lang('postinstall'); }
  public function LazyLoadFrontend() { return TRUE; }
  public function LazyLoadAdmin() { return TRUE; }
  public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
} // end of class

?>
