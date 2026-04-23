<?php
#CMSMS News module method: upgrade
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if( !isset($gCms) ) exit;
if( $gCms->test_state(CmsApp::STATE_INSTALL) ) {
    $uid = 1; // hardcode to first user
}
elseif( $this->CheckPermission('Modify Modules') ) {
    $uid = get_userid();
}
else {
    exit;
}

if( version_compare($oldversion,'2.50') < 0 ) {
    $_fix_name = function($str) {
        if( CmsAdminUtils::is_valid_itemname($str) ) return $str;
        $orig = $str;
        $str = trim($str);
        if( !CmsAdminUtils::is_valid_itemname($str[0]) ) $str[0] = '_';
        for( $i = 1; $i < strlen($str); $i++ ) {
            if( !CmsAdminUtils::is_valid_itemname($str[$i]) ) $str[$i] = '_';
        }
        for( $i = 0; $i < 5; $i++ ) {
            $in = $str;
            $str = str_replace('__','_',$str);
            if( $in == $str ) break;
        }
        if( $str == '_' ) throw new \Exception('Invalid name '.$orig.' and cannot be corrected');
        return $str;
    };

    // create template types.
    $upgrade_template = function($type,$prefix,$tplname,$currentdflt,$prefix2) use (&$mod,&$_fix_name,$uid) {
        if( !startswith($tplname,$prefix) ) return;
        $contents = $mod->GetTemplate($tplname);
        if( !$contents ) return;
        $prototype = substr($tplname,strlen($prefix));
        $prototype = $_fix_name($prototype);

        try {
            $tpl = new CmsLayoutTemplate();
            $tpl->set_name($tpl::generate_unique_name($prototype,$prefix2));
            $tpl->set_owner($uid);
            $tpl->set_content($contents);
            $tpl->set_type($type);
            $tpl->set_type_dflt($prototype == $mod->GetPreference($currentdflt));
            $tpl->save();

            $mod->DeleteTemplate($tplname);
        }
        catch( \CmsInvalidDataException $e ) {
            //nothing here
        }

  };

  try {
      $dict = NewDataDictionary($db);
      $sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'module_news','searchable I1');
      $dict->ExecuteSQLArray($sqlarray);

      $sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.'module_news_categories','item_order I');
      $dict->ExecuteSQLArray($sqlarray);

      $query = "SELECT * FROM ".CMS_DB_PREFIX."module_news_categories ORDER BY parent_id";
      $categories = $db->GetArray($query);

      $uquery = 'UPDATE '.CMS_DB_PREFIX.'module_news_categories SET item_order = ? WHERE news_category_id = ?';
      if( is_array($categories) && count($categories) ) {
          $prev_parent = 0;
          $item_order = 0;
          foreach( $categories as $row ) {
              $parent = (int)$row['parent_id'];
              if( $parent != $prev_parent ) $item_order = 0;
              $item_order++;
              $db->Execute($uquery,array($item_order,$row['news_category_id']));
          }
      }

      $mod = $this;
      $alltemplates = $this->ListTemplates();

      try {
          $summary_template_type = new CmsLayoutTemplateType();
          $summary_template_type->set_originator($this->GetName());
          $summary_template_type->set_name('summary');
          $summary_template_type->set_dflt_flag(TRUE);
          $summary_template_type->set_lang_callback('News::page_type_lang_callback');
          $summary_template_type->set_content_callback('News::reset_page_type_defaults');
          $summary_template_type->reset_content_to_factory();
          $summary_template_type->save();
          foreach( $alltemplates as $tplname ) {
              $upgrade_template($summary_template_type,'summary',$tplname,'current_summary_template','News-Summary-');
          }
      }
      catch( \CmsInvalidDataException $e ) {
          // ignore this error.
      }

      try {
          $detail_template_type = new CmsLayoutTemplateType();
          $detail_template_type->set_originator($this->GetName());
          $detail_template_type->set_name('detail');
          $detail_template_type->set_dflt_flag(TRUE);
          $detail_template_type->set_lang_callback('News::page_type_lang_callback');
          $detail_template_type->set_content_callback('News::reset_page_type_defaults');
          $detail_template_type->reset_content_to_factory();
          $detail_template_type->save();
          foreach( $alltemplates as $tplname ) {
              $upgrade_template($detail_template_type,'detail',$tplname,'current_detail_template','News-Detail-');
          }
      }
      catch( \CmsInvalidDataException $e ) {
          // ignore this error.
      }

      try {
          $form_template_type = new CmsLayoutTemplateType();
          $form_template_type->set_originator($this->GetName());
          $form_template_type->set_name('form');
          $form_template_type->set_dflt_flag(TRUE);
          $form_template_type->set_lang_callback('News::page_type_lang_callback');
          $form_template_type->set_content_callback('News::reset_page_type_defaults');
          $form_template_type->reset_content_to_factory();
          $form_template_type->save();
          foreach( $alltemplates as $tplname ) {
              $upgrade_template($form_template_type,'form',$tplname,'current_form_template','News-Form-');
          }
      }
      catch( \CmsInvalidDataException $e ) {
          // ignore this error.
      }

      try {
          $browsecat_template_type = new CmsLayoutTemplateType();
          $browsecat_template_type->set_originator($this->GetName());
          $browsecat_template_type->set_name('browsecat');
          $browsecat_template_type->set_dflt_flag(TRUE);
          $browsecat_template_type->set_lang_callback('News::page_type_lang_callback');
          $browsecat_template_type->set_content_callback('News::reset_page_type_defaults');
          $browsecat_template_type->reset_content_to_factory();
          $browsecat_template_type->save();
          foreach( $alltemplates as $tplname ) {
              $upgrade_template($browsecat_template_type,'browsecat',$tplname,'current_browsecat_template','News-Browsecat-');
          }
      }
      catch( \CmsInvalidDataException $e ) {
          // ignore this error.
      }
  }
  catch( CmsException $e ) {
      audit('',$this->GetName(),'Upgrade error: '.$e->GetMessage());
      return FALSE;
  }

  $this->RegisterModulePlugin(TRUE);
  $this->RegisterSmartyPlugin('news','function','function_plugin');
  $this->CreateStaticRoutes();
}

