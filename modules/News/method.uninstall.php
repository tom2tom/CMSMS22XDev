<?php
#CMSMS News module method: uninstall
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Modules')) exit;

$dict = NewDataDictionary( $db );

$sqlarray = $dict->DropTableSQL( CMS_DB_PREFIX.'module_news' );
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL( CMS_DB_PREFIX.'module_news_categories' );
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL( CMS_DB_PREFIX.'module_news_fielddefs' );
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL( CMS_DB_PREFIX.'module_news_fieldvals' );
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence( CMS_DB_PREFIX.'module_news_seq' );
$db->DropSequence( CMS_DB_PREFIX.'module_news_categories_seq' );

$this->RemovePermission('Modify News');
$this->RemovePermission('Approve News');
$this->RemovePermission('Delete News');

// Remove all preferences for this module
$this->RemovePreference();

// And events
$this->RemoveEvent('NewsArticleAdded');
$this->RemoveEvent('NewsArticleEdited');
$this->RemoveEvent('NewsArticleDeleted');
$this->RemoveEvent('NewsCategoryAdded');
$this->RemoveEvent('NewsCategoryEdited');
$this->RemoveEvent('NewsCategoryDeleted');

$this->RemoveSmartyPlugin();

$me = $this->GetName();
cms_route_manager::del_static('',$me);

// Remove user-specific filter-preferences
$sql = 'DELETE FROM '.CMS_DB_PREFIX."userprefs WHERE preference LIKE {$me}_%";
$db->Execute($sql);

// Remove all module-templates
$this->DeleteTemplate();

// And DM templates and template types
try {
  $types = CmsLayoutTemplateType::load_all_by_originator($me);
  if( is_array($types) && count($types) ) {
    foreach( $types as $type ) {
      $templates = $type->get_template_list();
      if( $templates && is_array($templates) ) {
        foreach( $templates as $template ) {
          $template->delete();
        }
      }
      $type->delete();
    }
  }
}
catch( Exception $e ) {
  // log it
  audit('',$me,'Uninstallation error: '.$e->GetMessage());
}

// Remove plugin
$p = cms_join_path(CMS_ROOT_PATH,'lib','plugins','function.news_image.php');
@unlink($p);

// Remove any uploads
$p = cms_join_path($config['uploads_path'],'news');
if( is_dir($p) ) {
  recursive_delete($p);
}

?>
