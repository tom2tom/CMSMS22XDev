<?php
#CMSMS CMSContentManager module method: install
#(c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file CMSContentManager.module.php applies to this file.

if( !isset($gCms) ) exit;
if( !($gCms->test_state(CmsApp::STATE_INSTALL) || $this->CheckPermission('Modify Modules')) ) exit;

$this->SetPreference('locktimeout',60);
$this->SetPreference('lockrefresh',120);
// default page-edit tabs display order
$this->SetPreference('order_TAB_MAIN',2);
$this->SetPreference('order_TAB_NAV',4);
$this->SetPreference('order_TAB_LOGIC',6);
$this->SetPreference('order_TAB_OPTIONS',8);
$this->SetPreference('order_TAB_PERMS',10);

?>
