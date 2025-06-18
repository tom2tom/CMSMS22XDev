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
$me           = $this->GetName();

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
$useexp       = !empty($params['useexp']);

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
        if ($startdate >= $enddate) {
            $error = $this->ShowErrors($this->Lang('error_invaliddates'));
        }
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
        $args = array(
            $articleid,
            $usedcategory,
            $title,
            $content,
            $summary,
            $status,
            trim($db->DBTimeStamp($postdate), "'"),
            NULL, // undefined DT value in db
            NULL, // ditto
            trim($db->DBTimeStamp(time()), "'"),
            trim($db->DBTimeStamp(time()), "'"),
            $userid,
            $extra,
            $news_url,
            $searchable
        );
        if ($useexp) {
            $args[7] = trim($db->DBTimeStamp($startdate), "'");
            $aegs[8] = trim($db->DBTimeStamp($enddate), "'");
        }

        $dbr = $db->Execute($query, $args);
        if (!$dbr) {
            echo "DEBUG: SQL = " . $db->sql . "<br>";
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
                    die('FATAL SQL ERROR: ' . $db->ErrorMsg() . '<br>QUERY: ' . $db->sql);
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
                $module->AddWords($me, $articleid, 'article', $text, ($useexp == 1 && $this->GetPreference('expired_searchable', 0) == 0) ? $enddate : NULL);
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
            audit($articleid, $me.' article', "Added: $title");
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
        $tmpfname = tempnam(TMP_CACHE_LOCATION, $me . '_preview');
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
$query = "SELECT news_category_id,long_name FROM " . CMS_DB_PREFIX . "module_news_categories ORDER BY hierarchy";
$rst = $db->Execute($query);
if ($rst) {
    while ($row = $rst->FetchRow()) {
        if ($row['long_name'] === null) $row['long_name'] = '';
        $categorylist[$row['long_name']] = $row['news_category_id'];
    }
    $rst->Close();
}


// Display custom fields
$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_fielddefs ORDER BY item_order';
$dbr = $db->Execute($query);
$custom_flds = array();
if ($dbr) {
    while (($row = $dbr->FetchRow())) {
        foreach (['name','type','extra'] as $fld) {
            if ($row[$fld] === null) $row[$fld] = '';
        }
        if (isset($row['extra']) && $row['extra']) {
            $row['extra'] = unserialize($row['extra']);
            if (!$row['extra']) $row['extra'] = '';
        }

        $options = [];
        if (isset($row['extra']['options'])) {
            $options = $row['extra']['options'];
        }
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
    $dbr->Close();
}

/*--------------------
 * Pass everything to smarty
 ---------------------*/

$tpl = $smarty->createTemplate("module_file_tpl:$me;editarticle.tpl", null, $me, $smarty);
$tpl->assign('formid', $id);
$tpl->assign('hide_summary_field', $this->GetPreference('hide_summary_field', '0'));
$tpl->assign('authortext', '');
$tpl->assign('inputauthor', '');
$tpl->assign('startform', $this->CreateFormStart($id, 'addarticle', $returnid, 'post', 'multipart/form-data'));
$tpl->assign('endform', $this->CreateFormEnd());
$tpl->assign('titletext', $this->Lang('title'));
$tpl->assign('title', $title);
$tpl->assign('allow_summary_wysiwyg', $this->GetPreference('allow_summary_wysiwyg'));
$tpl->assign('extratext', $this->Lang('extra'));
$tpl->assign('extra', $extra);
$tpl->assign('urltext', $this->Lang('url'));
$tpl->assign('news_url', $news_url);
$tpl->assign('postdate', $postdate);
$tpl->assign('postdateprefix', $id . 'postdate_');
$tpl->assign('useexp', $useexp);
$tpl->assign('actionid', $id);
$tpl->assign('inputexp', $this->CreateInputCheckbox($id, 'useexp', '1', $useexp, 'class="pagecheckbox"'));
$tpl->assign('startdate', $startdate);
$tpl->assign('startdateprefix', $id . 'startdate_');
$tpl->assign('enddate', $enddate);
$tpl->assign('enddateprefix', $id . 'enddate_');
$tpl->assign('status', $status);
$tpl->assign('categorylist', array_flip($categorylist));
$tpl->assign('category', $usedcategory);
$tpl->assign('submit', $this->CreateInputSubmit($id, 'submit', lang('submit')));
$tpl->assign('cancel', $this->CreateInputSubmit($id, 'cancel', lang('cancel')));
$tpl->assign('delete_field_val', $this->Lang('delete'));
$tpl->assign('titletext', $this->Lang('title'));
$tpl->assign('categorytext', $this->Lang('category'));
$tpl->assign('summarytext', $this->Lang('summary'));
$tpl->assign('contenttext', $this->Lang('content'));
$tpl->assign('postdatetext', $this->Lang('postdate'));
$tpl->assign('useexpirationtext', $this->Lang('useexpiration'));
$tpl->assign('startdatetext', $this->Lang('startdate'));
$tpl->assign('enddatetext', $this->Lang('enddate'));
$tpl->assign('searchable', $searchable);
$tpl->assign('select_option', $this->Lang('select_option'));
// tab stuff (could be replaced by template tags)
$tpl->assign('start_tab_headers', $this->StartTabHeaders());
$tpl->assign('tabheader_article', $this->SetTabHeader('article', $this->Lang('article')));
$tpl->assign('tabheader_preview', $this->SetTabHeader('preview', $this->Lang('preview')));
$tpl->assign('end_tab_headers', $this->EndTabHeaders());
$tpl->assign('start_tab_content', $this->StartTabContent());
$tpl->assign('start_tab_article', $this->StartTab('article', $params));
$tpl->assign('end_tab_article', $this->EndTab());
$tpl->assign('end_tab_content', $this->EndTabContent());
$tpl->assign('warning_preview', $this->Lang('warning_preview'));

$parms = array(
    'enablewysiwyg' => 1,
    'name' => $id . 'content',
    'text' => $content,
    'rows' => 10,
    'cols' => 80
);
$tpl->assign('inputcontent', CmsFormUtils::create_textarea($parms));

$parms = array(
    'enablewysiwyg' => $this->GetPreference('allow_summary_wysiwyg', 1),
    'name' => $id . 'summary',
    'text' => $summary,
    'rows' => 3,
    'cols' => 80
);
$tpl->assign('inputsummary', CmsFormutils::create_textarea($parms));

if ($custom_flds)
    $tpl->assign('custom_fields', $custom_flds);

if ($this->CheckPermission('Approve News')) {
    $tpl->assign('statustext', lang('status'));
    $tpl->assign('statuses', array_flip($statusdropdown));
}

$contentops = cmsms()->GetContentOperations();
$tpl->assign('preview_page_selector', $contentops->CreateHierarchyDropdown(0, $this->GetPreference('detail_returnid', -1), $id.'previewpage', TRUE));

// get the list of detail templates.
try {
    $type = CmsLayoutTemplateType::load($me . '::detail');
    $templates = $type->get_template_list();
    $list = array();
    if ($templates && is_array($templates)) {
        foreach ($templates as $template) {
            $list[$template->get_id()] = $template->get_name();
        }
    }
    if ($list) {
        $tpl->assign('prompt_detail_template', $this->Lang('detail_template'));
        $tpl->assign('prompt_detail_page', $this->Lang('detail_page'));
        $tpl->assign('detail_templates', $list);
        $tpl->assign('cur_detail_template', $this->GetPreference('current_detail_template'));
        $tpl->assign('start_tab_preview', $this->StartTab('preview', $params));
        $tpl->assign('end_tab_preview', $this->EndTab());
    }
} catch( Exception $e ) {
    audit('', $me, 'No detail template available for preview');
}
$tpl->display();
?>
