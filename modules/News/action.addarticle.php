<?php
if (!isset($gCms))
    exit;

if (!$this->CheckPermission('Modify News'))
    return;

if (isset($params['cancel']))
    $this->Redirect($id, 'defaultadmin', $returnid);

/*--------------------
 * Variables
 ---------------------*/

$ndays        = (int)$this->GetPreference('expiry_interval', 180);
if ($ndays == 0) $ndays = 180;
$now          = time();
$status       = 'draft';
if ($this->CheckPermission('Approve News')) $status = 'published';
$userid       = get_userid();

$content      = isset($params['content']) ? $params['content'] : '';
$enddate      = strtotime(sprintf("+%d days", $ndays), $now);
$extra        = isset($params['extra']) ? trim(strip_tags($params['extra'])) : '';
$news_url     = isset($params['news_url']) ? $params['news_url'] : '';
$postdate     = $now;
$searchable   = isset($params['searchable']) ? (int)$params['searchable'] : 1;
$startdate    = $now;
$status       = isset($params['status']) ? $params['status'] : $status;
$summary      = isset($params['summary']) ? $params['summary'] : '';
$title        = isset($params['title']) ? trim(strip_tags($params['title'])) : '';
$usedcategory = isset($params['category']) ? $params['category'] : $this->GetPreference('default_category', '');
$useexp       = isset($params['useexp']) ? (int)$params['useexp'] : 0;

if (isset($params['postdate_Month'])) {
    $postdate = mktime($params['postdate_Hour'], $params['postdate_Minute'], $params['postdate_Second'], $params['postdate_Month'], $params['postdate_Day'], $params['postdate_Year']);
}

if (isset($params['startdate_Month'])) {
    $startdate = mktime($params['startdate_Hour'], $params['startdate_Minute'], $params['startdate_Second'], $params['startdate_Month'], $params['startdate_Day'], $params['startdate_Year']);
}

if (isset($params['enddate_Month'])) {
    $enddate = mktime($params['enddate_Hour'], $params['enddate_Minute'], $params['enddate_Second'], $params['enddate_Month'], $params['enddate_Day'], $params['enddate_Year']);
}

/*--------------------
 * Logic
 ---------------------*/

