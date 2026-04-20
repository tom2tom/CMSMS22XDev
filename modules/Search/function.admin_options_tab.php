<?php

$tpl->assign('oformstart',
			$this->CreateFormStart($id, 'defaultadmin', $returnid, 'post', '',
								   false, '', array('__activetab'=>'settings')));
$stops = $this->GetPreference('stopwords');
if (!$stops) {
	$stops = $this->DefaultStopWords();
}
$tpl->assign('current_stopwords', str_replace(["\r\n", "\n", "\r"], ['', '',''], $stops));
$tpl->assign('search_text', $this->GetPreference('searchtext'));
$tpl->assign('use_stemming',$this->GetPreference('usestemming', '0'));
$tpl->assign('save_phrases', $this->GetPreference('savephrases', '0'));
$tpl->assign('alpha_results', $this->GetPreference('alpharesults', '0'));
