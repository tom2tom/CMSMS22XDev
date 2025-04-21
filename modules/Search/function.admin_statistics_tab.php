<?php

$tpl->assign('sformstart',$this->CreateFormStart($id,'defaultadmin',$returnid,'post','',false,'',
			array('__activetab'=>'statistics')));
if( $this->GetPreference('savephrases', 0) ) {
	$title = $this->Lang('phrase');
} else {
	$title = $this->Lang('word');
}
$tpl->assign('wordtext',$title);
$tpl->assign('counttext',$this->Lang('count'));
$tpl->assign('exportcsv',
		$this->CreateInputSubmit($id,'exportcsv',$this->Lang('export_to_csv'),
			'data-ui-icon="ui-icon-arrowreturnthick-1-s"'));
$tpl->assign('clearwordcount',
		$this->CreateInputSubmit($id,'clearwordcount',$this->Lang('clear'),
			'data-ui-icon="ui-icon-minusthick"','',$this->Lang('confirm_clearstats')));

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