if (isset($params['submit'])) {
    $error = FALSE;
    if (!$title) {
        $error = $this->ShowErrors($this->Lang('notitlegiven'));
    } else if (!$content) {
        $error = $this->ShowErrors($this->Lang('nocontentgiven'));
    } else if ($useexp) {
        if ($startdate >= $enddate)
            $error = $this->ShowErrors($this->Lang('error_invaliddates'));
    }

    if ($error === FALSE && $news_url) {
        // check for starting or ending slashes
        if (startswith($news_url, '/') || endswith($news_url, '/'))
            $error = $this->ShowErrors($this->Lang('error_invalidurl'));

        if ($error === FALSE) {
            // check for invalid chars.
            $translated = munge_string_to_url($news_url, false, true);
            if (strtolower($translated) != strtolower($news_url))
                $error = $this->ShowErrors($this->Lang('error_invalidurl'));
        }

        if ($error === FALSE) {
            // make sure this url isn't taken.
            cms_route_manager::load_routes();
            $route = cms_route_manager::find_match($news_url);
            if ($route) {
                $error = $this->ShowErrors($this->Lang('error_invalidurl'));
                // we're adding an article, not editing... any matching route is bad.
            }
        }
    }

    //
    // database work
    //
    if ($error) {
        echo $error;
    } else {
        $articleid = $db->GenID(CMS_DB_PREFIX . "module_news_seq");
        $query = 'INSERT INTO ' . CMS_DB_PREFIX . 'module_news (news_id, news_category_id, news_title, news_data, summary, status, news_date, start_time, end_time, create_date, modified_date,author_id,news_extra,news_url,searchable) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        if ($useexp) {
            $dbr = $db->Execute($query, array(
                $articleid,
                $usedcategory,
                $title,
                $content,
                $summary,
                $status,
                trim($db->DBTimeStamp($postdate), "'"),
                trim($db->DBTimeStamp($startdate), "'"),
                trim($db->DBTimeStamp($enddate), "'"),
                trim($db->DBTimeStamp(time()), "'"),
                trim($db->DBTimeStamp(time()), "'"),
                $userid,
                $extra,
                $news_url,
                $searchable
            ));
        } else {
            $dbr = $db->Execute($query, array(
                $articleid,
                $usedcategory,
                $title,
                $content,
                $summary,
                $status,
                trim($db->DBTimeStamp($postdate), "'"),
                NULL,
                NULL,
                trim($db->DBTimeStamp(time()), "'"),
                trim($db->DBTimeStamp(time()), "'"),
                $userid,
                $extra,
                $news_url,
                $searchable
            ));
        }

        if (!$dbr) {
            echo "DEBUG: SQL = " . $db->sql . "<br/>";
            die($db->ErrorMsg());
        }

        //
        //Handle submitting the 'custom' fields
        //
        // get the field types
        $query = "SELECT id,name,type FROM " . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='file'";
        $types = $db->GetArray($query);

        foreach ($types as $onetype) {
            $elem = $id . 'customfield_' . $onetype['id'];
            if (isset($_FILES[$elem]) && $_FILES[$elem]['name'] != '') {
                if ($_FILES[$elem]['error'] != 0 || $_FILES[$elem]['tmp_name'] == '') {
                    echo $this->ShowErrors($this->Lang('error_upload'));
                    $error = TRUE;
                } else {
                    $error = '';
                    $value = news_admin_ops::handle_upload($articleid, $elem, $error);
                    if ($value === FALSE) {
                        echo $this->ShowErrors($error);
                        $error = TRUE;
                    } else {
                        $params['customfield'][$onetype['id']] = $value;
                    }
                }
            }
        }

        if (isset($params['customfield']) && !$error) {
            $now = trim($db->DBTimeStamp(time()), "'");
            foreach ($params['customfield'] as $fldid => $value) {
                if ($value == '')
                    continue;

                $query = "INSERT INTO " . CMS_DB_PREFIX . "module_news_fieldvals (news_id,fielddef_id,value,create_date,modified_date) VALUES (?,?,?,?,?)";
                $dbr = $db->Execute($query, array(
                    $articleid,
                    $fldid,
                    $value,
                    $now,
                    $now
                ));
                if (!$dbr)
                    die('FATAL SQL ERROR: ' . $db->ErrorMsg() . '<br/>QUERY: ' . $db->sql);
            }
        }

        if (!$error && $status == 'published' && $news_url) {
            // todo: if not expired
            // register the route.
            news_admin_ops::delete_static_route($articleid);
            news_admin_ops::register_static_route($news_url, $articleid);
        }

        if (!$error && $status == 'published' && $searchable) {
            //Update search index
            $module = cms_utils::get_search_module();
            if (is_object($module)) {
                $text = '';
                if (isset($params['customfield'])) {
                    foreach ($params['customfield'] as $fldid => $value) {
                        if (strlen($value) > 1)
                            $text .= $value . ' ';
                    }
                }

                $text .= $content . ' ' . $summary . ' ' . $title . ' ' . $title;
                $module->AddWords($this->GetName(), $articleid, 'article', $text, ($useexp == 1 && $this->GetPreference('expired_searchable', 0) == 0) ? $enddate : NULL);
            }
        }

        if (!$error) {
            \CMSMS\HookManager::do_hook('News::NewsArticleAdded',
                array('news_id' => $articleid,
                      'category_id' => $usedcategory,
                      'title' => $title,
                      'content' => $content,
                      'summary' => $summary,
                      'status' => $status,
                      'start_time' => $startdate,
                      'end_time' => $enddate,
                      'postdate' => $postdate,
                      'useexp' => $useexp,
                      'extra' => $extra ));
            // put mention into the admin log
            audit($articleid, 'News: ' . $title, 'Article added');
            $this->SetMessage($this->Lang('articleadded'));
            $this->Redirect($id, 'defaultadmin', $returnid);
        } // !$error
    } // outer !$error
