<?php
#CMSMS News module action: admin_deletefielddef
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

$fdid = '';
if (isset($params['fdid'])) $fdid = $params['fdid'];

//Get the field details
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE id = ?';
$row = $db->GetRow($query, array($fdid));
if (!$row) {
    $this->SetError(lang('missingparams'));
    $this->RedirectToAdminTab('customfields','','admin_settings');
}

//Now remove the field
$query = "DELETE FROM ".CMS_DB_PREFIX."module_news_fielddefs WHERE id = ?";
$db->Execute($query, array($fdid));

//And remove it from any entries
$query = "DELETE FROM ".CMS_DB_PREFIX."module_news_fieldvals WHERE fielddef_id = ?";
$db->Execute($query, array($fdid));

$db->Execute('UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order - 1) WHERE item_order > ?', array($row['item_order']));

$params = array('tab_message'=> 'fielddefdeleted', '__activetab' => 'customfields');
// put mention into the admin log
audit($fdid,$this->GetName().' field definition',"Deleted: {$row['name']}");
$this->Setmessage($this->Lang('fielddefdeleted'));
$this->RedirectToAdminTab('customfields','','admin_settings');
