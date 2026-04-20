<?php
#CMSMS News module action: defaulturl
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;

$this->DoAction('detail', $id, $params, $returnid);

?>