// end submit
} elseif (isset($params['preview'])) {
    // save data for preview.
    unset($params['apply']);
    unset($params['preview']);
    unset($params['submit']);
    unset($params['cancel']);
    unset($params['ajax']);

    if (empty($error)) {
        $tmpfname = tempnam(TMP_CACHE_LOCATION, $this->GetName() . '_preview');
        file_put_contents($tmpfname, serialize($params));

        $detail_returnid = $this->GetPreference('detail_returnid', -1);
        if ($detail_returnid <= 0) {
            // now get the default content id.
            $detail_returnid = ContentOperations::get_instance()->GetDefaultContent();
        }
        if (isset($params['previewpage']) && (int)$params['previewpage'] > 0)
            $detail_returnid = (int)$params['previewpage'];

        $_SESSION['news_preview'] = array(
            'fname' => basename($tmpfname),
            'checksum' => md5_file($tmpfname)
        );
        $tparms = array('preview' => md5(serialize($_SESSION['news_preview'])));
        if (isset($params['detailtemplate'])) {
            $tparms['detailtemplate'] = trim($params['detailtemplate']);
        }
        $url = $this->create_url('_preview_', 'detail', $detail_returnid, $tparms, TRUE);
        $url = str_replace('&amp;', '&', $url);
        $out = array('response'=>'Success', 'details' => $url);
        $flags = 0;
    } else {
        $out = array('response'=>'Error', 'details' => $error);
        $flags = JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
        if (defined('JSON_INVALID_UTF8_IGNORE')) {
            $flags |= JSON_INVALID_UTF8_IGNORE;
        }
    }

    $handlers = ob_list_handlers();
    for ($cnt = 0; $cnt < count($handlers); $cnt++) { ob_end_clean(); }

    echo json_encode($out, $flags);
    exit;
}

//
// build the form
//
$statusdropdown = array();
$statusdropdown[$this->Lang('draft')] = 'draft';
$statusdropdown[$this->Lang('published')] = 'published';

$categorylist = array();
$query = "SELECT * FROM " . CMS_DB_PREFIX . "module_news_categories ORDER BY hierarchy";
$dbresult = $db->Execute($query);

while ($dbresult && $row = $dbresult->FetchRow()) {
    $categorylist[$row['long_name']] = $row['news_category_id'];
}


// Display custom fields
$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_fielddefs ORDER BY item_order';
$dbr = $db->Execute($query);
$custom_flds = array();

while ($dbr && ($row = $dbr->FetchRow())) {
    if (isset($row['extra']) && $row['extra'])
        $row['extra'] = unserialize($row['extra']);

    $options = [];
    if (isset($row['extra']['options']))
        $options = $row['extra']['options'];

    $value = isset($params['customfield'][$row['id']]) && in_array($params['customfield'][$row['id']], $params['customfield']) ? $params['customfield'][$row['id']] : '';

    if ($row['type'] == 'file') {
        $name = "customfield_" . $row['id'];
    } else {
        $name = "customfield[" . $row['id'] . "]";
    }

    $obj = new stdClass();

    $obj->value    = $value;
    $obj->type     = $row['type'];
    $obj->nameattr = $id . $name;
    $obj->idattr   = 'customfield_' . $row['id'];
    $obj->prompt   = $row['name'];
    $obj->size     = min(80, (int)$row['max_length']);
    $obj->max_len  = max(1, (int)$row['max_length']);
    $obj->options  = $options;
    // FIXME - If we create inputs with hmtl markup in smarty template, whats the use of switch and form API here?
    /*
    switch( $row['type'] ) {
        case 'textbox' :
            $size = min(50, $row['max_length']);
            $obj->field = $this->CreateInputText($id, $name, $value, $size, $row['max_length']);
            break;
        case 'checkbox' :
            $obj->field = $this->CreateInputHidden($id, $name, $value != '' ? $value : '0') . $this->CreateInputCheckbox($id, $name, '1', $value != '' ? $value : '0');
            break;
        case 'textarea' :
            $obj->field = $this->CreateTextArea(true, $id, $value, $name);
            break;
        case 'file' :
            $name = "customfield_" . $row['id'];
            $obj->field = $this->CreateFileUploadInput($id, $name);
            break;
        case 'dropdown' :
            $obj->field = $this->CreateInputDropdown($id, $name, array_flip($options));
            break;
    }
    */

    $custom_flds[$row['name']] = $obj;
}

/*--------------------
 * Pass everything to smarty
 ---------------------*/

