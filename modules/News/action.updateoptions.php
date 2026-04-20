<?php
#CMSMS News module action: updateoptions
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if( !isset($gCms) ) exit;
if( !$this->CheckPermission( 'Modify Site Preferences' ) ) return;  //or a new Modify News Settings permission

if( isset($params['cancel']) ) {
    $this->RedirectToAdminTab('settings','','admin_settings');
}

$this->SetPreference('default_category', (int)$params['default_category']);
$this->SetPreference('email_subject',trim($params['email_subject']));
$this->SetTemplate('email_template',$params['email_template']);
$this->SetPreference('allowed_upload_types', trim($params['allowed_upload_types']));
$this->SetPreference('hide_summary_field', (!empty($params['hide_summary_field']) ? 1 : 0));
$this->SetPreference('allow_summary_wysiwyg', (!empty($params['allow_summary_wysiwyg']) ? 1 : 0));
$this->SetPreference('expired_searchable', (!empty($params['expired_searchable']) ? 1 : 0));
$this->SetPreference('expired_viewable', (!empty($params['expired_viewable']) ? 1 : 0));
$this->SetPreference('expiry_interval', (int)$params['expiry_interval']);
$this->SetPreference('fesubmit_emailaddress', trim($params['fesubmit_emailaddress']));
$this->SetPreference('fesubmit_status', trim($params['fesubmit_status']));
$this->SetPreference('fesubmit_redirect', (int)$params['fesubmit_redirect']);
$this->SetPreference('detail_returnid',(int)$params['detail_returnid']);
$this->SetPreference('allow_fesubmit',(int)$params['allow_fesubmit']);
$this->SetPreference('alert_drafts',(int)$params['alert_drafts']);

$this->CreateStaticRoutes();
$this->SetMessage($this->Lang('optionsupdated'));
$this->RedirectToAdminTab('settings','','admin_settings');
?>