if( version_compare($oldversion,'2.50.8') < 0 ) {
    try {
        $types = CmsLayoutTemplateType::load_all_by_originator($this->GetName());
        if( is_array($types) && count($types) ) {
            foreach( $types as $type_obj ) {
                $type_obj->set_help_callback('News::template_help_callback');
                $type_obj->save();
            }
        }
    }
    catch( Exception $e ) {
        // log it
        audit('',$this->GetName(),'Upgrade error: '.$e->GetMessage());
        return FALSE;
    }
}

if( version_compare($oldversion,'2.51.14') < 0 ) {
    $tbl = CMS_DB_PREFIX.'module_news_categories';
    // update hierarchy values from 5-wide levels to 3-wide
    $data = $db->getArray('SELECT news_category_id,hierarchy FROM '.$tbl);
    $query = 'UPDATE '.$tbl.' SET hierarchy=? WHERE news_category_id=?';
    foreach( $data as $row ) {
        $parts = explode('.',$row['hierarchy']);
        foreach( $parts as &$one ) {
            $one = substr(trim($one),-3);
        }
        unset($one);
        $val = implode('.',$parts);
        $db->Execute($query,[$val,$row['news_category_id']]);
    }
    //CMSMS 2.2 DataDictionary can't handle extra field properties, so fallback to literal SQL for MySQL
    $query = <<<EOS
ALTER TABLE `{$tbl}`
CHANGE `hierarchy` `hierarchy` varchar(255) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL
EOS;
    $db->Execute($query);

    // correct and modify category-fields' format
    $when = $db->GetOne('SELECT MIN(create_date) FROM '.CMS_DB_PREFIX.'content WHERE create_date IS NOT NULL');
    if ($when) {
        $ywhen = substr($when,0,11);
    }
    else {
        $ywhen = '2015-01-01 ';
    }
    $data = $db->GetArray('SELECT news_category_id,create_date,modified_date FROM '.$tbl);

    if( !isset($dict) ) $dict = NewDataDictionary($db);
    $sqlarray = $dict->AlterColumnSQL($tbl,'create_date DT');
    $dict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dict->AlterColumnSQL($tbl,'modified_date DT');
    $dict->ExecuteSQLArray($sqlarray);

    $query = 'UPDATE '.$tbl.' SET create_date=?,modified_date=? WHERE news_category_id=?';
    foreach( $data as $row ) {
        $dc = $ywhen.$row['create_date'];
        if( $row['modified_date'] ) {
            $dm = $ywhen.$row['modified_date'];
            if( strcmp($dm,$dc) < 0 ) {
                $dm = $dc;
            }
        }
        else {
            $dm = null;
        }
        $res = $db->Execute($query,array($dc,$dm,$row['news_category_id']));
    }
    $pref = CMS_DB_PREFIX;
    foreach( array('news','news_categories','news_fielddefs','news_fieldvals') as $tname) {
        $query = <<<EOS
ALTER TABLE `{$pref}module_{$tname}`
CHANGE `create_date` `create_date` datetime NULL DEFAULT current_timestamp(),
CHANGE `modified_date` `modified_date` datetime DEFAULT NULL ON UPDATE current_timestamp()
EOS;
        $db->Execute($query);
    }
    // migrate 'fesubmit_redirect' preference from page alias to page id,
    // to allow use of a hierarcy-selector for setting that pref.
    $mgr = $gCms->GetHierarchyManager();
    $val = $this->GetPreference('fesubmit_redirect',-1);
    $node = $mgr->sureGetNodeByAlias($val);
    if( $node ) {
        $val = $node->getID();
        $this->SetPreference('fesubmit_redirect',$val);
    }
    elseif( $val != -1 ) {
        $node = $mgr->sureGetNodeById($val);
        if( !$node ) {
            $this->SetPreference('fesubmit_redirect',-1);
        }
    }
}