$smarty->assign('formid', $id);
$smarty->assign('hide_summary_field', $this->GetPreference('hide_summary_field', '0'));
$smarty->assign('authortext', '');
$smarty->assign('inputauthor', '');
$smarty->assign('startform', $this->CreateFormStart($id, 'addarticle', $returnid, 'post', 'multipart/form-data'));
$smarty->assign('endform', $this->CreateFormEnd());
$smarty->assign('titletext', $this->Lang('title'));
$smarty->assign('title', $title);
$smarty->assign('allow_summary_wysiwyg', $this->GetPreference('allow_summary_wysiwyg'));
$smarty->assign('extratext', $this->Lang('extra'));
$smarty->assign('extra', $extra);
$smarty->assign('urltext', $this->Lang('url'));
$smarty->assign('news_url', $news_url);
$smarty->assign('postdate', $postdate);
$smarty->assign('postdateprefix', $id . 'postdate_');
$smarty->assign('useexp', $useexp);
$smarty->assign('actionid', $id);
$smarty->assign('inputexp', $this->CreateInputCheckbox($id, 'useexp', '1', $useexp, 'class="pagecheckbox"'));
$smarty->assign('startdate', $startdate);
$smarty->assign('startdateprefix', $id . 'startdate_');
$smarty->assign('enddate', $enddate);
$smarty->assign('enddateprefix', $id . 'enddate_');
$smarty->assign('status', $status);
$smarty->assign('categorylist', array_flip($categorylist));
$smarty->assign('category', $usedcategory);
$smarty->assign('submit', $this->CreateInputSubmit($id, 'submit', lang('submit')));
$smarty->assign('cancel', $this->CreateInputSubmit($id, 'cancel', lang('cancel')));
$smarty->assign('delete_field_val', $this->Lang('delete'));
$smarty->assign('titletext', $this->Lang('title'));
$smarty->assign('categorytext', $this->Lang('category'));
$smarty->assign('summarytext', $this->Lang('summary'));
$smarty->assign('contenttext', $this->Lang('content'));
$smarty->assign('postdatetext', $this->Lang('postdate'));
$smarty->assign('useexpirationtext', $this->Lang('useexpiration'));
$smarty->assign('startdatetext', $this->Lang('startdate'));
$smarty->assign('enddatetext', $this->Lang('enddate'));
$smarty->assign('searchable', $searchable);
$smarty->assign('select_option', $this->Lang('select_option'));
// tab stuff.
$smarty->assign('start_tab_headers', $this->StartTabHeaders());
$smarty->assign('tabheader_article', $this->SetTabHeader('article', $this->Lang('article')));
$smarty->assign('tabheader_preview', $this->SetTabHeader('preview', $this->Lang('preview')));
$smarty->assign('end_tab_headers', $this->EndTabHeaders());
$smarty->assign('start_tab_content', $this->StartTabContent());
$smarty->assign('start_tab_article', $this->StartTab('article', $params));
$smarty->assign('end_tab_article', $this->EndTab());
$smarty->assign('end_tab_content', $this->EndTabContent());
$smarty->assign('warning_preview', $this->Lang('warning_preview'));

$parms = array(
    'enablewysiwyg' => 1,
    'name' => $id . 'content',
    'text' => $content,
    'rows' => 10,
    'cols' => 80
);
$smarty->assign('inputcontent', CmsFormUtils::create_textarea($parms));

$parms = array(
    'enablewysiwyg' => $this->GetPreference('allow_summary_wysiwyg', 1),
    'name' => $id . 'summary',
    'text' => $summary,
    'rows' => 3,
    'cols' => 80
);
$smarty->assign('inputsummary', CmsFormutils::create_textarea($parms));

if ($custom_flds)
    $smarty->assign('custom_fields', $custom_flds);

if ($this->CheckPermission('Approve News')) {
    $smarty->assign('statustext', lang('status'));
    $smarty->assign('statuses', array_flip($statusdropdown));
}

$contentops = cmsms()->GetContentOperations();
$smarty->assign('preview_page_selector', $contentops->CreateHierarchyDropdown('', $this->GetPreference('detail_returnid', -1), $id.'previewpage'));

// get the list of detail templates.
try {
    $type = CmsLayoutTemplateType::load($this->GetName() . '::detail');
    $templates = $type->get_template_list();
    $list = array();
    if ($templates && is_array($templates)) {
        foreach ($templates as $template) {
            $list[$template->get_id()] = $template->get_name();
        }
    }
    if ($list) {
        $smarty->assign('prompt_detail_template', $this->Lang('detail_template'));
        $smarty->assign('prompt_detail_page', $this->Lang('detail_page'));
        $smarty->assign('detail_templates', $list);
        $smarty->assign('cur_detail_template', $this->GetPreference('current_detail_template'));
        $smarty->assign('start_tab_preview', $this->StartTab('preview', $params));
        $smarty->assign('end_tab_preview', $this->EndTab());
    }
} catch( Exception $e ) {
    audit('', $this->GetName(), 'No detail templates available for preview');
}
echo $this->ProcessTemplate('editarticle.tpl');
?>
