<?php
if( !isset($gCms) ) exit;

$wordcount = ( !empty($params['count']) ) ? (int)$params['count'] : 500;
$pageid = ( !empty($params['pageid']) ) ? (int)$params['pageid'] : $returnid;

$pref = CMS_DB_PREFIX;
$query = <<<EOS
SELECT b.word FROM {$pref}module_search_index b
CROSS JOIN {$pref}module_search_items a
WHERE b.item_id = a.id
AND a.content_id = $pageid
AND a.module_name = 'search'
AND a.extra_attr = 'content'
ORDER BY b.`count` DESC
EOS;

$dbr = $db->SelectLimit($query, $wordcount, 0);
if( $dbr ) {
    $wordlist = array();
    while( ($row = $dbr->FetchRow()) ) {
        $wordlist[] = $row['word'];
    }
    $dbr->Close();
    echo implode(',', $wordlist);
}
else {
    echo '';
}
