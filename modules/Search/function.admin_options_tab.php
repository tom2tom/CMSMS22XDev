<?php

$tpl->assign('formstart2',$this->CreateFormStart($id, 'defaultadmin',$returnid,'post','',false,'',
			  array('active_tab'=>'options')));
$tpl->assign('formend2',$this->CreateFormEnd());
$tpl->assign('reindex',$this->CreateInputSubmit($id, 'reindex', $this->Lang('reindexallcontent')));
$tpl->assign('prompt_stopwords',$this->Lang('stopwords'));
$tpl->assign('input_stopwords',
				$this->CreateTextArea(false, $id, str_replace(array("\r", "\n"), '',
									  $this->GetPreference('stopwords', $this->DefaultStopWords())),
									  'stopwords', '', '', '', '', '50', '6'));
$tpl->assign('prompt_resetstopwords',$this->Lang('prompt_resetstopwords'));
$tpl->assign('input_resetstopwords',$this->CreateInputSubmit($id, 'resettodefault', $this->Lang('input_resetstopwords')));

$tpl->assign('prompt_stemming',$this->Lang('usestemming'));
$tpl->assign('input_stemming',
				$this->CreateInputCheckbox($id, 'usestemming', 'true',
										   $this->GetPreference('usestemming', 'false')));

$tpl->assign('prompt_searchtext',$this->Lang('prompt_searchtext'));
$tpl->assign('input_searchtext',
				$this->CreateInputText($id,'searchtext',
									   $this->GetPreference('searchtext','')));

$tpl->assign('prompt_savephrases',$this->Lang('prompt_savephrases'));
$tpl->assign('input_savephrases',
				$this->CreateInputCheckbox($id,'savephrases','true',
										   $this->GetPreference('savephrases','false')));

$tpl->assign('prompt_alpharesults',$this->Lang('prompt_alpharesults'));
$tpl->assign('input_alpharesults',
				$this->CreateInputCheckbox($id,'alpharesults','true',
										   $this->GetPreference('alpharesults','false')));

//$contentops = $gCms->GetContentOperations();
$tpl->assign('prompt_resultpage',$this->Lang('prompt_resultpage'));
/*
$tpl->assign('input_resultpage',
				$contentops->CreateHierarchyDropdown(0,$this->GetPreference('resultpage',-1),$id.'resultpage',true));
*/

$tpl->assign('submit',$this->CreateInputSubmit($id, 'submit', $this->Lang('submit')));
