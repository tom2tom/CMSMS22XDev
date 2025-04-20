<?php
if( !isset($gCms) ) exit;

// CreateFormStart sets up a proper form tag that will cause the submit to
// return control to this module for processing.
$tpl->assign('startform', $this->CreateFormStart($id, 'updateoptions', $returnid));
$tpl->assign('endform', $this->CreateFormEnd());

$tpl->assign('title_formsubmit_emailaddress',$this->Lang('formsubmit_emailaddress'));
$tpl->assign('formsubmit_emailaddress',$this->GetPreference('formsubmit_emailaddress',''));

$tpl->assign('title_email_subject',$this->Lang('email_subject'));
$tpl->assign('email_subject',$this->GetPreference('email_subject',''));

$tpl->assign('title_email_template',$this->Lang('email_template'));
$tpl->assign('email_template',$this->GetTemplate('email_template'));

$categorylist = array();
$query = "SELECT * FROM ".CMS_DB_PREFIX."module_news_categories ORDER BY hierarchy";
$dbresult = $db->Execute($query);
if ($dbresult) {
    while ($row = $dbresult->FetchRow()) {
        $categorylist[$row['long_name']] = $row['news_category_id'];
    }
    $dbresult->Close();
}

$tpl->assign('title_default_category', $this->Lang('default_category'));
$tpl->assign('categorylist',array_flip($categorylist));
$tpl->assign('default_category',$this->GetPreference('default_category'));

$tpl->assign('title_allowed_upload_types',$this->Lang('allowed_upload_types'));
$tpl->assign('allowed_upload_types',$this->GetPreference('allowed_upload_types'));

$tpl->assign('title_auto_create_thumbnails',$this->Lang('auto_create_thumbnails'));

$tpl->assign('title_hide_summary_field',$this->Lang('hide_summary_field'));
$tpl->assign('hide_summary_field',$this->GetPreference('hide_summary_field',0));

$tpl->assign('title_allow_summary_wysiwyg',$this->Lang('allow_summary_wysiwyg'));
$tpl->assign('allow_summary_wysiwyg',$this->GetPreference('allow_summary_wysiwyg',1));

$tpl->assign('title_expiry_interval',$this->Lang('expiry_interval'));
$tpl->assign('expiry_interval',$this->GetPreference('expiry_interval',180));

$tpl->assign('title_expired_searchable',$this->Lang('expired_searchable'));
$tpl->assign('expired_searchable',$this->GetPreference('expired_searchable'));

$tpl->assign('title_expired_viewable',$this->Lang('expired_viewable'));
$tpl->assign('expired_viewable',$this->GetPreference('expired_viewable',1));
$tpl->assign('info_expired_viewable',$this->Lang('info_expired_viewable'));

$tpl->assign('title_fesubmit_status',$this->Lang('fesubmit_status'));
$statusdropdown = array();
$statusdropdown[$this->Lang('draft')] = 'draft';
$statusdropdown[$this->Lang('published')] = 'published';
$tpl->assign('statuses',array_flip($statusdropdown));
$tpl->assign('fesubmit_status',$this->GetPreference('fesubmit_status'));
$tpl->assign('input_fesubmit_status',
		$this->CreateInputDropdown($id,'fesubmit_status',$statusdropdown,-1,$this->GetPreference('fesubmit_status','draft')));

$tpl->assign('title_fesubmit_redirect',$this->Lang('fesubmit_redirect'));
$contentops = $gCms->GetContentOperations();
$tpl->assign('input_fesubmit_redirect',
		$contentops->CreateHierarchyDropdown(0,$this->GetPreference('fesubmit_redirect',-1),$id.'fesubmit_redirect',true));

$tpl->assign('title_detail_returnid',$this->Lang('title_detail_returnid'));
$tpl->assign('input_detail_returnid',
		$contentops->CreateHierarchyDropdown(0,$this->GetPreference('detail_returnid',-1),$id.'detail_returnid',true));
$tpl->assign('info_detail_returnid',$this->Lang('info_detail_returnid'));

$tpl->assign('title_submission_settings',$this->Lang('title_submission_settings'));
$tpl->assign('title_fesubmit_settings',$this->Lang('title_fesubmit_settings'));
$tpl->assign('title_notification_settings',$this->Lang('title_notification_settings'));
$tpl->assign('title_detail_settings',$this->Lang('title_detail_settings'));
$tpl->assign('allow_fesubmit',$this->GetPreference('allow_fesubmit',0));
$tpl->assign('alert_drafts',$this->GetPreference('alert_drafts',0));
