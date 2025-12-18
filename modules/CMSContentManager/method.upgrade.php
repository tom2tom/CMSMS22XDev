<?php
#CMSMS CMSContentManager module method: upgrade
#(c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file CMSContentManager.module.php applies to this file.

if( !isset($gCms) ) exit;

if( version_compare($oldversion,'1.1.12') < 0 ) {
	// default page-edit tabs display order
	$this->SetPreference('order_TAB_MAIN', 2);
	$this->SetPreference('order_TAB_NAV', 4);
	$this->SetPreference('order_TAB_LOGIC', 6);
	$this->SetPreference('order_TAB_OPTIONS', 8);
	$this->SetPreference('order_TAB_PERMS', 10);
}

if( version_compare($oldversion,'1.1.14') < 0 ) {
	// remove (all users') redundant preferences
	$modname = $this->GetName();
	$sql = 'DELETE FROM '.CMS_DB_PREFIX."userprefs WHERE preference = {$modname}_pagelimit";
	$db->Execute($sql);
	$sql = 'DELETE FROM '.CMS_DB_PREFIX."userprefs WHERE preference = {$modname}_userfilter";
	$db->Execute($sql);
}
