<?php

$tpl->assign('sformstart',$this->CreateFormStart($id,'defaultadmin',$returnid,'post','',false,'',
			array('__activetab'=>'statistics')));
if( $this->GetPreference('savephrases', 0) ) {
	$title = $this->Lang('phrase');
} else {
	$title = $this->Lang('word');
}
$tpl->assign('wordtext',$title);

$results = array();
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY `count` DESC';
$dbr = $db->SelectLimit($query,50,0);
if( $dbr ) {
	while( $row = $dbr->FetchRow() ) {
		$results[] = $row;
	}
	$dbr->Close();
}
if( $results ) {
	$tpl->assign('topwords',$results);
}
