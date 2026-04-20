<?php
#CMSMS News module action: fesubmit
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

/*
This action has been deprecated since News 2.15.1 in CMSMS 1.12
and is due to be removed from CMSMS

This action is for adding an article, not editing an existing one.

Although its name suggests frontend use, this action could also be used
for article-submission by admin users lacking permission to use the full
News-admin UI, and/or to enable a custom template for adding an article.
*/

use CMSMS\FilePickerProfile;
use CMSMS\HookManager;

if( !isset($gCms) ) exit;
if( !$this->GetPreference('allow_fesubmit',0) ) return;

$modname = $this->GetName();
if( isset($params['formtemplate']) ) {
    $template = trim($params['formtemplate']);
}
else {
    $tpl = CmsLayoutTemplate::load_dflt_by_type("$modname::form");
    if( is_object($tpl) ) {
        $template = $tpl->get_name();
    }
    else {
        audit('',$modname.':feusubmit','No default form template found');
        return;
    }
}

$title = '';
$extra = '';
$content = '';
$summary = '';
$icon = '';
$status = $this->GetPreference('fesubmit_status','draft');
$now = time();
$startdate = $now;
$ndays = (int)$this->GetPreference('expiry_interval',30);
if( $ndays <= 0 ) $ndays = 30;
$enddate = strtotime(sprintf('+%d days',$ndays),$now);
$front = $gCms->is_frontend_request();
$userid ( $front ) ? 0 : get_userid(false); // frontend login later recorded as < 0
$category_id = $this->GetPreference('default_category',1);
$do_send_email = false;
$do_redirect = false;

if( $front ) {
    // handle the page to go to after submit.
    $dest_page = $returnid;
    $tmp = $this->GetPreference('fesubmit_redirect',-1); //id (or was alias)
    if( $tmp && $tmp != -1 ) {
        $manager = $gCms->GetHierarchyManager();
        $node = $manager->sureGetNodeById($tmp);
        if( $node ) {
            $dest_page = (int)$tmp;
        }
        else {
            //deprecated - check for alias
            $node = $manager->sureGetNodeByAlias($tmp);
            if( $node ) {
                $dest_page = $node->getID();
            }
        }
    }

    // see if we're logged in.
    $module = $this->GetModuleInstance('MAMS');
    if( !$module ) {
        $module = $this->GetModuleInstance('FrontEndUsers');
    }
    if( $module ) {
        $userid = (int)$module->LoggedInId();
        if( $userid > 0 ) { $userid = -$userid; } // < 0 indicates frontend
    }
}

if( isset($params['category']) ) {
    $query = 'SELECT news_category_id FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_name=?';
    $tmp = $db->GetOne($query,array($params['category']));
    if( $tmp ) $category_id = $tmp;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource($template),null,$modname,$smarty);
