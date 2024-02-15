<?php
if (!isset($gCms)) exit;

if (!$this->CheckPermission('Modify News')) return;

if (isset($params['cancel'])) $this->Redirect($id, 'defaultadmin', $returnid);

/*--------------------
 * Variables
 ---------------------*/
$now = time();
$me           = $this->GetName();
$status       = 'draft';
if ($this->CheckPermission('Approve News')) $status = 'published';

$articleid    = isset($params['articleid']) ? $params['articleid'] : '';
$author_id    = isset($params['author_id']) ? $params['author_id'] : '-1';
$content      = isset($params['content']) ? $params['content'] : '';
$ndays        = (int)$this->GetPreference('expiry_interval', 180);
if ($ndays == 0) $ndays = 180;
//TODO default to recorded $enddate value if any & > $now
$enddate      = strtotime(sprintf("+%d days", $ndays), $now);
$extra        = isset($params['extra']) ? trim(strip_tags($params['extra'])) : '';
$news_url     = isset($params['news_url']) ? $params['news_url'] : '';
$postdate     = $now;
$searchable   = isset($params['searchable']) ? (int)$params['searchable'] : 1;
$startdate    = $now;
$status       = isset($params['status']) ? $params['status'] : $status;
$summary      = isset($params['summary']) ? $params['summary'] : '';
$title        = isset($params['title']) ? trim(strip_tags($params['title'])) : '';
$usedcategory = isset($params['category']) ? $params['category'] : '';
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

