<?php
#CMSMS News module function
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify News') ) return; // or a new 'View News' permission

if (isset($params['bulk_action']) ) {
    if( !isset($params['sel']) || !is_array($params['sel']) || count($params['sel']) == 0 ) {
        $this->ShowErrors($this->Lang('error_noarticlesselected'));
    }
    else {
        $sel = array();
        foreach( $params['sel'] as $one ) {
            $one = (int)$one;
            if( $one < 1 ) continue;
            if( in_array($one,$sel) ) continue;
            $sel[] = $one;
        }

        switch($params['bulk_action']) {
        case 'delete':
            if (!$this->CheckPermission('Delete News')) {
                $this->ShowErrors($this->Lang('needpermission','Modify News'));
            }
            else {
                foreach( $sel as $news_id ) {
                    news_admin_ops::delete_article($news_id);
                }
            }
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setcategory':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET news_category_id = ?, modified_date = NOW()
WHERE news_id IN ('.implode(',',$sel).')';
            $parms = array((int)$params['category']);
            $db->Execute($query,$parms);
            audit((int)$params['category'],$this->GetName(),'Category of '.count($sel).' articles changed');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setpublished':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = NOW()
WHERE news_id IN ('.implode(',',$sel).')';
            $db->Execute($query,array('published'));
            audit('',$this->GetName(),'Status of '.count($sel)." articles changed to 'published'");
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setdraft':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = NOW()
WHERE news_id IN ('.implode(',',$sel).')';
            $db->Execute($query,array('draft'));
            audit('',$this->GetName(),'Status of '.count($sel)." articles changed to 'draft'");
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        default:
            break;
        }
    }
}

$categorylist = $db->GetAssoc('SELECT COALESCE(long_name,news_category_name),news_category_id FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy');
if( $categorylist ) {
    $categorylist = [$this->Lang('allcategories') => ''] + $categorylist;
}

if( isset($params['pagenumber']) ) {
    $pagenumber = (int)$params['pagenumber'];
    $_SESSION['news_pagenumber'] = $pagenumber;
}
elseif( isset($_SESSION['news_pagenumber']) ) {
    $pagenumber = (int)$_SESSION['news_pagenumber'];
}
else {
    $pagenumber = 1;
}

$settings = ['category'=>0,'allcategories'=>true,'pagelimit'=>0,'sortby'=>'news_date DESC'];
if( isset($params['submitfilter']) ) {
    if( isset( $params['category']) ) {
        $settings['category'] = (int)($params['category']);
    }
    if( isset( $params['sortby'] ) ) {
        $settings['sortby'] = str_replace("'",'_',$params['sortby']);
    }
    if( isset( $params['pagelimit'] ) ) {
        $settings['pagelimit'] = max(2,(int)$params['pagelimit']);
    }
    $settings['allcategories'] = !empty($params['allcategories']); // i.e. descendent-categories too
    cms_userprefs::set($modname.'_articles_filter',serialize($settings));
    unset($_SESSION['news_pagenumber']);
    $pagenumber = 1;
}
elseif( isset($params['resetfilter']) ) {
    cms_userprefs::set($modname.'_articles_filter',serialize($settings));
    unset($_SESSION['news_pagenumber']);
    $pagenumber = 1;
}

$tmp = cms_userprefs::get($modname.'_articles_filter');
if( $tmp ) {
    $tmp = unserialize($tmp);
}
if( $tmp ) {
    $settings = $tmp;
}

$curcategory = $settings['category']; // might be 0
$allcategories = $settings['allcategories'];
$sortby = $settings['sortby'];

$sortlist = array();
$sortlist[$this->Lang('post_date_desc')]='news_date DESC';
$sortlist[$this->Lang('post_date_asc')]='news_date ASC';
$sortlist[$this->Lang('expiry_date_desc')]='end_time DESC';
$sortlist[$this->Lang('expiry_date_asc')]='end_time ASC';
$sortlist[$this->Lang('title_asc')] = 'news_title ASC';
$sortlist[$this->Lang('title_desc')] = 'news_title DESC';
$sortlist[$this->Lang('status_asc')] = 'status ASC';
$sortlist[$this->Lang('status_desc')] = 'status DESC';

