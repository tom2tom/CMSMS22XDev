<?php
if(!isset($gCms) ) exit;
if( !$this->CheckPermission('Modify News') ) return;

include(__DIR__.'/function.admin_articlestab.php');

?>