if (isset($params['submit']) || isset($params['apply'])) {
    $error = FALSE;
    if (empty($title)) {
        $error = $this->Lang('notitlegiven');
    } else if (empty($content)) {
        $error = $this->Lang('nocontentgiven');
    } else if ($useexp) {
        if ($startdate >= $enddate) {
            $error = $this->Lang('error_invaliddates');
        }
    }

    if ($useexp) {
        $startdate = trim($db->DbTimeStamp($startdate), "'");
        $enddate = trim($db->DbTimeStamp($enddate), "'");
    }

    if ($error === FALSE && $news_url) {
        // check for starting or ending slashes
        if (startswith($news_url, '/') || endswith($news_url, '/')) {
            $error = $this->Lang('error_invalidurl');
        }
        if ($error === FALSE) {
            // check for invalid chars.
            $translated = munge_string_to_url($news_url, FALSE, TRUE);
            if (strtolower($translated) != strtolower($news_url)) {
                $error = $this->Lang('error_invalidurl');
            }
        }
        if ($error === FALSE) {
            // make sure this url isn't taken.
            cms_route_manager::load_routes();
            $route = cms_route_manager::find_match($news_url, TRUE);
            if ($route) {
                $dflts = $route->get_defaults();
                if ($route['key1'] != $me || !isset($dflts['articleid']) || $dflts['articleid'] != $articleid) {
                    // any other matching route is bad.
                    $error = $this->Lang('error_invalidurl');
                }
            }
        }
    }

    if (!$error) {
        //
        // database work
        //
        $query = 'UPDATE ' . CMS_DB_PREFIX . 'module_news SET news_title=?, news_data=?, summary=?, status=?, news_date=?, news_category_id=?, start_time=?, end_time=?, modified_date=?, news_extra=?, news_url = ?, searchable = ? WHERE news_id = ?';
        $args = array(
            $title,
            $content,
            $summary,
            $status,
            trim($db->DBTimeStamp($postdate), "'"),
            $usedcategory,
            NULL, // undefined DT value in db
            NULL, // ditto
            trim($db->DBTimeStamp(time()), "'"),
            $extra,
            $news_url,
            $searchable,
            $articleid
        );
        if ($useexp) {
            $args[6] = $startdate;
            $args[7] = $enddate;
        }
        $db->Execute($query, $args);
        // reliable update-query check
        if (!($db->Affected_Rows() == 1 || $db->ErrorNo() == 0)) {
            die('FATAL SQL ERROR: ' . $db->ErrorMsg() . '<br>QUERY: ' . $db->sql);
        }
        //
        //Update custom fields
        //

        // get the field types
        $query = "SELECT id,name,type FROM " . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='file'";
        $types = $db->GetArray($query);

        $error = FALSE;
        if ($types) {
            foreach ($types as $onetype) {
                $elem = $id . 'customfield_' . $onetype['id'];
                if (isset($_FILES[$elem]) && $_FILES[$elem]['name'] != '') {
                    if ($_FILES[$elem]['error'] != 0 || $_FILES[$elem]['tmp_name'] == '') {
                        $error = $this->Lang('error_upload');
                    } else {
                        $error = '';
                        $value = news_admin_ops::handle_upload($articleid, $elem, $error);
                        $smarty->assign('checking', 'blah');
                        if ($value !== FALSE) {
                            $params['customfield'][$onetype['id']] = $value;
                        }
                    }
                }
            }
        }

        if (isset($params['customfield']) && !$error) {
            $now = $db->DbTimeStamp(time());
            foreach ($params['customfield'] as $fldid => $value) {
                // first check if it's available
                $query = "SELECT value FROM " . CMS_DB_PREFIX . "module_news_fieldvals WHERE news_id = ? AND fielddef_id = ?";
                $tmp = $db->GetOne($query, array(
                    $articleid,
                    $fldid
                ));
                $dbr = TRUE;
                if ($tmp === FALSE) {
                    if (!empty($value)) {
                        $query = 'INSERT INTO ' . CMS_DB_PREFIX . "module_news_fieldvals (news_id,fielddef_id,value,create_date,modified_date) VALUES (?,?,?,$now,$now)";
                        $dbr = $db->Execute($query, array(
                            $articleid,
                            $fldid,
                            $value
                        ));
                    }
                } else {
                    if (empty($value)) {
                        $query = 'DELETE FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id = ? AND fielddef_id = ?';
                        $dbr = $db->Execute($query, array(
                            $articleid,
                            $fldid
                        ));
                    } else {
                        $query = 'UPDATE ' . CMS_DB_PREFIX .
"module_news_fieldvals SET value = ?, modified_date = $now WHERE news_id = ? AND fielddef_id = ?";
                        $db->Execute($query, array(
                            $value,
                            $articleid,
                            $fldid
                        ));
                        $dbr = ($db->Affected_Rows() > 0 || $db->ErrorNo() == 0);
                    }
                }
                if (!$dbr) {
                    die('FATAL SQL ERROR: ' . $db->ErrorMsg() . '<br>QUERY: ' . $db->sql);
                }
            }
        }
    }

    if (isset($params['delete_customfield']) && is_array($params['delete_customfield']) && !$error) {
        foreach ($params['delete_customfield'] as $k => $v) {
            if ($v != 'delete')
                continue;
            $query = 'DELETE FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id = ? AND fielddef_id = ?';
            $db->Execute($query, array(
                $articleid,
                $k
            ));
        }
    }

    if (!$error && $status == 'published' && $news_url) {
        news_admin_ops::delete_static_route($articleid);
        news_admin_ops::register_static_route($news_url, $articleid);
    }

    //Update search index
    if (!$error) {
        $module = cms_utils::get_search_module();
        if (is_object($module)) {
            if ($status == 'draft' || !$searchable) {
                $module->DeleteWords($me, $articleid, 'article');
            } else {
                if (!$useexp || ($enddate > time()) || $this->GetPreference('expired_searchable', 1) == 1) {
                    $text = '';
                }

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

        \CMSMS\HookManager::do_hook('News::NewsArticleEdited', array(
            'news_id' => $articleid,
            'category_id' => $usedcategory,
            'title' => $title,
            'content' => $content,
            'summary' => $summary,
            'status' => $status,
            'start_time' => $startdate,
            'end_time' => $enddate,
            'post_time' => $postdate,
            'extra' => $extra,
            'useexp' => $useexp,
            'news_url' => $news_url
        ));
        // put mention into the admin log
        audit($articleid, $me.' article', "Edited: $title");
    }// no error

    if (isset($params['apply']) && isset($params['ajax'])) {
        if (empty($error)) {
            $out = array('response' => 'Success', 'details' => $this->Lang('articleupdated'));
        } else {
            $out = array('response' => 'Error', 'details' => $error);
        }
        $flags = JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
        if (defined('JSON_INVALID_UTF8_IGNORE')) {
            $flags |= JSON_INVALID_UTF8_IGNORE;
        }

        $handlers = ob_list_handlers();
        for ($cnt = 0; $cnt < count($handlers); $cnt++) { ob_end_clean(); }

        echo json_encode($out, $flags);
        exit;
    }

    if (!isset($params['apply']) && !$error) {
        // redirect out of here.
        $this->SetMessage($this->Lang('articlesubmitted'));
        $this->Redirect($id, 'defaultadmin', $returnid);
        return;
    }

    if ($error) {
        echo $this->ShowErrors($error);
    }
// end submit or apply
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
        if ($detail_returnid <= 0)
            $detail_returnid = ContentOperations::get_instance()->GetDefaultContent();
        if (isset($params['previewpage']) && (int)$params['previewpage'] > 0)
            $detail_returnid = (int)$params['previewpage'];

        $_SESSION['news_preview'] = array(
            'fname' => basename($tmpfname),
            'checksum' => md5_file($tmpfname)
        );
        $tparms = array('preview' => md5(serialize($_SESSION['news_preview'])));
        if (isset($params['detailtemplate']))
            $tparms['detailtemplate'] = trim($params['detailtemplate']);
        $url = $this->create_url('_preview_', 'detail', $detail_returnid, $tparms, TRUE);
        $url = str_replace('&amp;', '&', $url);
        $out = array('response' => 'Success', 'details' => $url);
        $flags = 0;
    } else {
        $out = array('response' => 'Error', 'details' => $error);
        $flags = JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
        if (defined('JSON_INVALID_UTF8_IGNORE')) { //PHP 7.2+
            $flags |= JSON_INVALID_UTF8_IGNORE;
        }
    }

    $handlers = ob_list_handlers();
    for ($cnt = 0; $cnt < count($handlers); $cnt++) { ob_end_clean(); }

    echo json_encode($out, $flags);
    exit;
} else {
    //
    // Load data from database
    //
    $query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news WHERE news_id = ?';
    $row = $db->GetRow($query, array($articleid));

    if ($row) {
        // sanitize relevant fields, whose content might be from an untrustworthy source
        $title        = $row['news_title'] ? news_ops::execSpecialize($row['news_title']) : (string)$row['news_title'];
        $content      = $row['news_data'] ? news_ops::execSpecialize($row['news_data']) : (string)$row['news_data'];
        $extra        = $row['news_extra'] ? news_ops::execSpecialize($row['news_extra']) : $row['news_extra']; // TODO CHECK FORMAT
        $summary      = $row['summary'] ? news_ops::execSpecialize($row['summary']) : (string)$row['summary'];
        $news_url     = $row['news_url']; //TODO handle untrusted e.g. external href
        $status       = $row['status'];
        $usedcategory = $row['news_category_id'];
        $postdate     = $db->UnixTimeStamp($row['news_date']);
        $startdate    = $db->UnixTimeStamp($row['start_time']);
        $author_id    = $row['author_id'];
        $searchable   = $row['searchable'];
        $useexp = 0;
        if (isset($row['end_time'])) {
            $useexp  = 1;
            $enddate = $db->UnixTimeStamp($row['end_time']);
        }
    }
}

$statusdropdown = array();
$statusdropdown[$this->Lang('draft')] = 'draft';
$statusdropdown[$this->Lang('published')] = 'published';

$categorylist = array();
$query = 'SELECT news_category_id,long_name FROM ' . CMS_DB_PREFIX . 'module_news_categories ORDER BY hierarchy';
$rst = $db->Execute($query);
if ($rst) {
    while ($row = $rst->FetchRow()) {
        if ($row['long_name'] === NULL) $row['long_name'] = '';
        $categorylist[$row['long_name']] = $row['news_category_id'];
    }
    $rst->Close();
}

/*--------------------
 * Custom fields logic
 ---------------------*/

// Get the field values
$fieldvals = array();
$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id = ?';
$tmp = $db->GetArray($query, array($articleid));
if (is_array($tmp)) {
    foreach ($tmp as $one) {
        $fieldvals[$one['fielddef_id']] = $one;
    }
}

$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_fielddefs ORDER BY item_order';
$rst = $db->Execute($query);
$custom_flds = array();
if ($rst) {
    while ($row = $rst->FetchRow()) {
        if (isset($row['extra']) && $row['extra']) $row['extra'] = unserialize($row['extra']);

        $options = [];
        if (isset($row['extra']['options'])) $options = $row['extra']['options'];

        $value = '';
        if (isset($fieldvals[$row['id']])) $value = $fieldvals[$row['id']]['value'];
        $value = isset($params['customfield'][$row['id']]) && in_array($params['customfield'][$row['id']], $params['customfield']) ? $params['customfield'][$row['id']] : $value;

        if ($row['type'] == 'file') {
            $name = "customfield_" . $row['id'];
        } else {
            $name = "customfield[" . $row['id'] . "]";
        }

        $obj = new stdClass();

        $obj->value    = $value;
        $obj->nameattr = $id . $name;
        $obj->type     = $row['type'];
        $obj->idattr   = 'customfield_' . $row['id'];
        $obj->prompt   = $row['name'];
        $obj->size     = min(80, $row['max_length']);
        $obj->max_len  = max(1, (int)$row['max_length']);
        $obj->delete   = $id . 'delete_customfield[' . $row['id'] . ']';
        $obj->options  = $options;
/* FIXME - If we create inputs with hmtl markup in smarty template, what's the use of switch and form API here?
        switch( $row['type'] ) {
            case 'textbox' :
                $size = min(50, $row['max_length']);
                $obj->field = $this->CreateInputText($id, $name, $value, $size, $row['max_length']);
                break;
            case 'checkbox' :
                $obj->field = $this->CreateInputHidden($id, $name, 0) . $this->CreateInputCheckbox($id, $name, 1, (int)$value);
                break;
            case 'textarea' :
                $obj->field = $this->CreateTextArea(true, $id, $value, $name);
                break;
            case 'file' :
                $del = '';
                if ($value != '') {
                    $deln = 'delete_customfield[' . $row['id'] . ']';
                    $del = '&nbsp;' . $this->Lang('delete') . $this->CreateInputCheckbox($id, $deln, 'delete');
                }
                $obj->field = $value . '&nbsp;' . $this->CreateFileUploadInput($id, $name) . $del;
                break;
            case 'dropdown' :
                $obj->field = $this->CreateInputDropdown($id, $name, array_flip($options), -1, $value);
                break;
        }
*/
        $custom_flds[$row['name']] = $obj;
    }
    $rst->Close();
}

/*--------------------
 * Pass everything to smarty
 ---------------------*/

if ($author_id > 0) {
    $userops = $gCms->GetUserOperations();
    $theuser = $userops->LoadUserById($author_id);
    $smarty->assign('inputauthor', $theuser->username);
} else if ($author_id == 0) {
    $smarty->assign('inputauthor', $this->Lang('anonymous'));
} else {
    $feu = $this->GetModuleInstance('FrontEndUsers');
    if ($feu) {
        $uinfo = $feu->GetUserInfo($author_id * -1);
        if ($uinfo[0])
            $smarty->assign('inputauthor', $uinfo[1]['username']);
    }
}

$smarty->assign('formid', $id);
$smarty->assign('startform', $this->CreateFormStart($id, 'editarticle', $returnid, 'POST', 'multipart/form-data'));
$smarty->assign('endform', $this->CreateFormEnd());
$smarty->assign('hide_summary_field', $this->GetPreference('hide_summary_field', '0'));
$smarty->assign('authortext', $this->Lang('author'));
$smarty->assign('articleid', $articleid);
$smarty->assign('titletext', $this->Lang('title'));
$smarty->assign('searchable', $searchable);
$smarty->assign('extratext', $this->Lang('extra'));
$smarty->assign('extra', $extra);
$smarty->assign('urltext', $this->Lang('url'));
$smarty->assign('news_url', $news_url);
$smarty->assign('title', $title);
$smarty->assign('inputcontent', CmsFormUtils::create_textarea([
    'enablewysiwyg' => 1,
    'name' => $id . 'content',
    'text' => $content,
    'rows' => 10,
    'cols' => 80
]));
$smarty->assign('inputsummary', CmsFormutils::create_textarea([
    'enablewysiwyg' => $this->GetPreference('allow_summary_wysiwyg', 1),
    'name' => $id . 'summary',
    'text' => $summary,
    'rows' => 3,
    'cols' => 80
]));
$smarty->assign('useexp', $useexp);
$smarty->assign('actionid', $id);
$smarty->assign('inputexp', $this->CreateInputCheckbox($id, 'useexp', '1', $useexp, 'class="pagecheckbox"'));
$smarty->assign('postdate', $postdate);
$smarty->assign('postdateprefix', $id . 'postdate_');
$smarty->assign('startdate', $startdate);
$smarty->assign('startdateprefix', $id . 'startdate_');
$smarty->assign('enddate', $enddate);
$smarty->assign('enddateprefix', $id . 'enddate_');
$smarty->assign('status', $status);
$smarty->assign('categorylist', array_flip($categorylist));
$smarty->assign('category', $usedcategory);
$smarty->assign('hidden', $this->CreateInputHidden($id, 'articleid', $articleid) . $this->CreateInputHidden($id, 'author_id', $author_id));
$smarty->assign('submit', $this->CreateInputSubmit($id, 'submit', lang('submit')));
$smarty->assign('apply', $this->CreateInputSubmit($id, 'apply', lang('apply')));
$smarty->assign('cancel', $this->CreateInputSubmit($id, 'cancel', lang('cancel')));
$smarty->assign('delete_field_val', $this->Lang('delete'));
$smarty->assign('titletext', $this->Lang('title'));
$smarty->assign('extratext', $this->Lang('extra'));
$smarty->assign('categorytext', $this->Lang('category'));
$smarty->assign('summarytext', $this->Lang('summary'));
$smarty->assign('contenttext', $this->Lang('content'));
$smarty->assign('postdatetext', $this->Lang('postdate'));
$smarty->assign('useexpirationtext', $this->Lang('useexpiration'));
$smarty->assign('startdatetext', $this->Lang('startdate'));
$smarty->assign('enddatetext', $this->Lang('enddate'));
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

if ($this->CheckPermission('Approve News')) {
    $smarty->assign('statustext', lang('status'));
    $smarty->assign('statuses', array_flip($statusdropdown));
}

if ($custom_flds) {
    $smarty->assign('custom_fields', $custom_flds);
}
$contentops = cmsms()->GetContentOperations();
$smarty->assign('preview_page_selector', $contentops->CreateHierarchyDropdown(0, $this->GetPreference('detail_returnid', -1), $id.'previewpage', TRUE));

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
        $smarty->assign('prompt_detail_template', $this->Lang('detail_template'));
        $smarty->assign('prompt_detail_page', $this->Lang('detail_page'));
        $smarty->assign('detail_templates', $list);
        $smarty->assign('cur_detail_template', $this->GetPreference('current_detail_template'));
        $smarty->assign('start_tab_preview', $this->StartTab('preview', $params));
        $smarty->assign('end_tab_preview', $this->EndTab());
    }
} catch( Exception $e ) {
    audit('', $me.':editarticle', 'No detail template available for preview');
}

// and display the template.
echo $this->ProcessTemplate('editarticle.tpl');