if( version_compare($oldversion,'2.51.16') < 0 ) {
    // ensure recorded 'risky' properties are clean
    $pref = CMS_DB_PREFIX;
    $fmt = "UPDATE {$pref}module_news SET %s WHERE news_id=?";
    $fu = ['news_title=?','news_data=?','summary=?','news_extra=?'];
    $query = "SELECT news_id,news_title,news_data,summary,news_extra FROM {$pref}module_news";
    $dbr = $db->GetArray($query);
    foreach( $dbr as $row ) {
        $FN = [];
        $args = [];
        $t = $row['news_title'];
        $ta = ($t) ? news_ops::execSpecialize($t) : $t;
        if( $ta != $t ) { $FN[] = $fu[0]; $args[] = $ta; }
        $d = $row['news_data'];
        $da = ($d) ? news_ops::execSpecialize($d) : $d;
        if( $da != $d ) { $FN[] = $fu[1]; $args[] = $da; }
        $s = $row['summary'];
        $sa = ($s) ? news_ops::execSpecialize($s) : $s;
        if( $sa != $s ) { $FN[] = $fu[2]; $args[] = $sa; }
        $e = $row['news_extra'];
        $ea = ($e) ? news_ops::execSpecialize($e) : $e;
        if( $ea != $e ) { $FN[] = $fu[3]; $args[] = $ea; }
        if( $FN ) {
            $query = sprintf($fmt, implode(',',$FN));
            $args[] = (int)$row['news_id'];
            $db->Execute($query,$args);
        }
    }

    // scrub redundant fields
    $query = <<<EOS
DELETE FV FROM {$pref}module_news_fieldvals FV
JOIN {$pref}module_news_fielddefs FD ON FV.fielddef_id = FD.id
WHERE (FV.value IS NULL OR FV.value='' OR (FV.value=-1 AND FD.type='dropdown'))
EOS;
    $db->Execute($query);

    // re-arrange maximum length
    $dbr = $db->GetArray("SELECT id,type,max_length,extra FROM {$pref}module_news_fielddefs");
    $query = "UPDATE {$pref}module_news_fielddefs SET extra=? WHERE id=?";
    foreach( $dbr as $row ) {
       switch ($row['type']) {
           case 'dropdown':
               continue 2; // stet this one
           case 'textbox':
               $lm = (int)$row['max_length'];
               if ($lm < 48) { $lm = 48; }
               $adjusted = serialize(['max_length'=>$lm]);
               break;
           default:
               $adjusted = null; // clear it
               break;
       }
       if ($adjusted !== false) $db->Execute($query,[$adjusted,$row['id']]);
    }

$query = <<<EOS
ALTER TABLE `{$pref}module_news_fielddefs`
CHANGE `type` `type` varchar(24) CHARACTER SET ascii COLLATE ascii_bin,
CHANGE `item_order` `item_order` smallint unsigned,
CHANGE `public` `public` tinyint unsigned
EOS;
    $db->Execute($query);

    if( !isset($dict) ) { $dict = NewDataDictionary($db); }
    $sqlarray = $dict->DropColumnSQL("{$pref}module_news_fielddefs",['max_length']);
    $dict->ExecuteSQLArray($sqlarray);

    // sanitize risky values in fieldvals
    $query = <<<EOS
SELECT FV.news_id,FV.fielddef_id,FV.value FROM {$pref}module_news_fieldvals FV
JOIN {$pref}module_news_fielddefs FD ON FV.fielddef_id = FD.id
WHERE (FD.type='textbox' OR FD.type='textarea')
EOS;
    $dbr = $db->GetArray($query);
    foreach( $dbr as $row ) {
        $t = $row['value'];
        $ta = ($t) ? news_ops::execSpecialize($t) : $t;
        if( $ta != $t ) {
            $db->Execute("UPDATE {$pref}module_news_fieldvals SET value=? WHERE news_id=? AND fielddef_id=?",
                [$ta,(int)$row['news_id'],(int)$row['fielddef_id']]);
        }
    }

    // update default templates
    $now = time();
    $done = 0;
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'layout_templates
(name,content,description,type_id,type_dflt,category_id,owner_id,listable,created,modified)
VALUES (?,?,?,?,?,?,?,?,?,?)';
    $outfiles = [
     'Simplex_Detail_template',
     'orig_detail_template',
     'orig_form_template',
     'orig_summary_template'
    ];
    $innames = [
     'Simplex News Detail',
     'News Detail Sample',
     'News Fesubmit Form Sample',
     'News Summary Sample'
    ];
    foreach( $innames as $i => $tplname ) {
        $newcontent = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$outfiles[$i].'.tpl');
        $row = $db->GetRow("SELECT * FROM {$pref}layout_templates WHERE name = ?",[$tplname]);
        if( $row ) {
            $args = $row;
            unset($args['id']);
            $args['name'] .= ' Superseded Content';
            $args['type_dflt'] = 0;
            $db->Execute($query,array_values($args));

            $tplid = $row['id'];
            $db->Execute("UPDATE {$pref}layout_templates SET content=?,modified=$now WHERE id=$tplid",[$newcontent]);
            if( $row['type_dflt'] ) {
                $type = CmsLayoutTemplateType::load($row['type_id']);
                $type->reset_content_to_factory();
            }
            ++$done;
        }
        else {
            // record new template as example
            $desc = 'Default News template example';
            $ttid = 999;
            $args = [$tplname.' Replaced Content',$newcontent,$desc,$ttid,0,0,1,1,$now,$now];
            $db->Execute($query,$args);
        }
    }
    if( $done > 0 ) {
        audit('','News module',"Replaced content of $done recorded template(s) involving custom fields");
        $msg = <<<EOS
The content of $done News-module default templates (involving custom fields) has been replaced.
Any customisation formerly in those will need to be re-created from the backup templates.
EOS;
        if( $done == 1 ) {
            $msg = str_replace(['lates','ose'],['late','at'],$msg);
        }
        $themeObject = cms_utils::get_theme_object();
        $themeObject->AddNotification(2,'News module change',$msg);
    }

