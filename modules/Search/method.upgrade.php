<?php
if (!isset($gCms)) exit;

$uid = 1;

if( version_compare($oldversion,'1.50') < 1 ) {
  $this->RegisterModulePlugin(true);
  $this->RegisterSmartyPlugin('search','function','function_plugin');

  try {
    try {
      $searchform_type = new CmsLayoutTemplateType();
      $searchform_type->set_originator($this->GetName());
      $searchform_type->set_name('searchform');
      $searchform_type->set_dflt_flag(TRUE);
      $searchform_type->set_lang_callback('Search::page_type_lang_callback');
      $searchform_type->set_content_callback('Search::reset_page_type_defaults');
      $searchform_type->reset_content_to_factory();
      $searchform_type->save();
    }
    catch( CmsInvalidDataException $e ) {
      // ignore this error.
    }

    $template = $this->GetTemplate('displaysearch');
    if( $template ) {
      $tpl = new CmsLayoutTemplate();
      $tpl->set_name('Search Form Sample');
      $tpl->set_owner($uid);
      $tpl->set_content($template);
      $tpl->set_type($searchform_type);
      $tpl->set_type_dflt(TRUE);
      $tpl->save();
      $this->DeleteTemplate('displaysearch');
    }

    try {
      $searchresults_type = new CmsLayoutTemplateType();
      $searchresults_type->set_originator($this->GetName());
      $searchresults_type->set_name('searchresults');
      $searchresults_type->set_dflt_flag(TRUE);
      $searchresults_type->set_lang_callback('Search::page_type_lang_callback');
      $searchresults_type->set_content_callback('Search::reset_page_type_defaults');
      $searchresults_type->reset_content_to_factory();
      $searchresults_type->save();
    }
    catch( \CmsInvalidDataException $e ) {
      // ignore this error.
    }

    $template = $this->GetTemplate('displayresult');
    if( $template ) {
      $tpl = new CmsLayoutTemplate();
      $tpl->set_name('Search Results Sample');
      $tpl->set_owner($uid);
      $tpl->set_content($template);
      $tpl->set_type($searchresults_type);
      $tpl->set_type_dflt(TRUE);
      $tpl->save();
      $this->DeleteTemplate('displayresult');
    }
  }
  catch( CmsException $e ) {
    audit('',$this->GetName(),'Upgrade error: '.$e->GetMessage());
  }
}

if( version_compare($oldversion,'1.51') < 0 ) {
  // InnoDB engine for tables where transactions are used
  $pref = CMS_DB_PREFIX;
  $sql_i = "ALTER TABLE {$pref}module_search_%s ENGINE=InnoDB";
  $tables = array('items','index');
  foreach( $tables as $table ) {
    $db->Execute(sprintf($sql_i,$table));
  }
}

if( version_compare($oldversion,'1.52') < 1 ) {
  // extra permission
  $this->CreatePermission('Manage Search');
  // revert former 1.51 change
  $sql_i = 'ALTER TABLE '.CMS_DB_PREFIX.'module_search_words ENGINE=MyISAM';
  $db->Execute($sql_i);
}

if( version_compare($oldversion,'1.53') < 1 ) {
  foreach( ['alpharesults','savephrases','usestemming'] as $pname ) {
    $v = $this->GetPreference($pname);
    $this->SetPreference($pname, ($v == 'true') ? 1 : 0);
  }
  // might have missed InnoDB engine conversion, earlier
  $pref = CMS_DB_PREFIX;
  $sql_i = "ALTER TABLE {$pref}module_search_%s ENGINE=InnoDB";
  foreach( ['items','index'] as $table ) {
    $db->Execute(sprintf($sql_i,$table));
  }
}