if( isset($params['submit']) ) {
    try {
        if( !empty($params['title']) ) $title = news_ops::execSpecialize(trim($params['title']));
        if( !$title ) throw new CmsException($this->Lang('notitlegiven'));
        if( !empty($params['content']) ) $content = news_ops::execSpecialize(trim($params['content']));
        if( !$content ) throw new CmsException($this->Lang('nocontentgiven'));
        if( !empty($params['summary']) ) $summary = news_ops::execSpecialize(trim($params['summary']));
        if( !empty($params['extra']) ) $extra = news_ops::execSpecialize(trim($params['extra']));
        if( !empty($params['icon']) ) {
            $icon = trim($params['icon']);
            $res = cms_utils::validate_url($icon,'image');
            if( $res !== TRUE ) {
                $icon = '';
            }
        }
        if( isset($params['category_id']) ) $category_id = (int)$params['category_id'];
        if( isset($params['input_category']) ) $category_id = (int)$params['input_category'];

        if( isset($params['startdate_Month']) ) {
            $startdate = mktime((int)$params['startdate_Hour'],(int)$params['startdate_Minute'],(int)$params['startdate_Second'],
                                (int)$params['startdate_Month'],(int)$params['startdate_Day'],(int)$params['startdate_Year']);
        }
        if( isset($params['enddate_Month']) ) {
            $enddate = mktime((int)$params['enddate_Hour'],(int)$params['enddate_Minute'],(int)$params['enddate_Second'],
                              (int)$params['enddate_Month'],(int)$params['enddate_Day'],(int)$params['enddate_Year']);
        }
        if( $startdate > 0 && $startdate >= $enddate ) throw new CmsException($this->Lang('startdatetoolate'));

        // generate a new article id
        $articleid = $db->GenID(CMS_DB_PREFIX."module_news_seq");

        // and generate the insert query
        $longnow = $db->DBTimeStamp($now);
        $tnow = trim($longnow,"'");
        $tstart = ($startdate > 0) ? trim($db->DBTimeStamp($startdate),"'") : NULL; // always use-expiry for $front
        $tend = ($enddate > 0) ? trim($db->DBTimeStamp($enddate),"'") : NULL; // ditto
        // note: there's no option for fesubmit to disable searchability
        // CHECKME also support 'news_url' property ?
        $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news
(news_id,news_category_id,news_title,news_data,summary,status,icon,news_date,start_time,end_time,create_date,modified_date,author_id,news_extra,searchable)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)';
        $dbr = $db->Execute($query,array(
            $articleid,
            $category_id,
            $title,
            $content,
            $summary,
            $status,
            news_ops::storeformat_url($icon),
            $tnow,
            $tstart,
            $tend,
            $tnow,
            $tnow,
            $userid,
            $extra // no $news_url
            ));

        if( !$dbr ) {
//          if( $front ) throw else TODO usefully advise about error
            return;
        }

        // handle any custom fields
        $wantedfields = array();
        foreach( $params as $key => $value ) {
            if( startswith($key,'news_wantedfield_') ) {
                $fldid = (int)substr($key,17);
                $wantedfields[$fldid] = 1;
            }
        }
        if( $wantedfields ) {
            if( !$front ) {
                // process the file fields, which involve an input-file element
                $query = 'SELECT id FROM ' . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='file'";
                $ffields = $db->GetCol($query);
                if( $ffields ) {
                    foreach( $ffields as $fid ) {
                        if( !empty($wantedfields[$fid]) ) { // use this one
                            $elem = $id . 'customfield_' . $fid;
                            if( isset($_FILES[$elem]) && $_FILES[$elem]['name'] != '' ) {
                                if( $_FILES[$elem]['error'] != 0 || $_FILES[$elem]['tmp_name'] == '' ) {
//                                  if( $front ) throw else TODO usefully advise about error $this->Lang('error_upload')
                                    $error = TRUE;
                                } else {
                                    $error = '';
                                    $value = news_admin_ops::handle_upload($articleid,$elem,$error);
                                    if( $value !== FALSE ) {
                                        $params['customfield'][$fid] = $value; // uploads-relative filepath
                                    } else {
//                                      if( $front ) throw else TODO usefully advise about $error
                                        $error = TRUE;
                                    }
                                }
                            }
                        }
                    }
                }
                // cache the possibly-wanted linkedfile fields
                $query = 'SELECT id FROM ' . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='linkedfile'";
                $lfields = $db->GetCol($query);
            }
        }

        foreach( $params as $key => $value ) {
            if( startswith($key,'news_customfield_') ) {
                $fldid = (int)substr($key,17);
                if( $wantedfields && !empty($wantedfields[$fldid]) ) { // use this one
                    if( !$front && $lfields && in_array($fldid,$lfields) ) {
                        if( $value ) {
                            $tmp = news_ops::check_linkedfile($value,$config['uploads_path']);
                            if( $tmp != $value ) {
                                //do stuff
                                $value = $tmp;
                            }
                        } else {
                            //not really wanted ...
                            continue;
                        }
                    }
                    $query = 'INSERT INTO '.CMS_DB_PREFIX."module_news_fieldvals
(news_id,fielddef_id,value,create_date,modified_date)
VALUES ($articleid,$fldid,?,$longnow,$longnow)";
                    $dbr = $db->Execute($query,array($value));
                    if( !$dbr ) {
//                      if( $front ) throw else TODO usefully advise about error
                    }
                }
            }
        }

        if( $status == 'published' ) {
            // Update search index
            $module = cms_utils::get_search_module();
            if( is_object($module) ) {
                $module->AddWords($modname,$articleid,'article',$content . ' ' . $summary . ' ' . $title . ' ' . $title,$enddate );
            }
        }

        // send an email
        $do_send_email = true;
        $do_redirect = true;

        // send an event
        // CHECKME also support 'news_url' property ?
        HookManager::do_hook('News::NewsArticleAdded',array(
            'news_id' => $articleid,
            'category_id' => $category_id,
            'title' => $title,
            'content' => $content,
            'summary' => $summary,
            'status' => $status,
            'icon' => $icon,
            'start_time' => $startdate,
            'end_time' => $enddate,
            'useexp' => 1));

        // put mention into the admin log
        audit($articleid,$modname.' article',"Added: $title (from frontend)");

        // and we're done
        $tpl->assign('message',$this->Lang('articleadded'));
    }
    catch( Exception $e ) {
//      if( $front ) rethrow else TODO usefully advise about error
    }
}

