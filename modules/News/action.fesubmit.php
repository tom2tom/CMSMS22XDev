<?php
// Robert Campbell: this action is officially deprecated.
if (!isset($gCms)) exit;
if( !$this->GetPreference('allow_fesubmit',0) ) return;

function __newsCleanHTML($html)
{
    $i = 0;
    for( $i = 0; $i < 10; $i++ ) {
        $old = $html;
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
        $html = str_replace('script:','___',$html);
        if( strcmp($old,$html) == 0 ) break;
    }
    return $html;
}

$title = '';
$extra = '';
$content = '';
$summary = '';
$status = $this->GetPreference('fesubmit_status','draft');
$startdate = time();
$ndays = (int)$this->GetPreference('expiry_interval',180);
if( $ndays <= 0 ) $ndays = 180;
$enddate = strtotime(sprintf("+%d days",$ndays), time());
$userid = get_userid(false);
$category_id = $this->GetPreference('default_category', '');
$do_send_email = false;
$do_redirect = false;
$modname = $this->GetName();

if (isset($params['formtemplate'])) {
  $template = trim($params['formtemplate']);
}
else {
  $tpl = CmsLayoutTemplate::load_dflt_by_type('News::form');
  if( !is_object($tpl) ) {
    audit('',$modname.':feusubmit','No default form template found');
    return;
  }
  $template = $tpl->get_name();
}

// handle the page to go to after submit.
$dest_page = $returnid;
$tmp = $this->GetPreference('fesubmit_redirect');
if( !empty($tmp) ) {
  $manager = $gCms->GetHierarchyManager();
  $node = $manager->sureGetNodeByAlias($tmp);
  if (isset($node)) {
    $dest_page = $node->getID();
  }
  else {
    $node = $manager->sureGetNodeById($tmp);
    if (isset($node)) $dest_page = $tmp;
  }
}

if( $userid == '' ) {
  // not logged in to the admin console
  // see if we're logged into FEU.
  $module = $this->GetModuleInstance('FrontEndUsers');
  if( $module ) {
    $userid = $module->LoggedInId();
    $userid = $userid * -1;
  }
}

if (isset($params['category'])) {
  $query = 'SELECT news_category_id FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_name = ?';
  $tmp = $db->GetOne($query,array($params['category']));
  if( $tmp ) $category_id = $tmp;
}

$tpl = $smarty->CreateTemplate($this->GetTemplateResource($template),null,$modname,$smarty);
if( isset( $params['submit'] ) ) {
    try {
        if( isset($params['title'] ) ) $title = strip_tags(cms_html_entity_decode(trim($params['title'])));
        if( isset($params['content']) ) $content = __newsCleanHTML(cms_html_entity_decode(trim($params['content'])));
        if( isset($params['summary']) ) $summary = __newsCleanHTML(cms_html_entity_decode(trim($params['summary'])));
        if( isset($params['extra']) ) $extra = strip_tags(cms_html_entity_decode(trim($params['extra'])));
        if( isset($params['category_id']) ) $category_id = (int)$params['category_id'];
        if( isset($params['input_category'])) $category_id = (int)$params['input_category'];

        if (isset($params['startdate_Month'])) {
            $startdate = mktime((int)$params['startdate_Hour'], (int)$params['startdate_Minute'], (int)$params['startdate_Second'],
                                (int)$params['startdate_Month'], (int)$params['startdate_Day'], (int)$params['startdate_Year']);
        }

        if (isset($params['enddate_Month'])) {
            $enddate = mktime((int)$params['enddate_Hour'], (int)$params['enddate_Minute'], (int)$params['enddate_Second'],
                              (int)$params['enddate_Month'], (int)$params['enddate_Day'], (int)$params['enddate_Year']);
        }

        if( $startdate > $enddate ) throw new CmsException($this->Lang('startdatetoolate'));
        if( $title == '' ) throw new CmsException($this->Lang('notitlegiven'));
        if( $content == '' ) throw new CmsException($this->Lang('nocontentgiven'));

        // generate a new article id
        $articleid = $db->GenID(CMS_DB_PREFIX."module_news_seq");

        // test file upload custom fields
        $qu = "SELECT id,name,type FROM ".CMS_DB_PREFIX."module_news_fielddefs WHERE type='file'";
        $fields = $db->GetArray($qu);

        foreach( $fields as $onefield ) {
            $elem = $id.'news_customfield_'.$onefield['id'];
            if( isset($_FILES[$elem]) && $_FILES[$elem]['name'] != '') {
                if( $_FILES[$elem]['error'] == 0 && $_FILES[$elem]['tmp_name'] != '' ) {
                    $error = '';
                    $value = news_admin_ops::handle_upload($articleid,$elem,$error);
                    if( $value === FALSE ) throw new CmsException($error);
                    $params['news_customfield_'.$onefield['id']] = $value;
                }
                else {
                    // error with upload
                    // abort the whole thing
                    throw new CmsException($this->Lang('error_upload'));
                }
            }
        }

        // and generate the insert query
        // note: there's no option for fesubmit wether it's searchable or not.
        $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news
              (news_id, news_category_id, news_title, news_data, summary,
               news_extra, status, news_date, start_time, end_time, create_date,
               modified_date,author_id,searchable)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $dbr = $db->Execute($query,
                            array($articleid, $category_id, $title,
                                  $content, $summary, $extra, $status,
                                  trim($db->DBTimeStamp($startdate), "'"),
                                  trim($db->DBTimeStamp($startdate), "'"),
                                  trim($db->DBTimeStamp($enddate), "'"),
                                  trim($db->DBTimeStamp(time()), "'"),
                                  trim($db->DBTimeStamp(time()), "'"),
                                  $userid,1));

        if( $dbr ) {
            // handle the custom fields
            $now = $db->DbTimeStamp(time());
            $query = 'INSERT INTO '.CMS_DB_PREFIX."module_news_fieldvals (news_id, fielddef_id, value, create_date, modified_date)
                VALUES (?,?,?,$now,$now)";
            foreach( $params as $key => $value ) {
                $value = trim($value);
                if( empty($value) ) continue;
                if( preg_match('/^news_customfield_/',$key) ) {
                    $field_id = intval(substr($key,17));
                    $db->Execute($query,array($articleid,$field_id,$value));
                }
            }

            // should've checked those errors too, but eh, I'm up for the odds.

            //Update search index
            $module = cms_utils::get_search_module();
            if (is_object($module)) {
                $module->AddWords($modname, $articleid, 'article', $content . ' ' . $summary . ' ' . $title . ' ' . $title, $enddate );
            }

            // Send an email
            $do_send_email = true;
            $do_redirect = true;

            // send an event
            \CMSMS\HookManager::do_hook('News::NewsArticleAdded',
                              array('news_id' => $articleid,
                                    'category_id' => $category_id,
                                    'title' => $title,
                                    'content' => $content,
                                    'summary' => $summary,
                                    'status' => $status,
                                    'start_time' => $startdate,
                                    'end_time' => $enddate,
                                    'useexp' => 1));

            // put mention into the admin log
            audit($articleid,$modname.' article',"Added: $title (from frontend)");

            // and we're done
            $tpl->assign('message',$this->Lang('articleadded'));
        }
    }
    catch( Exception $e ) {
        $tpl->assign('error',$error);
    }
}


