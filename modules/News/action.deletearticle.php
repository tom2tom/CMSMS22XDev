<?php
#CMSMS News module action: deletearticle
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Delete News')) {
    $this->SetError($this->Lang('needpermission', 'Modify News'));
    $this->Redirect($id, 'defaultadmin', $returnid, array('__activetab' => 'articles'));
}

$articleid = '';
if (isset($params['articleid'])) {
    $articleid = $params['articleid'];
}

news_admin_ops::delete_article($articleid);

$params = array('tab_message'=> 'articledeleted', '__activetab' => 'articles');
$this->Redirect($id, 'defaultadmin', $returnid, $params);
?>
