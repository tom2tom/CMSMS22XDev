<?php
#CMSMS News module action: editarticle
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify News')) return;

if (isset($params['cancel'])) $this->Redirect($id, 'defaultadmin', $returnid);

/*--------------------
 Variables
 ---------------------*/
$now          = time();
$me           = $this->GetName();
$status       = ($this->CheckPermission('Approve News')) ? 'published' : 'draft';
$ndays        = (int)$this->GetPreference('expiry_interval', 30);
if ($ndays <= 0) $ndays = 30;

$articleid    = (isset($params['articleid'])) ? (int)$params['articleid'] : 0;
$author_id    = (isset($params['author_id'])) ? (int)$params['author_id'] : -1;
$content      = (isset($params['content'])) ? news_ops::execSpecialize(trim($params['content'])) : '';
$extra        = (isset($params['extra'])) ? news_ops::execSpecialize(trim($params['extra'])) : '';
$image_url    = (isset($params['image_url'])) ? $params['image_url'] : ''; // sanitized below
$news_url     = (isset($params['news_url'])) ? $params['news_url'] : ''; // sanitized below
$searchable   = !empty($params['searchable']);
$status       = (isset($params['status'])) ? $params['status'] : $status;
$summary      = (isset($params['summary'])) ? news_ops::execSpecialize(trim($params['summary'])) : '';
$title        = (isset($params['title'])) ? news_ops::execSpecialize(trim($params['title'])) : '';
$usedcategory = (isset($params['category'])) ? (int)$params['category'] : $this->GetPreference('default_category', 1);
$useexp       = !empty($params['useexp']);
$postdate     = (isset($params['postdate_Month'])) ?
    mktime($params['postdate_Hour'], $params['postdate_Minute'], $params['postdate_Second'], $params['postdate_Month'], $params['postdate_Day'], $params['postdate_Year']) :
    $now;
$startdate    = (isset($params['startdate_Month'])) ?
    mktime($params['startdate_Hour'], $params['startdate_Minute'], $params['startdate_Second'], $params['startdate_Month'], $params['startdate_Day'], $params['startdate_Year']) :
    $now;
$enddate      = (isset($params['enddate_Month'])) ?
    mktime($params['enddate_Hour'], $params['enddate_Minute'], $params['enddate_Second'], $params['enddate_Month'], $params['enddate_Day'], $params['enddate_Year']) :
    strtotime(sprintf('+%d days', $ndays), $now);

/*--------------------
 Logic
 ---------------------*/

