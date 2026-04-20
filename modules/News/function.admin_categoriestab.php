<?php
#CMSMS News module function
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify News') ) return; // better as a new 'Modify News Settings' permission

// Put together a list of current categories
$entryarray = array();

$query = "SELECT * FROM ".CMS_DB_PREFIX."module_news_categories ORDER BY hierarchy";
$dbresult = $db->Execute($query);
if( $dbresult ) {
  $admintheme = cms_utils::get_theme_object();
  $rowclass = 'row1';
  while ($row = $dbresult->FetchRow()) {
    $onerow = new stdClass();
    $depth = count(preg_split('/\./', $row['hierarchy']));
    $onerow->id = $row['news_category_id'];
    $onerow->depth = $depth - 1;
    $onerow->edit_url = $this->create_url($id, 'editcategory', $returnid, array('catid'=>$row['news_category_id']));
    $onerow->name = $row['news_category_name'];
    $onerow->editlink = $this->CreateLink($id, 'editcategory', $returnid, $admintheme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'),'','','systemicon'), array('catid'=>$row['news_category_id']));
    $onerow->delete_url = $this->create_url($id, 'deletecategory', $returnid, array('catid'=>$row['news_category_id']));
    $onerow->deletelink = $this->CreateLink($id, 'deletecategory', $returnid, $admintheme->DisplayImage('icons/system/delete.gif', $this->Lang('delete'),'','','systemicon'), array('catid'=>$row['news_category_id']), $this->Lang('areyousure'));
    $onerow->rowclass = $rowclass;
    $entryarray[] = $onerow;
    ($rowclass == "row1" ? $rowclass = "row2" : $rowclass = "row1");
  }
  $dbresult->Close();
}

$tpl->assign('citems', $entryarray);
$tpl->assign('citemcount', count($entryarray));
$tpl->assign('categorytext', $this->Lang('name'));
