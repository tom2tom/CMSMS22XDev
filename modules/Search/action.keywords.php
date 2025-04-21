<?php
if( !isset($gCms) ) exit;

$wordcount = 500;
if( isset($params['count']) ) $wordcount = (int)$params['count'];

$pageid = $returnid;
if( isset($params['pageid']) ) $pageid = (int)$params['pageid'];

$query = 'SELECT b.word FROM '.
CMS_DB_PREFIX.'module_search_items a INNER JOIN '.
CMS_DB_PREFIX.'module_search_index b ON
a.id = b.item_id
WHERE a.content_id = \''.$pageid.'\'
AND a.module_name = \'search\'
AND a.extra_attr = \'content\'
ORDER BY b.`count` DESC';

$wordlist = array();
$dbr = $db->SelectLimit( $query, $wordcount, 0 );
if( $dbr ) {
    while( ($row = $dbr->FetchRow() ) ) {
        $wordlist[] = $row['word'];
    }
    $dbr ->Close();
}
echo implode(',',$wordlist);
