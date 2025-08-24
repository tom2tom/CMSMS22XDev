<?php
#CMSMS News module action: deletearticle
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Delete News')) {
    echo $this->ShowErrors($this->Lang('needpermission', array('Modify News')));
    return;
}

$articleid = '';
if (isset($params['articleid'])) {
    $articleid = $params['articleid'];
}

news_admin_ops::delete_article($articleid);

$params = array('tab_message'=> 'articledeleted', '__activetab' => 'articles');
$this->Redirect($id, 'defaultadmin', $returnid, $params);
?>