// build the category list
$categorylist = array();
$query = "SELECT * FROM ".CMS_DB_PREFIX."module_news_categories ORDER BY hierarchy";
$dbresult = $db->Execute($query);
while ($dbresult && $row = $dbresult->FetchRow()) {
    $categorylist[$row['news_category_id']] = $row['long_name'];
}

// Display template
$tpl->assign('category_id',$category_id);
$tpl->assign('title',$title);
$tpl->assign('categorylist',$categorylist);
$tpl->assign('extra',$extra);
$tpl->assign('content',$content);
$tpl->assign('summary',$summary);
$tpl->assign('hide_summary_field',$this->GetPreference('hide_summary_field','0'));
$tpl->assign('allow_summary_wysiwyg',$this->GetPreference('allow_summary_wysiwyg',1));
$tpl->assign('startdate', $startdate);
$tpl->assign('enddate', $enddate);
$tpl->assign('status',$this->CreateInputHidden($id,'status',$status));

$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE public = 1 ORDER BY item_order';
$dbr = $db->Execute($query);
$customfields = array();
$customfieldsbyname = array();
if( $dbr ) {
  while( $row = $dbr->FetchRow() ) {
  if( $row['type'] == 'linkedfile' ) continue;
    $obj = new stdClass();
    $obj->name = $row['name'];
    $obj->type = $row['type'];
    $obj->id = $row['id'];
    $obj->max_length = $row['max_length'];
    $key = str_replace(' ','_',strtolower($row['name']));
    $customfieldsbyname[$key] = $obj;
  }
  $dbr->Close();
}
if( $customfieldsbyname ) $tpl->assign('customfields',$customfieldsbyname);

$tpl->display();

if( $do_send_email ) {

    $tpl2 = $smarty->CreateTemplate("module_db_tpl:$modname;email_template",null,$modname,$smarty);
    $tmp_vars = $tpl->get_template_vars();
    foreach( $tmp_vars as $key => $val ) {
        $tpl2->assign($key,$val);
    }
    $tmp_vars2 = $tpl2->get_template_vars();

    // this needs to be done after the form is generated
    // because we use some of the same smarty variables
    $cmsmailer = new cms_mailer();
    if( $cmsmailer ) {
        $addy = trim($this->GetPreference('formsubmit_emailaddress'));
        if( $addy ) {
            $tpl2->assign('startdate',$startdate);
            $tpl2->assign('enddate',$enddate);
            $tpl2->assign('ipaddress',\cms_utils::get_real_ip());
            $tpl2->assign('status',$status);
            if( $title != '' ) $tpl2->assign('title',$title);
            if( $summary != '' ) $tpl2->assign('summary',$summary);
            if( $content != '' ) $tpl2->assign('content',$content);

            $cmsmailer->AddAddress( $addy );
            $cmsmailer->SetSubject( $this->GetPreference('email_subject',$this->Lang('subject_newnews')));
            $cmsmailer->IsHTML( false );

            $body = $tpl2->fetch();
            $cmsmailer->SetBody( $body );
            $cmsmailer->Send();
        }
    }
}

if( $do_redirect ) $this->RedirectContent($dest_page);

?>