/*  // conform uploads folder to module name
    $from = $config['uploads_path'].DIRECTORY_SEPARATOR.'news';
    if( is_dir($from) ) {
        $to = str_replace('news',$this->GetName(),$from);
        rename($from,$to);
    }
    $query = "UPDATE {$pref}module_news_fieldvals SET value=? WHERE value LIKE '%news%'" i.e. rename news[\/] to News[\/] whereever used
    $db->Execute($query);
*/
    // rename preference
    $t = $this->GetPreference('formsubmit_emailaddress','^_NONE_^');
    if( $t != '^_NONE_^' ) {
        $this->RemovePreference('formsubmit_emailaddress');
        $this->SetPreference('fesubmit_emailaddress',$t);
    }

    // relocate/install plugin
    $from = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'function.news_image.php';
    $to = cms_join_path(CMS_ROOT_PATH,'lib','plugins','function.news_image.php');
    if( copy($from,$to) ) {
        chmod($to,0644);
    }
    else {
        audit('','News module','Failed to install news_image plugin');
    }
}

if( version_compare($oldversion,'2.51.17') < 0 ) {
    //articles-list filter properties are now user-specific
    $this->RemovePreference('allcategories');
    $this->RemovePreference('article_category');
    $this->RemovePreference('article_pagelimit');
    $this->RemovePreference('article_sortby');
}
?>
