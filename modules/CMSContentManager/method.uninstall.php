<?php
#CMSMS CMSContentManager module method: uninstall
#(c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file CMSContentManager.module.php applies to this file.

if( !isset($gCms) ) exit;

$this->RemovePreference();

// Remove user-specific filter-preferences
$me = $this->GetName();
$sql = 'DELETE FROM '.CMS_DB_PREFIX."userprefs WHERE preference LIKE {$me}_%";
$db->Execute($sql);