if (isset($params['submit']) || isset($params['apply'])) {
    $error = FALSE;
    if (!$title) {
        $error = $this->Lang('notitlegiven');
    } elseif (!$content) {
        $error = $this->Lang('nocontentgiven');
    } elseif ($useexp) {
        if ($startdate >= $enddate) {
            $error = $this->Lang('error_invaliddates');
        }
    }

    if ($useexp) {
        $startdate = trim($db->DBTimeStamp($startdate), "'");
        $enddate = trim($db->DBTimeStamp($enddate), "'");
    }

    if (!$error && $image_url) {
        $res = cms_utils::validate_url($image_url, 'image');
        if ($res !== TRUE) {
            $error = $res;
        }
    }

    if (!$error && $news_url) {
        if ($news_url[0] == '/') { // trailing '/' ok
            $error = $this->Lang('error_invalidurl');
        } else {
            // check for other invalid chars.
            $translated = cms_utils::cleanUrlPath($news_url);
            if ($translated != $news_url) {
                $error = $this->Lang('error_invalidurl');
            }
        }

        if (!$error) {
            // make sure this url isn't taken.
            cms_route_manager::load_routes();
            $route = cms_route_manager::find_match($news_url, TRUE);
            if ($route) {
                $dflts = $route->get_defaults();
                if ($route['key1'] != $me || !isset($dflts['articleid']) || $dflts['articleid'] != $articleid) {
                    // any other matching route is bad.
                    $error = $this->Lang('error_urlused');
                }
            }
        }
    }

    if (!$error) {
        //
        // database work
        //
        $query = 'UPDATE ' . CMS_DB_PREFIX . 'module_news SET news_category_id=?,news_title=?,news_data=?,summary=?,status=?,icon=?,news_date=?,start_time=?,end_time=?,modified_date=?,news_extra=?,news_url=?,searchable=? WHERE news_id=?';
        $args = array(
            $usedcategory,
            $title,
            $content,
            $summary,
            $status,
            news_ops::storeformat_url($image_url),
            trim($db->DBTimeStamp($postdate), "'"),
            NULL, // undefined DT value in db
            NULL, // ditto
            trim($db->DBTimeStamp($now), "'"),
            $extra,
            $news_url,
            ($searchable) ? 1 : 0,
            $articleid
        );
        if ($useexp) {
            $args[7] = $startdate;
            $args[8] = $enddate;
        }
        $db->Execute($query, $args);
        // reliable update-query check
        if (!($db->Affected_Rows() == 1 || $db->ErrorNo() == 0)) {
            die('FATAL SQL ERROR: ' . $db->ErrorMsg() . '<br>QUERY: ' . $db->sql);
        }

        //Update custom fields

        // all recorded fields' data
        $currentfields = $db->GetArray('SELECT V.news_id,V.fielddef_id,D.type,V.value
FROM '.CMS_DB_PREFIX.'module_news_fieldvals V
INNER JOIN '.CMS_DB_PREFIX.'module_news_fielddefs D
ON V.fielddef_id = D.id
ORDER BY V.news_id,D.item_order');
        // fields selected via 'use this field' checkboxes
        $wantedfields = isset($params['usefield']) ? $params['usefield'] : [];
        // all recorded 'file'-field id's
        $query = 'SELECT id FROM ' . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='file'";
        $ffields = $db->GetCol($query);

        $query = 'DELETE FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id=? AND fielddef_id=?';
        foreach ($currentfields as $row) {
            if ($row['news_id'] == $articleid && (!$wantedfields || !isset( $wantedfields[$row['fielddef_id']]))) {
                $db->Execute($query, array($articleid, $row['fielddef_id']));
                if ($ffields && in_array($row['fielddef_id'], $ffields)) {
                    $p = cms_join_path($config['uploads_path'], 'news', 'id'.$articleid);  // OR 'News' if globally changed
                    if (is_dir($p)) { recursive_delete($p); }
                }
            }
        }

        $error = FALSE;

        if ($ffields) {
            // process used and formerly-used 'file' fields, which involve an input-file element and typically an uploaded file
            foreach ($ffields as $fid) {
                if ($wantedfields && !empty($wantedfields[$fid])) { // use this one
                    /*
                     the recorded value for a 'file' field is like
                       somefile.ext
                     which file will have been saved as
                       $config['uploads_path'].DIRECTORY_SEPARATOR
                      .'news'.DIRECTORY_SEPARATOR.
                       'id'.$articleid.DIRECTORY_SEPARATOR.
                       somefile.ext
                     the default displayer for that tag involves
                       <img src="{$entry->file_location}/{$field->value}"
                    */
                    $elem = $id . 'customfield_' . $fid;
                    if (isset($_FILES[$elem]) && $_FILES[$elem]['name'] != '') {
                        // a new upload
                        if ($_FILES[$elem]['error'] != 0 || $_FILES[$elem]['tmp_name'] == '') { // 4 = no upload, 0 might be an attack
                            $error = $this->Lang('error_upload');
                        } else {
                            $error = '';
                            $value = news_admin_ops::handle_upload($articleid, $elem, $error); //file basename or false
                            if ($value !== FALSE) {
                                $params['customfield'][$fid] = $value;
                            }
                            // was it formerly a different file?
                            if (!empty($params['currentfile'][$fid])) {
                                if ($params['currentfile'][$fid] != $value) { // != FALSE ok here
                                    $p = cms_join_path($config['uploads_path'], 'news', 'id'.$articleid, $params['currentfile'][$fid]); // OR 'News' if globally changed
                                    if (is_file($p)) { unlink($p); }
                                }
                            }
                        }
                    } elseif (isset($_FILES[$elem]) && $_FILES[$elem]['error'] != 4) {
                        $error = $this->Lang('error_upload');
                    } else {
                        // no new upload = nothing to do
                    }
                } else { // (now)unwanted field
                    if (!empty($params['currentfile'][$fid])) {
                       // it was used before
                       $p = cms_join_path($config['uploads_path'], 'news', 'id'.$articleid, $params['currentfile'][$fid]); // OR 'News' if globally changed
                       if (is_file($p)) { unlink($p); }
                    }
                }
            }
        }

        if (isset($params['customfield']) && !$error) {
            // cache the potential wanted linkedfile fields
            $query = 'SELECT id FROM ' . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='linkedfile'";
            $lfields = $db->GetCol($query);
            $longnow = $db->DBTimeStamp($now);
            foreach ($params['customfield'] as $fldid => $value) {
                if ($wantedfields && !empty($wantedfields[$fldid])) { // use this one
                    if ($lfields && in_array($fldid, $lfields)) {
                        /*
                        the default FipePicker-generated value for a 'linkedfile' field is like
                          /uploads/somepath/to/somefile.ext
                        but the user can change that or enter/paste some other url altogether
                        the default displayer for a 'linkedfile' field involves
                          {file_url file=$field->value}
                        and that tag looks for $config['uploads_path'][/dir parameter]/file parameter
                        so the recorded value needs adjustment (strip '/uploads') as well as sanitisation
                        */
                        if ($value) {
                            $tmp = news_ops::check_linkedfile($value, $config['uploads_path']);
                            if ($tmp != $value) {
                                //do stuff
                                $value = $tmp;
                            }
                        } else {
                            //not really wanted ...
                            $db->Execute('DELETE FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id=? AND fielddef_id=?',
                               array($articleid, $fldid));
                            continue;
                        }
                    }

                    $dbr = TRUE;
                    // check whether the field is already recorded for this article
                    $query = 'SELECT news_id FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id=? AND fielddef_id=?';
                    $exists = $db->GetOne($query, array($articleid,$fldid));
                    if (!$exists) {
                        $query = 'INSERT INTO ' . CMS_DB_PREFIX .
"module_news_fieldvals (news_id,fielddef_id,value,create_date,modified_date) VALUES (?,?,?,$longnow,$longnow)";
                        $dbr = $db->Execute($query, array(
                            $articleid,
                            $fldid,
                            $value
                        ));
                    } else {
                        $query = 'UPDATE ' . CMS_DB_PREFIX .
"module_news_fieldvals SET value=?,modified_date=$longnow WHERE news_id=? AND fielddef_id=?";
                        $db->Execute($query, array(
                            $value,
                            $articleid,
                            $fldid
                        ));
                        $dbr = ($db->Affected_Rows() > 0 || $db->ErrorNo() == 0);
                    }
                    if (!$dbr) {
                        die('FATAL SQL ERROR: ' . $db->ErrorMsg() . '<br>QUERY: ' . $db->sql);
                    }
                } else { // unwanted field
                    // delete field data if any (should have been done in loop above)
                    $db->Execute('DELETE FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id=? AND fielddef_id=?',
                        array($articleid, $fldid));
                }
            }
        }
    }

    if (!$error && $status == 'published' && $news_url) {
        // TODO this refresh only if article not expired
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

        CMSMS\HookManager::do_hook('News::NewsArticleEdited', array(
            'news_id' => $articleid,
            'category_id' => $usedcategory,
            'title' => $title,
            'content' => $content,
            'summary' => $summary,
            'status' => $status,
            'icon' => $image_url,
            'start_time' => $startdate,
            'end_time' => $enddate,
            'post_time' => $postdate,
            'extra' => $extra,
            'useexp' => $useexp,
            'news_url' => $news_url
        ));
        // put mention into the admin log
        audit($articleid, $me.' article', "Edited: $title");
    } // no error

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
        $this->ShowErrors($error);
    }
    // end submit or apply
} elseif (isset($params['preview'])) {
    if (empty($error)) {
        // save data for preview
        unset($params['apply']);
        unset($params['preview']);
        unset($params['submit']);
        unset($params['cancel']);
        unset($params['ajax']);
        // without unwanted fields
        $wantedfields = isset($params['usefield']) ? $params['usefield'] : [];
        $currentfields = isset($params['customfield']) ? $params['customfield'] : []; // non-'file' fields
        foreach ($currentfields as $fid => $val) {
            if (!$wantedfields || !isset($wantedfields[$fid])) {
                unset($currentfields[$fid]);
            }
        }
        $params['customfield'] = $currentfields;

        $ffields = preg_filter('/^customfield_(\d+)$/', '$1', array_keys($params)); // new 'file' fields
        foreach ($ffields as $i => $fid) {
            if (!$wantedfields || !isset($wantedfields[$fid])) {
                unset($ffields[$i]);
                unset($params['customfield_'.$fid]);
            }
        }

        $ffields = isset($params['currentfile']) ? $params['currentfile'] : []; // recorded 'file' fields
        foreach ($ffields as $fid => $val) {
            if (!$wantedfields || !isset($wantedfields[$fid])) {
//                unset($ffields[$fid]);
//                unset($params['currentfile'][$fid]);
            } else {
                $params['customfield_'.$fid] = $val;
            }
        }
        unset($params['currentfile']);
        unset($params['usefield']);

        $tmpfname = tempnam(TMP_CACHE_LOCATION, $me . '_preview');
        file_put_contents($tmpfname, serialize($params));

        $detail_returnid = $this->GetPreference('detail_returnid', -1);
        if ($detail_returnid <= 0) {
            // get the default content id.
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
    $query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news WHERE news_id=?';
    $row = $db->GetRow($query, array($articleid));

    if ($row) {
        $title        = $row['news_title'] ? news_ops::execSpecialize($row['news_title']) : (string)$row['news_title'];
        $content      = $row['news_data'] ? news_ops::execSpecialize($row['news_data']) : (string)$row['news_data'];
        $extra        = $row['news_extra'] ? news_ops::execSpecialize($row['news_extra']) : $row['news_extra'];
        $summary      = $row['summary'] ? news_ops::execSpecialize($row['summary']) : (string)$row['summary'];
        $news_url     = $row['news_url'];
        $status       = $row['status'];
        $image_url    = $row['icon'];
        $usedcategory = $row['news_category_id'];
        $postdate     = $db->UnixTimeStamp($row['news_date']);
        $startdate    = $db->UnixTimeStamp($row['start_time']);
        $author_id    = (int)$row['author_id'];
        $searchable   = (bool)$row['searchable'];
        $useexp = false;
        if (isset($row['end_time'])) {
            $useexp  = true;
            $enddate = $db->UnixTimeStamp($row['end_time']);
        }
    }
}

$statusdropdown = array();
$statusdropdown[$this->Lang('draft')] = 'draft';
$statusdropdown[$this->Lang('published')] = 'published';

// Categories list
$query = 'SELECT COALESCE(long_name,news_category_name),news_category_id FROM ' . CMS_DB_PREFIX . 'module_news_categories ORDER BY hierarchy';
$categorylist = $db->GetAssoc($query);

/*--------------------
 Custom fields logic
 ---------------------*/

// Get current field values (maybe none even if the next query finds fields)
$fieldvals = array();
$query = 'SELECT news_id,fielddef_id,value FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id=?';
$tmp = $db->GetArray($query, array($articleid));
if (is_array($tmp)) {
    foreach ($tmp as $one) {
        $fieldvals[$one['fielddef_id']] = $one;
    }
}

// Populate
$custom_flds = array();
$query = 'SELECT id,name,type,extra FROM ' . CMS_DB_PREFIX . 'module_news_fielddefs ORDER BY item_order';
$fldefs = $db->GetArray($query);
if ($fldefs) {
    foreach ($fldefs as $row) {
        foreach (['name', 'type', 'extra'] as $fld) {
            if ($row[$fld] === null) {
                $row[$fld] = '';
            }
        }
        $fid = $row['id'];
        if ($row['type'] == 'file') {
            $name = "customfield_$fid";
        } else {
            $name = "customfield[$fid]";
        }

        $obj = new stdClass();
        $obj->id       = $fid;
        $obj->type     = $row['type'];
        $obj->nameattr = $id . $name;
        $obj->idattr   = "customfield_$fid";
        $obj->prompt   = $row['name'];
//      $obj->delete   = $id . "delete_customfield[$fid]";
        $value = (isset($fieldvals[$fid])) ? $fieldvals[$fid]['value'] : '';
        $obj->value    = $value;
        $obj->options  = []; // might be replaced below

        if (!empty($row['extra'])) {
            $row['extra'] = unserialize($row['extra'], array('allowed_classes' => false));
            if ($row['extra'] !== false) {
                foreach ($row['extra'] as $prop => $pval) {
                    switch ($prop) {
                      case 'max_length':
                        $ms = (int)$pval;
                        $obj->max_len = max(1, $ms); //for input text, really
                        if (!isset($obj->size)) $obj->size = min(50, $ms);
                        break;
                      case 'size':
                        $ms = (int)$pval;
                        $obj->size = min(50, $ms);
                        break;
                      default:
                        $obj->$prop = $pval;
                    }
                }
            }
        }
        $custom_flds[$row['name']] = $obj;
    }
}

$dir = $config['image_uploads_path']; // append /news or /News ?
$data = $image_url;

$filepicker = cms_utils::get_filepicker_module();
$userid = get_userid(false);
$profile = $filepicker->get_default_profile($dir, $userid);
$parms = ['top'=>$dir, 'type'=>'image']; // aka CMSMS\FileType::TYPE_IMAGE BUT type is not enforced in the picker
$profile->overrideWith($parms); // TODO other property-overrides ? not writability
$input = $filepicker->get_html($id.'image_url', $data, $profile);
preg_match('/id="(.+?)"/', $input, $matches);
$inputid = $matches[1];

CMSMS\HookManager::add_hook('admin_add_headtext', function() {
    $root_url = CMS_ROOT_URL;
    return "<script src=\"$root_url/lib/jquery/js/jquery.cmsms_dirtyform.js\" defer></script>\n";
});

/*--------------------
 Pass everything to template
 ---------------------*/

$tpl = $smarty->createTemplate("module_file_tpl:$me;editarticle.tpl", null, $me, $smarty);
if ($author_id > 0) {
    $userops = $gCms->GetUserOperations();
    $theuser = $userops->LoadUserById($author_id);
    $tpl->assign('inputauthor', $theuser->username);
} elseif ($author_id == 0) {
    $tpl->assign('inputauthor', $this->Lang('anonymous'));
} else {
    // < 0 indicates this article was submitted by logged-in feu
    $tmp = '';
    $feu = $this->GetModuleInstance('MAMS');
    if (!$feu) {
        $feu = $this->GetModuleInstance('FrontEndUsers');
    }
    if ($feu) {
        $uinfo = $feu->GetUserInfo(-(int)$author_id);
        if ($uinfo && $uinfo[0]) {
            $tmp = $uinfo[1]['username'];
        }
    }
    $tpl->assign('inputauthor', $tmp);
}

$tpl->assign('tab_preview', true); // show a preview tab
$tpl->assign('formid', $id);
$tpl->assign('startform', $this->CreateFormStart($id, 'editarticle', $returnid, 'post', 'multipart/form-data', false, '',
    ['articleid'=>$articleid,
     'author_id'=>$author_id]));
$tpl->assign('endform', $this->CreateFormEnd());
$tpl->assign('authortext', $this->Lang('author'));
$tpl->assign('articleid', $articleid);
$tpl->assign('titletext', $this->Lang('title'));
$tpl->assign('title', $title);
$tpl->assign('extratext', $this->Lang('extra'));
$tpl->assign('extra', $extra);
$tpl->assign('imagetext', $this->Lang('image'));
$tpl->assign('imageinput', $input);
$tpl->assign('imageinputid', $inputid);
$tpl->assign('urltext', $this->Lang('url'));
$tpl->assign('news_url', $news_url);
$tpl->assign('inputcontent', CmsFormUtils::create_textarea([
    'enablewysiwyg' => true,
    'name' => $id . 'content',
    'text' => $content,
    'rows' => 10,
    'cols' => 60
]));
$tpl->assign('hide_summary_field', $this->GetPreference('hide_summary_field', 0));
$val = $this->GetPreference('allow_summary_wysiwyg', 1);
$tpl->assign('inputsummary', CmsFormutils::create_textarea([
    'enablewysiwyg' => (bool)$val,
    'name' => $id . 'summary',
    'text' => $summary,
    'rows' => 3,
    'cols' => 60
]));
$tpl->assign('useexp', $useexp);
$tpl->assign('actionid', $id);
$tpl->assign('inputexp', $this->CreateInputCheckbox($id, 'useexp', '1', $useexp, 'class="pagecheckbox"'));
$tpl->assign('postdate', $postdate);
$tpl->assign('postdateprefix', $id . 'postdate_');
$tpl->assign('startdate', $startdate);
$tpl->assign('startdateprefix', $id . 'startdate_');
$tpl->assign('enddate', $enddate);
$tpl->assign('enddateprefix', $id . 'enddate_');
$tpl->assign('status', $status);
$tpl->assign('categorylist', array_flip($categorylist));
$tpl->assign('category', $usedcategory);
$tpl->assign('submit', $this->CreateInputSubmit($id, 'submit', lang('submit')));
$tpl->assign('apply', $this->CreateInputSubmit($id, 'apply', lang('apply')));
$tpl->assign('cancel', $this->CreateInputSubmit($id, 'cancel', lang('cancel')));
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
$tpl->assign('warning_preview', $this->Lang('warning_preview'));
if ($custom_flds) {
    $tpl->assign('custom_fields', $custom_flds);
}

if ($this->CheckPermission('Approve News')) {
    $tpl->assign('statustext', lang('status'));
    $tpl->assign('statuses', array_flip($statusdropdown));
}

$contentops = cmsms()->GetContentOperations();
$tpl->assign('preview_page_selector', $contentops->CreateHierarchyDropdown(0, $this->GetPreference('detail_returnid', -1), $id.'previewpage', true, false, false, false, false, 'seldetail'));

try {
    // detail templates for preview
    $list = array();
    $type = CmsLayoutTemplateType::load($me . '::detail');
    $templates = $type->get_template_list();
    if ($templates && is_array($templates)) {
        foreach ($templates as $template) {
            $list[$template->get_id()] = $template->get_name();
        }
    }
    if ($list) {
        $tpl->assign('detail_templates', $list);
        $tpl->assign('cur_detail_template', $this->GetPreference('current_detail_template'));
    }
} catch( Exception $e ) {
    audit('', $me.':editarticle', 'No detail template available for preview');
}

$tpl->display();
?>
