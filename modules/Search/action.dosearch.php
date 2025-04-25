<?php
if (!isset($gCms)) exit;

class SearchItemCollection
{
    public $_ary;
    public $maxweight;

    function __construct()
    {
        $this->_ary = array();
        $this->maxweight = 1;
    }

    function AddItem($title, $url, $txt, $weight = 1, $module = '', $modulerecord = 0)
    {
        if( $txt == '' ) $txt = $url;
        $exists = false;

        foreach ($this->_ary as $oneitem) {
            if ($url == $oneitem->url) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $newitem = new stdClass();
            $newitem->url = $url;
            $newitem->urltxt = search_CleanupText($txt);
            $newitem->title = $title;
            $newitem->intweight = intval($weight);
            if (intval($weight) > $this->maxweight) $this->maxweight = intval($weight);
            if (!empty($module) ) {
                $newitem->module = $module;
                if( intval($modulerecord) > 0 )	$newitem->modulerecord = $modulerecord;
            }
            $this->_ary[] = $newitem;
        }
    }

    function CalculateWeights()
    {
        foreach ($this->_ary as $oneitem) {
            $oneitem->weight = intval(($oneitem->intweight / $this->maxweight) * 100);
        }
    }

    function Sort()
    {
        $fn = function($a,$b) {
            if ($a->urltxt == $b->urltxt) return 0;
            return ($a->urltxt < $b->urltxt ? -1 : 1);
        };

        usort($this->_ary, $fn);
    }
} // end of class

////////////////////////////////////////////////////////////////////////

