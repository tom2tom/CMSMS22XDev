<?php
if( !isset($gCms) ) exit;

if( version_compare($oldversion,'1.1.12') < 0 ) {
	// default page-edit tabs display order
	$this->SetPreference('order_TAB_MAIN', 2);
	$this->SetPreference('order_TAB_NAV', 4);
	$this->SetPreference('order_TAB_LOGIC', 6);
	$this->SetPreference('order_TAB_OPTIONS', 8);
	$this->SetPreference('order_TAB_PERMS', 10);
}
