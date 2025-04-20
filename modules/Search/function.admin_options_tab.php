<?php

$tpl->assign('oformstart', $this->CreateFormStart($id, 'defaultadmin', $returnid, 'post', '', false, '',
			array('__activetab'=>'settings')));
$tpl->assign('reindex', $this->CreateInputSubmit($id, 'reindex', $this->Lang('reindexallcontent')));
$tpl->assign('prompt_stopwords', $this->Lang('stopwords'));
$tpl->assign('input_stopwords',
			$this->CreateTextArea(false, $id, str_replace(array("\r", "\n"), '',
								  $this->GetPreference('stopwords', $this->DefaultStopWords())),
								  'stopwords', '', '', '', '', '50', '6'));
$tpl->assign('prompt_resetstopwords', $this->Lang('prompt_resetstopwords'));
$tpl->assign('input_resetstopwords', $this->CreateInputSubmit($id, 'resettodefault', $this->Lang('input_resetstopwords')));

$tpl->assign('prompt_stemming', $this->Lang('usestemming'));
$tpl->assign('input_stemming',
			$this->CreateInputCheckbox($id, 'usestemming', '1',
									   $this->GetPreference('usestemming', '0'),
									   'id="chkstem"'));

$tpl->assign('prompt_searchtext', $this->Lang('prompt_searchtext'));
$tpl->assign('input_searchtext',
			$this->CreateInputText($id, 'searchtext',
								   $this->GetPreference('searchtext'),
								   '15', '64'));

$tpl->assign('prompt_savephrases', $this->Lang('prompt_savephrases'));
$tpl->assign('input_savephrases',
			$this->CreateInputCheckbox($id, 'savephrases', '1',
									   $this->GetPreference('savephrases', '0'),
									  'id="chkphrased"'));

$tpl->assign('prompt_alpharesults', $this->Lang('prompt_alpharesults'));
$tpl->assign('input_alpharesults',
			$this->CreateInputCheckbox($id, 'alpharesults', '1',
									   $this->GetPreference('alpharesults', '0'),
									   'id="chkalpha"'));

$tpl->assign('prompt_resultpage', $this->Lang('prompt_resultpage'));
/*
$contentops = $gCms->GetContentOperations();
$tpl->assign('input_resultpage',
			$contentops->CreateHierarchyDropdown(0,
								$this->GetPreference('resultpage', -1),
								$id.'resultpage', true));
*/
$tpl->assign('submit', $this->CreateInputSubmit($id, 'submit', $this->Lang('submit')));
