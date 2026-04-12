<?php
#CMSMS News module action: addarticle
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

use CMSMS\FilePickerProfile;
use CMSMS\HookManager;

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
$userid       = get_userid(); // (false) ?
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

if (isset($params['submit'])) {
    $error = FALSE;
    if (!$title) {
        $error = $this->ShowErrors($this->Lang('notitlegiven'));
    } elseif (!$content) {
        $error = $this->ShowErrors($this->Lang('nocontentgiven'));
    } elseif ($useexp) {
        if ($startdate >= $enddate) {
            $error = $this->ShowErrors($this->Lang('error_invaliddates'));
        }
    }

    if (!$error && $image_url) {
        $res = cms_utils::validate_url($image_url, 'image');
        if ($res !== TRUE) {
            $error = $this->ShowErrors($res);
        }
    }

    if (!$error && $news_url) {
        if ($news_url[0] == '/') { // trailing '/' ok
            $error = $this->ShowErrors($this->Lang('error_invalidurl'));
        } else {
            // check for other invalid chars.
            $translated = cms_utils::cleanUrlPath($news_url);
            if ($translated != $news_url) {
                $error = $this->ShowErrors($this->Lang('error_invalidurl'));
            }
        }

        if (!$error) {
            // make sure this url isn't taken.
            // we're adding an article, not editing... any matching route is bad.
            cms_route_manager::load_routes();
            $route = cms_route_manager::find_match($news_url);
            if ($route) {
                $error = $this->ShowErrors($this->Lang('error_urlused'));
            }
        }
    }

    if ($error) {
        echo $error;
    } else {
        //
        // database work
        //
        $articleid = $db->GenID(CMS_DB_PREFIX . 'module_news_seq');
        $longnow = trim($db->DBTimeStamp($now), "'");
        $query = 'INSERT INTO ' . CMS_DB_PREFIX . 'module_news
(news_id,news_category_id,news_title,news_data,summary,status,icon,news_date,start_time,end_time,create_date,modified_date,author_id,news_extra,news_url,searchable)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $args = array(
            $articleid,
            $usedcategory,
            $title,
            $content,
            $summary,
            $status,
            news_ops::storeformat_url($image_url),
            trim($db->DBTimeStamp($postdate), "'"),
            NULL, // undefined DT value in db
            NULL, // ditto
            $longnow,
            $longnow,
            $userid,
            $extra,
            $news_url,
            ($searchable) ? 1 : 0
        );
        if ($useexp) {
            $args[8] = trim($db->DBTimeStamp($startdate), "'");
            $args[9] = trim($db->DBTimeStamp($enddate), "'");
        }

        $dbr = $db->Execute($query, $args);
        if (!$dbr) {
            echo 'DEBUG: SQL = ' . $db->sql . '<br>';
            die($db->ErrorMsg());
        }

        //
        //Handle any 'custom' fields
        //
        $wantedfields = isset($params['usefield']) ? $params['usefield'] : [];
        if ($wantedfields) {
            // process the file fields, which involve an input-file element
            $query = 'SELECT id FROM ' . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='file'";
            $ffields = $db->GetCol($query);
            if ($ffields) {
                foreach ($ffields as $fid) {
                    if (!empty($wantedfields[$fid])) { // use this one
                        /*
                        the recorded value for a 'file' field is like
                          somefile.ext
                        which file will have been saved (see above) as
                          $config['uploads_path'].DIRECTORY_SEPARATOR
                         .'news'.DIRECTORY_SEPARATOR.
                          'id'.$articleid.DIRECTORY_SEPARATOR.
                          somefile.ext
                        the default displayer for that tag involves
                         <img src="{$entry->file_location}/{$field->value}"
                        */
                        $elem = $id . 'customfield_' . $fid;
                        if (isset($_FILES[$elem]) && $_FILES[$elem]['name'] != '') {
                            if ($_FILES[$elem]['error'] != 0 || $_FILES[$elem]['tmp_name'] == '') {
                                $this->ShowErrors($this->Lang('error_upload'));
                                $error = TRUE;
                            } else {
                                $error = '';
                                $value = news_admin_ops::handle_upload($articleid, $elem, $error);
                                if ($value !== FALSE) {
                                    $params['customfield'][$fid] = $value; // uploads-relative filepath
                                } else {
                                    $this->ShowErrors($error);
                                    $error = TRUE;
                                }
                            }
                        }
                    }
                }
            }
            // cache the potential wanted linkedfile fields
            $query = 'SELECT id FROM ' . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='linkedfile'";
            $lfields = $db->GetCol($query);

            if (isset($params['customfield']) && !$error) {
                $longnow = $db->DBTimeStamp($now);
                $query = 'INSERT INTO ' . CMS_DB_PREFIX . "module_news_fieldvals (news_id,fielddef_id,value,create_date,modified_date) VALUES (?,?,?,$longnow,$longnow)";
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
                                continue;
                            }
                        }

                        $dbr = $db->Execute($query, array(
                            $articleid,
                            $fldid,
                            $value
                        ));
                        if (!$dbr) {
                            die('FATAL SQL ERROR: ' . $db->ErrorMsg() . '<br>QUERY: ' . $db->sql);
                        }
                    }
                }
            }
        }

        if (!$error && $status == 'published' && $news_url) {
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
                        if (strlen($value) > 1) {
                            $text .= $value . ' ';
                        }
                    }
                }
                $text .= $content . ' ' . $summary . ' ' . $title . ' ' . $title;
                $module->AddWords($me, $articleid, 'article', $text, ($useexp == 1 && $this->GetPreference('expired_searchable', 0) == 0) ? $enddate : NULL);
            }
        }

        if (!$error) {
            HookManager::do_hook('News::NewsArticleAdded', array(
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
}

//
// build the form
//
$statusdropdown = array();
$statusdropdown[$this->Lang('draft')] = 'draft';
$statusdropdown[$this->Lang('published')] = 'published';

// Categories list
$query = 'SELECT COALESCE(long_name,news_category_name),news_category_id FROM ' . CMS_DB_PREFIX . 'module_news_categories ORDER BY hierarchy';
$categorylist = $db->GetAssoc($query);

// Fields
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
        $value = isset($params['customfield'][$fid]) &&
                 in_array($params['customfield'][$fid], $params['customfield']) ?
                 $params['customfield'][$fid] : '';
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
$profile = $filepicker->get_default_profile($dir, $userid);
$parms = ['top'=>$dir, 'type'=>'image']; // aka CMSMS\FileType::TYPE_IMAGE BUT type not enforced in the picker
$profile->overrideWith($parms); // TODO other property-overrides? not writability
$input = $filepicker->get_html($id.'image_url', $data, $profile);
preg_match('/id="(.+?)"/', $input, $matches);
$inputid = $matches[1];

HookManager::add_hook('admin_add_headtext', function() {
    $root_url = CMS_ROOT_URL;
    return "<script src=\"$root_url/lib/jquery/js/jquery.cmsms_dirtyform.js\" defer></script>\n";
});

/*--------------------
 Pass everything to template
 ---------------------*/

$tpl = $smarty->createTemplate("module_file_tpl:$me;editarticle.tpl", null, $me, $smarty);
$tpl->assign('formid', $id);
$tpl->assign('startform', $this->CreateFormStart($id, 'addarticle', $returnid, 'post', 'multipart/form-data'));
$tpl->assign('endform', $this->CreateFormEnd());
$tpl->assign('authortext', '');
$tpl->assign('inputauthor', '');
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
    'enablewysiwyg' => TRUE,
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
if ($custom_flds) {
    $tpl->assign('custom_fields', $custom_flds);
}

if ($this->CheckPermission('Approve News')) {
    $tpl->assign('statustext', lang('status'));
    $tpl->assign('statuses', array_flip($statusdropdown));
}

$tpl->display();
?>
