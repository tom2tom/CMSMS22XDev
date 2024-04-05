<?php
if( !isset($gCms) ) exit;

if( version_compare($oldversion,'1.1.12') < 0 ) {
	// default page-edit tabs display order
	$this->SetPreference('order_TAB_MAIN', 10);
	$this->SetPreference('order_TAB_NAV', 15);
	$this->SetPreference('order_TAB_LOGIC', 20);
	$this->SetPreference('order_TAB_OPTIONS', 25);
	$this->SetPreference('order_TAB_PERMS', 30);
}
