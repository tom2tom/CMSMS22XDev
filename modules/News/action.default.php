<?php
if( !isset($gCms) ) exit;

if( isset($params['summarytemplate']) ) {
    $template = trim($params['summarytemplate']);
}
else {
    $tpl = CmsLayoutTemplate::load_dflt_by_type('News::summary');
    if( !is_object($tpl) ) {
        audit('', $this->GetName().':default', 'No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}

$cache_id = '|ns'.md5(serialize($params));
$tpl_ob = $smarty->CreateTemplate($this->GetTemplateResource($template), $cache_id, null, $smarty);
if( !$tpl_ob->IsCached() ) {
//$tpl_ob = $smarty->CreateTemplate($this->GetTemplateResource($template), null, null, $smarty);
    $detailpage = 0;
    $tmp = (int)$this->GetPreference('detail_returnid', -1);
    if( isset($params['detailpage']) ) {
        $manager = $gCms->GetHierarchyManager();
        $node = $manager->sureGetNodeByAlias(trim($params['detailpage']));
        if( $node ) {
            $params['detailpage'] = $detailpage = $node->getID();
        }
        else {
            $node = $manager->sureGetNodeById($params['detailpage']);
            if( $node ) {
                $params['detailpage'] = $detailpage = (int)$params['detailpage'];
            }
            else if( $tmp > 0 ) {
                $params['detailpage'] = $detailpage = $tmp;
            }
            else {
                unset($params['detailpage']);
            }
        }
    }
    else if( $tmp > 0 ) {
        $params['detailpage'] = $detailpage = $tmp;
    }
    if( !empty($params['browsecat']) ) {
        $this->DoAction('browsecat', $id, $params, $returnid);
        return;
    }

    $entryarray = array();
    // SQL_CALC_FOUND_ROWS is deprecated. Instead execute 2 related queries
    $query1 = "
SELECT SQL_CALC_FOUND_ROWS
 mn.*,
 mnc.news_category_name,
 mnc.long_name,
 u.username,
 u.first_name,
 u.last_name
FROM " . CMS_DB_PREFIX . "module_news mn
LEFT OUTER JOIN " . CMS_DB_PREFIX . "module_news_categories mnc
ON mnc.news_category_id = mn.news_category_id
LEFT OUTER JOIN " . CMS_DB_PREFIX . "users u
ON u.user_id = mn.author_id
WHERE status = 'published' AND
";

    if( isset($params['idlist']) ) {
        $tmp = cleanValue(trim($params['idlist']));
        $tmp = explode(',', $tmp);
        $idlist = [];
        for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
            $val = (int)trim($tmp[$i]);
            if( $val > 0 && !in_array($val, $idlist) ) $idlist[] = $val;
        }
        if( $idlist ) $query1 .= ' (mn.news_id IN ('.implode(', ', $idlist).')) AND ';
    }

    if( isset($params['category_id']) ) {
        $query1 .= " (mnc.news_category_id = ".(int)$params['category_id'].") AND ";
    }
    else if( isset($params['category']) && $params['category'] !== '' ) { // in theory, could be '0'
        $category = cleanValue(trim($params['category']));
        $categories = explode(',', $category);
        $query1 .= " (";
        $count = 0;
        foreach( $categories as $onecat ) {
            if( $count > 0 ) $query1 .= ' OR ';
            $onecat = trim($onecat);
            if( strpos($onecat, '?') !== FALSE || strpos($onecat, '*') !== FALSE ) {
                $tmp = $db->qstr(trim(str_replace(['*', '?', "'"], ['%', '_', '_'], $onecat))); //also replace included single-quotes
                $query1 .= "mnc.long_name LIKE {$tmp}"; //table collation is *_ci so this will be caseless and support UTF-8
            }
            else {
                $tmp = $db->qstr(trim(str_replace("'", '_', $onecat)));
                $query1 .= "mnc.news_category_name = {$tmp}";
            }
            $count++;
        }
        $query1 .= ") AND ";
    }

    if( !empty($params['showall']) ) {
        // show everything irrespective of end date
        $query1 .= 'IF(start_time IS NULL, news_date <= NOW(), start_time <= NOW()) ';
    }
    else {
        // we're concerned about start time, end time, and news_date
        if( isset($params['showarchive']) ) {
            // show only expired entries.
            $query1 .= 'IF(end_time IS NULL, 0, end_time < NOW()) ';
        }
        else {
            $query1 .= 'IF(start_time IS NULL OR end_time IS NULL, news_date <= NOW(), NOW() BETWEEN start_time AND end_time) ';
        }
    }

    $sortrandom = false;
    $sortby = trim(get_parameter_value($params, 'sortby', 'news_date'));
    switch( $sortby ) {
    case 'news_category':
        if( isset($params['sortasc']) && (strtolower($params['sortasc']) == 'true') ) {
            $query1 .= "ORDER BY mnc.long_name ASC, mn.news_date ";
        }
        else {
            $query1 .= "ORDER BY mnc.long_name DESC, mn.news_date ";
        }
        break;

    case 'random':
        $query1 .= "ORDER BY RAND() ";
        $sortrandom = true;
        break;

    case 'summary':
    case 'news_data':
    case 'news_category':
    case 'news_title':
    case 'end_time':
    case 'start_time':
    case 'news_extra':
        $query1 .= "ORDER BY mn.$sortby ";
        break;

    default:
        $query1 .= "ORDER BY mn.news_date ";
        break;
    }

    if( !$sortrandom ) {
        if( !isset($params['sortasc']) || !cms_to_bool($params['sortasc']) ) {
            $query1 .= "DESC";
        }
    }

    $pagelimit = 1000; // foolish default - 10 or 20 would be sensible
    if( isset($params['pagelimit']) ) {
        $pagelimit = (int)$params['pagelimit'];
    }
    else if( isset($params['number']) ) {
        $pagelimit = (int)$params['number'];
    }
    $pagelimit = max(1, min(1000, $pagelimit)); // maximum of 1000 entries per page

    // Get the number of rows (so we can determine the numer of pages)
    $pagecount = -1;
    $startelement = 0;
    $pagenumber = 1;

    if( isset($params['pagenumber']) && $params['pagenumber'] != '' ) {
        // if given a page number, determine a start element
        $pagenumber = (int)$params['pagenumber'];
        $startelement = ($pagenumber-1) * $pagelimit;
    }
    if( isset($params['start']) ) {
        // given a start element, determine a page number
        $startelement = $startelement + (int)$params['start'];
    }

    // SQL_CALC_FOUND_ROWS is deprecated. Instead execute the query with LIMIT, and then again with COUNT(*) for the FOUND_ROWS()
    $dbresult = $db->SelectLimit($query1, $pagelimit, $startelement);
    $count = (int) $db->GetOne('SELECT FOUND_ROWS()');

    // Determine number of pages
    if( $count > 0) {
        if( isset($params['start']) ) {
            $count -= (int)$params['start'];
            if( $count < 0 ) $count = 0;
        }
        $pagecount = (int)($count / $pagelimit);
        if( ($count % $pagelimit) != 0 ) $pagecount++;
    }
    else {
        $pagecount = 1;
    }

    //if the URLs generated below include parameter $targetcontentonly = true,
    // generated content populates {News tag} content,
    // or if = false, that generated content populates {content} on relevant page

    // Assign some pagination variables to Smarty
    if( $pagenumber == 1 ) {
        $tpl_ob->assign('prevpage', $this->Lang('prevpage'));
        $tpl_ob->assign('firstpage', $this->Lang('firstpage'));
    }
    else {
        $params['pagenumber'] = $pagenumber-1;
        $tpl_ob->assign('prevpage', $this->CreateFrontendLink($id, $returnid, 'default', $this->Lang('prevpage'), $params));
        $tpl_ob->assign('prevurl', $this->create_url($id, 'default', $returnid, $params, true)); //inline, !targetcontentonly
        $params['pagenumber'] = 1;
        $tpl_ob->assign('firstpage', $this->CreateFrontendLink($id, $returnid, 'default', $this->Lang('firstpage'), $params));
        $tpl_ob->assign('firsturl', $this->create_url($id, 'default', $returnid, $params, true)); //inline, !targetcontentonly
    }

    if( $pagenumber >= $pagecount ) {
        $tpl_ob->assign('nextpage', $this->Lang('nextpage'));
        $tpl_ob->assign('lastpage', $this->Lang('lastpage'));
    }
    else {
        $params['pagenumber'] = $pagenumber+1;
        $tpl_ob->assign('nextpage', $this->CreateFrontendLink($id, $returnid, 'default', $this->Lang('nextpage'), $params));
        $tpl_ob->assign('nexturl', $this->create_url($id, 'default', $returnid, $params, true)); //inline, !targetcontentonly
        $params['pagenumber'] = $pagecount;
        $tpl_ob->assign('lastpage', $this->CreateFrontendLink($id, $returnid, 'default', $this->Lang('lastpage'), $params));
        $tpl_ob->assign('lasturl', $this->create_url($id, 'default', $returnid, $params, true)); // inline, !targetcontentonly
    }
    $tpl_ob->assign('pagenumber', $pagenumber);
    $tpl_ob->assign('pagecount', $pagecount);
    $tpl_ob->assign('oftext', $this->Lang('prompt_of'));
    $tpl_ob->assign('pagetext', $this->Lang('prompt_page'));

    //we will substitute $detailpage into URLs cuz 'returnid' is used to select the page to be displayed
    $displayid = $detailpage ?: $returnid;

    if( $dbresult ) {
        // build a list of news id's so we can preload stuff from other tables.
        $result_ids = array();
        while( $dbresult && !$dbresult->EOF ) {
            $result_ids[] = $dbresult->fields['news_id'];
            $dbresult->MoveNext();
        }
        $dbresult->MoveFirst();
        news_ops::preloadFieldData($result_ids);

        while( !$dbresult->EOF ) {
            $row = $dbresult->fields;
            $onerow = new stdClass();

            $onerow->author_id = $row['author_id'];
            if( $onerow->author_id > 0 ) {
                $onerow->author = $row['username'];
                $onerow->authorname = trim($row['first_name'].' '.$row['last_name']);
            }
            else if( $onerow->author_id == 0 ) {
                $onerow->author = $this->Lang('anonymous');
                $onerow->authorname = $this->Lang('unknown');
            }
            else {
                if( !isset($feu) ) {
                    $feu = $this->GetModuleInstance('FrontEndUsers');
                    if( !$feu ) {
                        $feu = $this->GetModuleInstance('MAMS');
                    }
                }
                if( $feu ) {
                    $uinfo = $feu->GetUserInfo($onerow->author_id * -1); //TODO adapt for MAMS
                    if( $uinfo[0] ) $onerow->author = $uinfo[1]['username'];
                }
            }
            $onerow->id = $row['news_id'];
            $onerow->title = $row['news_title'] ? news_ops::execSpecialize($row['news_title']) : (string)$row['news_title'];
            $onerow->content = $row['news_data'] ? news_ops::execSpecialize($row['news_data']) : (string)$row['news_data'];
            $str = $row['summary'] ? news_ops::execSpecialize($row['summary']) : (string)$row['summary'];
            if( $str ) {
                if( preg_match('/^\s*<br ?\/?>\s*$/', $str) ) {
                    $onerow->summary = '';
                }
                else {
                    $onerow->summary = trim($str);
                }
            }
            else {
                $onerow->summary = '';
            }
            if( !empty($row['news_extra']) ) $onerow->extra = news_ops::execSpecialize($row['news_extra']); // TODO CHECK FORMAT
            $onerow->postdate = $row['news_date'];
            $onerow->startdate = $row['start_time'];
            $onerow->enddate = $row['end_time'];
            $onerow->create_date = $row['create_date'];
            $onerow->modified_date = $row['modified_date'];
            $onerow->category = $row['news_category_name'];

            //
            // Handle the custom fields
            //
            $onerow->fields = news_ops::get_fields($row['news_id'], TRUE); //TODO sanitize untrusted content
            $onerow->fieldsbyname = $onerow->fields; // dumb, I know.
            $onerow->file_location = $config['uploads_url'].'/news/id'.$row['news_id'];

            $moretext = (!empty($params['moretext'])) ? trim($params['moretext']) : $this->Lang('more');

            $sendtodetail = array('articleid'=>$row['news_id']);
            if( !empty($params['showall']) ) {
                $sendtodetail['showall'] = (int)$params['showall'];
            }
            if( !empty($params['origid']) ) {
                $sendtodetail['origid'] = $returnid = $params['origid'];
            }
            else {
                $value = cms_utils::get_app_data('News::origid');
                if( $value !== null) {
                    $sendtodetail['origid'] = $returnid = $value;
                }
                else if( !empty($params['detailpage']) ) {
                    $sendtodetail['origid'] = $returnid; // $returnid might be re-purposed for specifying displayed detail page
                }
            }
            if( !empty($params['detailtemplate']) ) {
                $sendtodetail['detailtemplate'] = $params['detailtemplate'];
            }
            $prettyurl = $row['news_url'];
            if( !$prettyurl ) {
                $aliased_title = munge_string_to_url($row['news_title']);
                $prettyurl = 'news/'.$row['news_id']."/$displayid/$aliased_title";
                if( !empty($sendtodetail['detailtemplate']) ) {
                    $prettyurl .= '/d,' . $sendtodetail['detailtemplate'];
                }
            }

            if( !empty($params['lang']) ) $sendtodetail['lang'] = trim($params['lang']);
            if( !empty($params['category_id']) ) $sendtodetail['category_id'] = (int)$params['category_id'];
            if( !empty($params['pagelimit']) ) $sendtodetail['pagelimit'] = (int)$params['pagelimit'];
            $onerow->detail_url = $this->create_url($id, 'detail', $returnid, $sendtodetail, false, true); //!inline, targetcontentonly UNUSED in any of the default News templates
            $onerow->link = $this->create_url($id, 'detail', $displayid, $sendtodetail, false, true, $prettyurl); // !inline, targetcontentonly UNUSED in any of the default News templates
            $onerow->titlelink = $this->CreateLink($id, 'detail', $displayid, $row['news_title'], $sendtodetail,   '',               false,           false,         '',           false,                    $prettyurl); // !inline, !targetcontentonly UNUSED in any of the default News templates
            $onerow->morelink = $this->CreateLink($id, 'detail', $displayid,   $moretext,         $sendtodetail,   '',               false,           false,         '',           false,                    $prettyurl); // !inline, !targetcontentonly
            $onerow->moreurl = $this->create_url ($id, 'detail', $displayid, $sendtodetail, false, false, $prettyurl); // !inline, !targetcontentonly

            $entryarray[] = $onerow;
            $dbresult->MoveNext();
        }
        $dbresult->Close();
    } // if $dbresult

    $tpl_ob->assign('itemcount', count($entryarray));
    $tpl_ob->assign('items', $entryarray);
    $tpl_ob->assign('category_label', $this->Lang('category_label'));
    $tpl_ob->assign('author_label', $this->Lang('author_label'));

    foreach( $params as $key => $value ) {
        if( $key == 'mact' || $key == 'action' ) continue;
        $tpl_ob->assign('param_'.$key, $value);
    }

    unset($params['pagenumber']);
    $catarray = news_ops::get_categories($id, $params, $returnid);

    $catName = '';
    if( isset($params['category']) ) {
        $catName = $params['category'];
    }
    else if( isset($params['category_id']) ) {
        if( $catarray ) {
            foreach( $catarray as $item ) {
                if( $item['news_category_id'] == $params['category_id'] ) {
                    $catName = $item['news_category_name'];
                    break;
                }
            }
        }
        //$catName = $db->GetOne('SELECT news_category_name FROM '.CMS_DB_PREFIX . 'module_news_categories where news_category_id=?', array($params['category_id']));
    }
    $tpl_ob->assign('category_name', $catName);
    $tpl_ob->assign('count', count($catarray));
    $tpl_ob->assign('cats', $catarray);
} //if IsCached

$tpl_ob->display();

?>