// categories list
$query = 'SELECT COALESCE(long_name,news_category_name),news_category_id FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
$categorylist = $db->GetAssoc($query);

if( $front ) {
    $input = '';
    $inputid = '';
} else {
    // linkedfile fields are supported
    $filepicker = cms_utils::get_filepicker_module();
    $profile = $filepicker->get_default_profile($dir,$userid);
    $parms = ['top'=>$dir,'type'=>'image']; // aka CMSMS\FileType::TYPE_IMAGE BUT type not enforced in the picker
    $profile->overrideWith($parms); // TODO other property-overrides ? not writability
    $input = $filepicker->get_html($id.'image_url',$data,$profile);
    preg_match('/id="(.+?)"/',$input,$matches);
    $inputid = $matches[1];
}

$tpl->assign('category_id',$category_id);
$tpl->assign('title',$title);
$tpl->assign('categorylist',$categorylist);
$tpl->assign('extra',$extra);
$tpl->assign('content',$content);
$tpl->assign('summary',$summary);
$tpl->assign('hide_summary_field',$this->GetPreference('hide_summary_field',0));
$tpl->assign('allow_summary_wysiwyg',$this->GetPreference('allow_summary_wysiwyg',1));
$tpl->assign('startdate',$startdate);
$tpl->assign('enddate',$enddate);
$tpl->assign('status',$this->CreateInputHidden($id,'status',$status));
$tpl->assign('imageinput',$input);
$tpl->assign('imageinputid',$inputid);

$customfields = array();
$query = 'SELECT id,name,type,extra FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE public=1 ORDER BY item_order';
$dbr = $db->GetArray($query);
if( $dbr ) {
    foreach( $dbr as $row ) {
        if( $front && $row['type'] == 'linkedfile' ) continue;
        if( $front && $row['type'] == 'file' ) continue;
        $obj = new stdClass();
        $obj->id = $row['id'];
        $obj->type = $row['type'];
        $obj->nameattr = $id . $name;
        $obj->idattr = "customfield_$fid";
        $obj->prompt = $row['name'];

        if( !empty($row['extra']) ) {
            $row['extra'] = unserialize($row['extra'],array('allowed_classes' => FALSE));
            if( $row['extra'] !== FALSE ) {
                foreach ($row['extra'] as $prop => $pval) {
                    switch ($prop) {
                      case 'max_length':
                        $ms = (int)$pval;
                        $obj->max_len = max(1,$ms); //for input text, really
                        if( !isset($obj->size) ) $obj->size = min(50,$ms);
                        break;
                      case 'size':
                        $ms = (int)$pval;
                        $obj->size = min(50,$ms);
                        break;
                      default:
                        $obj->$prop = $pval;
                    }
                }
            }
        }

        $key = str_replace(' ','_',strtolower($row['name']));
        $customfields[$key] = $obj;
    }
}
if( $customfields ) $tpl->assign('customfields',$customfields);

$tpl->display();

if( $do_send_email ) {
    // this needs to be done after the form is generated
    // because we use some of the same Smarty variables
    $addy = trim($this->GetPreference('fesubmit_emailaddress'));
    if( $addy ) {
        $cmsmailer = new cms_mailer();
        if( $cmsmailer ) {
            try {
                $tpl2 = $smarty->createTemplate("module_db_tpl:$modname;email_template",null,$modname,$smarty);
                $tmp_vars = $tpl->smarty->getTemplateVars();
                foreach( $tmp_vars as $key => $val ) {
                    $tpl2->assign($key,$val);
                }
                $tpl2->assign('startdate',$startdate);
                $tpl2->assign('enddate',$enddate);
                $tpl2->assign('ipaddress',cms_utils::get_real_ip());
                $tpl2->assign('status',$status);
                $tpl2->assign('title',$title);
                if( $summary ) $tpl2->assign('summary',$summary);
                $tpl2->assign('content',$content);
                $body = $tpl2->fetch();
            }
            catch( Exception $e ) {
//            if( $front ) rethrow else TODO usefully advise about error
              if( $do_redirect ) $this->RedirectContent($dest_page);
              return;
            }

            $cmsmailer->AddAddress($addy);
            $val = ($this->GetPreference('email_subject')) ?: $this->Lang('subject_newnews');
            $cmsmailer->SetSubject($val);
            $cmsmailer->IsHTML(false);
            $cmsmailer->SetBody($body);
            $cmsmailer->Send();
        }
    }
}

if( $do_redirect ) $this->RedirectContent($dest_page);

?>