$tpl->assign('prompt_category',$this->Lang('category'));
$tpl->assign('categorylist',array_flip($categorylist));
$tpl->assign('curcategory',$curcategory);
$tpl->assign('allcategories',$allcategories != 0);
$tpl->assign('sortlist',array_flip($sortlist));
$tpl->assign('sortby',$sortby);
$tpl->assign('prompt_showchildcategories',$this->Lang('showchildcategories'));
$tpl->assign('prompt_sorting',$this->Lang('prompt_sorting'));
$tpl->assign('submitfilter',
            $this->CreateInputSubmit($id,'submitfilter',$this->Lang('submit')));
$tpl->assign('prompt_pagelimit',$this->Lang('prompt_pagelimit'));

//Load the applicable articles
$entryarray = array();

$query1 = 'SELECT n.*,nc.news_category_name,nc.long_name FROM '.CMS_DB_PREFIX.'module_news n LEFT OUTER JOIN '.CMS_DB_PREFIX.'module_news_categories nc ON n.news_category_id = nc.news_category_id';
$parms = array();
if ($curcategory) {
    $scats = array_flip($categorylist); // see also above
    $cname = isset($scats[$curcategory]) ? $scats[$curcategory] : 'NOMATCH';
    if( $allcategories ) {
        $query1 .= ' WHERE nc.long_name LIKE ?';
        $parms[] = $cname.'%';
    }
    else {
        $query1 .= ' WHERE nc.long_name = ?';
        $parms[] = $cname;
    }
    $have_filter = true;
}
elseif( !$allcategories ) {
    $query1 .= " WHERE INSTR(`long_name`, ' | ') = 0";
    $have_filter = true;
}
else {
    $have_filter = false;
}
$query2 = str_replace('n.*,nc.news_category_name,nc.long_name', 'COUNT(*)', $query1);
$query1 .= ' ORDER BY '.$sortby;

$numrows = (int)$db->GetOne($query2);
$savedlimit = $settings['pagelimit'];

if ($savedlimit == 0) {
    if ($numrows > 50) { $pagelimit = 100; }
    elseif ($numrows > 25) { $pagelimit = 50; }
    elseif ($numrows > 10) { $pagelimit = 25; }
    else { $pagelimit = 10; }
}
else {
    $pagelimit = $savedlimit;
}

$pagenumber = max(1,$pagenumber);
$startelement = ($pagenumber-1) * $pagelimit;
$dbresult = $db->SelectLimit($query1,$pagelimit,$startelement,$parms);
$pagecount = (int)ceil(($numrows/$pagelimit) - 0.001);
if ($pagenumber > 1 && $pagecount < $pagenumber) {
    $numrows = (int)$db->GetOne($query2);
    if ($savedlimit == 0) {
        if ($numrows > 50) { $pagelimit = 100; }
        elseif ($numrows > 25) { $pagelimit = 50; }
        elseif ($numrows > 10) { $pagelimit = 25; }
        else { $pagelimit = 10; }
    }
    $_SESSION['news_pagenumber'] = --$pagenumber;
    $startelement = ($pagenumber-1) * $pagelimit;
    $dbresult = $db->SelectLimit($query1,$pagelimit,$startelement,$parms);
    $pagecount = (int)ceil(($numrows/$pagelimit) - 0.001);
}

$lengths = [10 => 10];
if ($numrows > 10 ) { $lengths[25] = 25; }
if ($numrows > 25 ) { $lengths[50] = 50; }
if ($numrows > 50 ) { $lengths[100] = 100; }

$tpl->assign('pagelimit',$pagelimit);
$tpl->assign('pagelimits',$lengths);
$tpl->assign('pagenumber',$pagenumber);
$tpl->assign('pagecount',$pagecount);
$tpl->assign('oftext',$this->Lang('prompt_of'));

$admintheme = cms_utils::get_theme_object();