if( isset($params['resulttemplate']) ) {
    $template = trim($params['resulttemplate']);
}
else {
    $tpl = CmsLayoutTemplate::load_dflt_by_type('Search::searchresults');
    if( !is_object($tpl) ) {
        audit('',$this->GetName().':dosearch','No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}
$tpl = $smarty->CreateTemplate($this->GetTemplateResource($template),null,'Search',$smarty);

if( !empty($params['searchinput']) ) {
    // Fix to prevent XSS like behaviour. See: http://www.securityfocus.com/archive/1/455417/30/0/threaded
    //TODO robust sanitisation of user input c.f. news_ops::execSpecialize()
    $params['searchinput'] = cms_html_entity_decode($params['searchinput'],ENT_COMPAT,'UTF-8');
    $params['searchinput'] = strip_tags($params['searchinput']);
    CMSMS\HookManager::do_hook('Search::SearchInitiated', [trim($params['searchinput'])] );

    $searchstarttime = microtime(true);

    $tpl->assign('phrase', $params['searchinput']);
    $words = array_values($this->StemPhrase($params['searchinput']));
    $nb_words = count($words);
    $max_weight = 1;

    $searchphrase = '';
    if( $nb_words > 0 ) {
        //$searchphrase = implode(' OR ', array_fill(0, $nb_words, 'word = ?'));
        $ary = array();
        foreach( $words as $word ) {
            $word = trim($word);
            // $ary[] = "word = " . $db->qstr(htmlentities($word, ENT_COMPAT, 'UTF-8'));
            $ary[] = "word = " . $db->qstr($word);
        }
        $searchphrase = implode(' OR ', $ary); //TODO IN query
    }

    // Update the search words table
    if( $this->GetPreference('savephrases', 0) == 0 ) {
        foreach( $words as $word ) {
            $q = 'SELECT `count` FROM '.CMS_DB_PREFIX.'module_search_words WHERE word = ?';
            $tmp = $db->GetOne($q,array($word));
            if( $tmp ) {
                $q = 'UPDATE '.CMS_DB_PREFIX.'module_search_words SET `count`=`count`+1 WHERE word = ?';
                $db->Execute($q,array($word));
            }
            else {
                $q = 'INSERT INTO '.CMS_DB_PREFIX.'module_search_words (word,`count`) VALUES (?,1)';
                $db->Execute($q,array($word));
            }
        }
    }
    else {
        $term = trim($params['searchinput']);
        $q = 'SELECT `count` FROM '.CMS_DB_PREFIX.'module_search_words WHERE word = ?';
        $tmp = $db->GetOne($q,array($term));
        if( $tmp ) {
            $q = 'UPDATE '.CMS_DB_PREFIX.'module_search_words SET `count`=`count`+1 WHERE word = ?';
            $db->Execute($q,array($term));
        }
        else {
            $q = 'INSERT INTO '.CMS_DB_PREFIX.'module_search_words (word,`count`) VALUES (?,1)';
            $db->Execute($q,array($term));
        }
    }

//  $val = 100 * 100 * 100 * 100 * 25;
    $longnow = $db->DBTimeStamp(time());
    $query = 'SELECT DISTINCT i.module_name, i.content_id, i.extra_attr, COUNT(*) AS nb, SUM(idx.`count`) AS total_weight FROM '.
    CMS_DB_PREFIX.'module_search_items i INNER JOIN '.
    CMS_DB_PREFIX.'module_search_index idx ON idx.item_id = i.id WHERE ('.$searchphrase.") AND (COALESCE(i.expires,$longnow)>=$longnow)";
    if( isset( $params['modules'] ) ) {
        $modules = explode(",",$params['modules']);
        for( $i = 0; $i < count($modules); $i++ ) {
            $modules[$i] = $db->qstr($modules[$i]);
        }
        $query .= ' AND i.module_name IN ('.implode(',',$modules).')';
    }
    $query .= ' GROUP BY i.module_name, i.content_id, i.extra_attr';
    if( empty($params['use_or']) ) {
        //This makes it an AND query TODO borks if $nb_words > 1
        $query .= " HAVING nb >= $nb_words";
    }
    $query .= ' ORDER BY nb DESC, total_weight DESC';

    $col = new SearchItemCollection();
    $result = $db->Execute($query);
    if ($result) {
        $hm = $gCms->GetHierarchyManager();
        while (!$result->EOF) {
            //Handle internal (templates, content, etc) first...
            if ($result->fields['module_name'] == $this->GetName()) {
                if ($result->fields['extra_attr'] == 'content') {
                    //Content is easy... just grab it out of hierarchy manager and toss the url in
                    $node = $hm->sureGetNodeById($result->fields['content_id']);
                    if (isset($node)) {
                        $content = $node->GetContent();
                        if ($content && $content->Active()) $col->AddItem($content->Name(), $content->GetURL(), $content->Name(), $result->fields['total_weight'], $result->fields['extra_attr'], $result->fields['content_id']);
                    }
                }
            }
            else {
                $thepageid = $this->GetPreference('resultpage',-1);
                if( $thepageid == -1 ) $thepageid = $returnid;
                if( isset($params['detailpage']) ) {
                    $tmppageid = '';
                    $manager = $gCms->GetHierarchyManager();
                    $node = $manager->sureGetNodeByAlias($params['detailpage']);
                    if (isset($node)) {
                        $tmppageid = $node->getID();
                    }
                    else {
                        $node = $manager->sureGetNodeById($params['detailpage']);
                        if (isset($node)) $tmppageid= $params['detailpage'];
                    }
                    if( $tmppageid ) $thepageid = $tmppageid;
                }
                if( $thepageid == -1 ) $thepageid = $returnid;

                //Start looking at modules...
                $modulename = $result->fields['module_name'];
                $moduleobj = $this->GetModuleInstance($modulename);
                if ($moduleobj) {
                    if (method_exists($moduleobj, 'SearchResultWithParams' )) {
                        // search through the params, for all the passthru ones
                        // and get only the ones matching this module name
                        $parms = array();
                        foreach( $params as $key => $value ) {
                            $str = 'passthru_'.$modulename.'_';
                            if( preg_match( "/$str/", $key ) > 0 ) {
                                $name = substr($key,strlen($str));
                                if( $name != '' ) $parms[$name] = $value;
                            }
                        }
                        $searchresult = $moduleobj->SearchResultWithParams($thepageid, $result->fields['content_id'],
                                                                            $result->fields['extra_attr'], $parms);
                        if ($searchresult && count($searchresult) == 3) {
                            $col->AddItem($searchresult[0], $searchresult[2], $searchresult[1],
                                          $result->fields['total_weight'], $modulename, $result->fields['content_id']);
                        }
                    }
                    else if (method_exists($moduleobj, 'SearchResult')) {
                        $searchresult = $moduleobj->SearchResult($thepageid, $result->fields['content_id'], $result->fields['extra_attr']);
                        if ($searchresult && count($searchresult) == 3) {
                            $col->AddItem($searchresult[0], $searchresult[2], $searchresult[1],
                                          $result->fields['total_weight'], $modulename, $result->fields['content_id']);
                        }
                    }
                }
            }

            $result->MoveNext();
        }
        $result->Close();
    }

    $col->CalculateWeights();
    if ($this->GetPreference('alpharesults', 0)) { $col->Sort(); }

    // now we're gonna do some post processing on the results
    // and replace the search terms with <span class="searchhilite">term</span>

    $results = $col->_ary;
    $newresults = array();
    foreach( $results as $result ) {
        $title = cms_htmlentities($result->title);
        $txt = cms_htmlentities($result->urltxt);
        foreach( $words as $word ) {
            $word = preg_quote($word);
            $title = preg_replace('/\b('.$word.')\b/i', '<span class="searchhilite">$1</span>', $title);
            $txt = preg_replace('/\b('.$word.')\b/i', '<span class="searchhilite">$1</span>', $txt);
        }
        $result->title = $title;
        $result->urltxt = $txt;
        $newresults[] = $result;
    }
    $col->_ary = $newresults;

    \CMSMS\HookManager::do_hook( 'Search::SearchCompleted', [ &$params['searchinput'], &$col->_ary ] );

    $tpl->assign('searchwords', $words);
    $tpl->assign('results', $col->_ary);
    $tpl->assign('itemcount', count($col->_ary));

    $searchendtime = microtime(true);
    $tpl->assign('timetook', ($searchendtime - $searchstarttime));
}
else {
    $tpl->assign('phrase', '');
    $tpl->assign('results', 0);
    $tpl->assign('itemcount', 0);
    $tpl->assign('timetook', 0);
}

$tpl->assign('use_or_text', $this->Lang('use_or'));
$tpl->assign('searchresultsfor', $this->Lang('searchresultsfor'));
$tpl->assign('noresultsfound', $this->Lang('noresultsfound'));
$tpl->assign('timetaken', $this->Lang('timetaken'));
$tpl->display();
