<?php
if (!isset($gCms)) exit;
if (!$this->CheckPermission('Manage Search')) exit;

if (isset($params['reindex'])) {
    try {
        $this->Reindex();
        $this->ShowMessage($this->Lang('reindexcomplete'));
    }
    catch( \Exception $e ) {
        debug_display($e);
    }
}
elseif (isset($params['clearwordcount'])) {
    $query = 'TRUNCATE '.CMS_DB_PREFIX.'module_search_words';
    $db->Execute($query);
}
elseif (isset($params['exportcsv']) ) {
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY `count` DESC';
    $data = $db->GetArray($query);
    if( $data ) {
        $fwhen = date('Y-m-d_H-i-s', time());
        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename=CMSMS_Search_Export_'.$fwhen.'.csv');
        while(@ob_end_clean());

        $output = '';
        for( $i = 0, $n = count($data); $i < $n; $i++ ) {
            $output .= "\"{$data[$i]['word']}\",{$data[$i]['count']}\n";
        }
        echo $output;
        exit;
    }
}
elseif (isset($params['resettodefault'])) {
    $this->SetPreference('stopwords', $this->DefaultStopWords());
}
elseif (isset($params['submit'])) {
    $this->SetPreference('stopwords', $params['stopwords']); //TODO sanitize string
    $this->SetPreference('searchtext', $params['searchtext']); // ditto
    $this->SetPreference('resultpage', (int)$params['resultpage']);

    $newval = (!empty($params['savephrases'])) ? 1 : 0;
    $this->SetPreference('savephrases', $newval);

    $newval = (!empty($params['alpharesults'])) ? 1 : 0;
    $this->SetPreference('alpharesults', $newval);

    $curval = (int)$this->GetPreference('usestemming', 0);
    $newval = (!empty($params['usestemming'])) ? 1 : 0;
    $this->ShowMessage($this->Lang('settingssaved'));
    if ($newval != $curval) {
        $this->SetPreference('usestemming', $newval);
        $this->Reindex();
        $this->ShowMessage($this->Lang('reindexcomplete'));
    }
}

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;defaultadmin.tpl", null, $modname, $smarty);

//setup statistics tab content
require __DIR__.DIRECTORY_SEPARATOR.'function.admin_statistics_tab.php';
//setup settings tab content
require __DIR__.DIRECTORY_SEPARATOR.'function.admin_options_tab.php';

if( empty($seetab) ) {
    $seetab = (!empty($params['__activetab'])) ? $params['__activetab'] : '';
}
$tpl->assign('tab', $seetab);
$tpl->assign('formend', $this->CreateFormEnd());

$tpl->display();