if ($dbresult) {
  $rowclass = 'row1';
  while ($row = $dbresult->FetchRow()) {
    $onerow = new stdClass();

    $onerow->id = $row['news_id'];
    $onerow->news_title = $row['news_title'] ? news_ops::execSpecialize($row['news_title']) : (string)$row['news_title'];
    $onerow->title = $this->CreateLink($id,'editarticle',$returnid,$row['news_title'],array('articleid'=>$row['news_id']));
    $onerow->data = $row['news_data'] ? news_ops::execSpecialize($row['news_data']) : (string)$row['news_data'];
    $onerow->expired = 0;
    if( ($row['end_time'] != '') && ($db->UnixTimeStamp($row['end_time']) < time()) ) $onerow->expired = 1;
    $onerow->postdate = $row['news_date'];
    $onerow->startdate = $row['start_time'];
    $onerow->enddate = $row['end_time'];
    $onerow->u_postdate = $db->UnixTimeStamp($row['news_date']);
    $onerow->u_startdate = $db->UnixTimeStamp($row['start_time']);
    $onerow->u_enddate = $db->UnixTimeStamp($row['end_time']);
    $onerow->status = $this->Lang($row['status']);
    if( $this->CheckPermission('Approve News') ) {
        if( $row['status'] == 'published' ) {
            $onerow->approve_link = $this->CreateLink($id,'approvearticle',
                                                      $returnid,
                                                      $admintheme->DisplayImage('icons/system/true.gif',$this->Lang('revert'),'','','systemicon'),array('approve'=>0,'articleid'=>$row['news_id']));
        }
        else {
            $onerow->approve_link = $this->CreateLink($id,'approvearticle',
                                                      $returnid,
                                                      $admintheme->DisplayImage('icons/system/false.gif',$this->Lang('approve'),'','','systemicon'),array('approve'=>1,'articleid'=>$row['news_id']));
        }
    }
    $onerow->category = ($row['long_name']) ?: $row['news_category_name'];

    $onerow->rowclass = $rowclass;

    if( $this->CheckPermission('Modify News') ) {
        $onerow->edit_url = $this->create_url($id,'editarticle',$returnid,
                                              array('articleid'=>$row['news_id']));
        $onerow->editlink = $this->CreateLink($id,'editarticle',$returnid,
                                              $admintheme->DisplayImage('icons/system/edit.gif',$this->Lang('edit'),'','','systemicon'),array('articleid'=>$row['news_id']));
    }
    if( $this->CheckPermission('Delete News') ) {
        $onerow->delete_url = $this->create_url($id,'deletearticle',$returnid,array('articleid'=>$row['news_id']));
    }

    $entryarray[] = $onerow;
    ($rowclass == "row1" ? $rowclass = "row2" : $rowclass = "row1");
  }
  $dbresult->Close();
}

$tpl->assign('aitems',$entryarray);
$tpl->assign('aitemcount',count($entryarray));

if( $this->CheckPermission('Modify News') ) {
    $tpl->assign('addlink',$this->CreateLink($id,'addarticle',$returnid,$admintheme->DisplayImage('icons/system/newobject.gif',$this->Lang('addarticle'),'','','systemicon'),array(),'',false,false,'') .' '. $this->CreateLink($id,'addarticle',$returnid,$this->Lang('addarticle'),array(),'',false,false,'class="pageoptions"'));
}
$tpl->assign('can_add',$this->CheckPermission('Modify News'));
$tpl->assign('can_delete',$this->CheckPermission('Delete News'));
if( $categorylist ) {
    array_shift($categorylist); // no 'allcategories' item in this picker
    $tpl->assign('categoryinput',$this->CreateInputDropdown($id,'category',$categorylist,-1,'','id="selcategory"'));
}
$tpl->assign('have_filter',$have_filter);
$tpl->assign('reassigntext',$this->Lang('reassign_category'));
$tpl->assign('selecttext',$this->Lang('select'));
$tpl->assign('statustext',$this->Lang('status'));
$tpl->assign('startdatetext',$this->Lang('startdate'));
$tpl->assign('enddatetext',$this->Lang('enddate'));
$tpl->assign('titletext',$this->Lang('title'));
$tpl->assign('postdatetext',$this->Lang('postdate'));
$tpl->assign('categorytext',$this->Lang('category'));

$iconsurl = $config['admin_url'].'/themes/'.$admintheme->themeName.'/images/icons/system';
$tpl->assign('iconurl',$iconsurl);
$tpl->assign('securename',CMS_SECURE_PARAM_NAME);
$tpl->assign('secureval',$_SESSION[CMS_USER_KEY]);
$tpl->assign('endform',$this->CreateFormEnd());
