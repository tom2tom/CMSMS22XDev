<?php
#CMSMS News module action: admin_movefielddef
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

$order = 1;
$fdid = $params['fdid'];

#Grab necessary info for fixing the item_order
$order = $db->GetOne("SELECT item_order FROM ".CMS_DB_PREFIX."module_news_fielddefs WHERE id = ?", array($fdid));
$time = $db->DBTimeStamp(time());

if ($params['dir'] == "down")
  {
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order - 1), modified_date = '.$time.' WHERE item_order = ?';
    $db->Execute($query, array($order + 1));

    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order + 1), modified_date = '.$time.' WHERE id = ?';
    $db->Execute($query, array($fdid));

  }
else if ($params['dir'] == "up")
  {
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order + 1), modified_date = '.$time.' WHERE item_order = ?';
    $db->Execute($query, array($order - 1));
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_fielddefs SET item_order = (item_order - 1), modified_date = '.$time.' WHERE id = ?';
    $db->Execute($query, array($fdid));
  }

$this->RedirectToAdminTab('customfields','','admin_settings');
?>
