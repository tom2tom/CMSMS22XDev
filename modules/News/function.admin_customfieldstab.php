<?php
#CMSMS News module function
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify News') ) return;  // better as a new 'Modify News Settings' permission

$entryarray = array();
$maxorder = $db->GetOne("SELECT MAX(item_order) as max_item_order FROM ".CMS_DB_PREFIX."module_news_fielddefs");

$admintheme = cms_utils::get_theme_object();
$query = "SELECT * FROM ".CMS_DB_PREFIX."module_news_fielddefs ORDER BY item_order";
$dbresult = $db->Execute($query);
if ($dbresult) {
  $rowclass = 'row1';
  while ($row = $dbresult->FetchRow()) {
    $onerow = new stdClass();

    $onerow->id = $row['id'];
    $onerow->name = $this->CreateLink($id, 'admin_editfielddef', $returnid, htmlspecialchars($row['name']), array('fdid'=>$row['id']));
    $onerow->type = $this->Lang($row['type']);
    $onerow->item_order = $row['item_order'];

    if ($onerow->item_order > 1) {
        $onerow->uplink = $this->CreateLink($id, 'admin_movefielddef', $returnid, $admintheme->DisplayImage('icons/system/arrow-u.gif', $this->Lang('up'), '', '', 'systemicon'), array('fdid'=>$row['id'], 'dir'=>'up'));
    }
    else {
        $onerow->uplink = '';
    }
    if ($maxorder > $onerow->item_order) {
        $onerow->downlink = $this->CreateLink($id, 'admin_movefielddef', $returnid, $admintheme->DisplayImage('icons/system/arrow-d.gif', $this->Lang('down'), '', '', 'systemicon'), array('fdid'=>$row['id'], 'dir'=>'down'));
    }
    else {
        $onerow->downlink = '';
    }

    $onerow->editlink = $this->CreateLink($id, 'admin_editfielddef', $returnid, $admintheme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'), '', '', 'systemicon'), array('fdid'=>$row['id']));

    $onerow->delete_url = $this->create_url($id, 'admin_deletefielddef', $returnid, array('fdid'=>$row['id']));

    $entryarray[] = $onerow;
    ($rowclass == "row1" ? $rowclass = "row2" : $rowclass = "row1");
  }
  $dbresult->Close();
}

$tpl->assign('fitems', $entryarray);
$tpl->assign('fitemcount', count($entryarray));
$tpl->assign('addurl', $this->create_url($id, 'admin_addfielddef'));
$tpl->assign('addlink', $this->CreateLink($id, 'admin_addfielddef', $returnid, $admintheme->DisplayImage('icons/system/newfolder.gif', $this->Lang('addfielddef'), '', '', 'systemicon'), array(), '', false, false, '') .' '. $this->CreateLink($id, 'admin_addfielddef', $returnid, $this->Lang('addfielddef'), array(), '', false, false, 'class="pageoptions"'));
$tpl->assign('fielddeftext', $this->Lang('name'));
$tpl->assign('typetext', $this->Lang('type'));
