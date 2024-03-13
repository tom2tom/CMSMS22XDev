<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

if (!defined('CMS_VERSION')) exit;
if (!$this->CheckPermission('Modify Modules')) exit;

if (cmsms()->test_state(CmsApp::STATE_INSTALL)) {
    $uid = 1; // hardcode to first user
} else {
    $uid = get_userid();
}
$me = $this->GetName();

// Setup module permissions
$this->CreatePermission(UserGuide::MANAGE_PERM, "$me - Manage");
$this->CreatePermission(UserGuide::RESTRICT_PERM, "$me - RestrictView");
$this->CreatePermission(UserGuide::SETTINGS_PERM, "$me - Settings");

// Set preferences (see also stylesheet names and folder creation, below)
$this->SetPreference('adminSection', 'content');
$this->SetPreference('customCSS', false);
$this->SetPreference('customLabel', '');
$this->SetPreference('filesFolder', $me);
$this->SetPreference('useSmarty', false);

// Support clean module-tags
$this->RegisterModulePlugin(true);

// Create table
$dict = NewDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MyISAM', 'mysql' => 'ENGINE=MyISAM'];

//restricted could be (tiny)X, or B if crypted
$fields = '
id I2 UNSIGNED KEY AUTO,
name C(128) NOTNULL,
revision C(48) DEFAULT \'\',
position I1 UNSIGNED,
active I1 DEFAULT 1,
admin I1 DEFAULT 0,
restricted C(255) DEFAULT \'\',
search I1 DEFAULT 1,
smarty I1 DEFAULT 0,
wysiwyg I1 DEFAULT 1,
author_id I UNSIGNED,
author C(64) DEFAULT \'\',
template_id I UNSIGNED DEFAULT 0,
sheets C(255) DEFAULT \'\',
create_date DT,
modified_date DT,
content X
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_userguide', $fields, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);
//TODO consider adjusting create_date DT DEFAULT CURRENT_TIMESTAMP, modified_date DT ON UPDATE CURRENT_TIMESTAMP
//TODO any other indices worth having? Should be only a few items in the table.

// Setup frontend templating
try {
    $type = new CmsLayoutTemplateType();
    $type->set_originator($me);
    $type->set_name('oneguide');
    $type->set_dflt_flag(true);
    $type->set_content_callback("$me::type_reset_defaults");
    $type->set_help_callback("$me::type_help_callback");
    $type->set_lang_callback("$me::type_lang_callback");
    $type->reset_content_to_factory();
    $type->save();
} catch (CmsException $e) {
    audit('', $me, 'Template-type \'oneguide\' installation error: '.$e->GetMessage());
}
try {
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_guide_template.tpl';
    if (file_exists($fn)) {
        $content = file_get_contents($fn);
        $tpl = new CmsLayoutTemplate();
        $tpl->set_name('Single UserGuide Sample');
        $tpl->set_description('Default template for displaying a single guide');
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($type);
        $tpl->set_type_dflt(true);
        $tpl->save();
    }
}
catch (CmsException $e) {
    audit('', $me, 'Template \'Single UserGuide Sample\' installation error: '.$e->GetMessage());
}
try {
    $type = new CmsLayoutTemplateType();
    $type->set_originator($me);
    $type->set_name('listguides');
    $type->set_dflt_flag(true);
    $type->set_content_callback("$me::type_reset_defaults");
    $type->set_help_callback("$me::template_help_callback");
    $type->set_lang_callback("$me::type_lang_callback");
    $type->reset_content_to_factory();
    $type->save();
} catch (CmsException $e) {
    audit('', $me, 'Template-type \'listguides\' installation error: '.$e->GetMessage());
}
try {
    $fn = __DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'orig_list_template.tpl';
    if (file_exists($fn)) {
        $content = file_get_contents($fn);
        $tpl = new CmsLayoutTemplate();
        $tpl->set_name('UserGuides List Sample');
        $tpl->set_description('Default template for displaying a list of guides');
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($type);
        $tpl->set_type_dflt(true);
        $tpl->save();
    }
}
catch (CmsException $e) {
    audit('', $me, 'Template \'UserGuides List Sample\' installation error: '.$e->GetMessage());
}

// Setup frontend styling
try {
    $css = new CmsLayoutStylesheet();
    //include a module-identifier-prefix in the name, in lieu of sheet type or originator property
    //see also CmsAdminUtils::is_valid_itemname
    $css->set_name("${me}_Item");
    $css->set_description('Styles for displaying a single guide');
    $fn = cms_join_path(__DIR__, 'lib', 'css', 'orig_view_guide.css');
    $css->set_content(file_get_contents($fn));
    $css->set_media_types(['screen', 'print']); //deprecated
    //see https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_media_queries/Using_media_queries
/*  $css->set_media_query(<<<EOS
@media ....) {
 code ...
}
EOS;
    );
*/
    $css->save();
    $this->SetPreference('guideStyles', "{$me}_Item");
} catch (CmsException $e) {
    audit('', $me, "Stylesheet '{$me}_Item' installation error: ".$e->GetMessage());
}
try {
    $css = new CmsLayoutStylesheet();
    $css->set_name("{$me}_List");
    $css->set_description('Styles for displaying a list of guides');
    $fn = cms_join_path(__DIR__, 'lib', 'css', 'orig_list_guides.css');
    $css->set_content(file_get_contents($fn));
    $css->set_media_types(['screen', 'print']); //deprecated
/*  $css->set_media_query(<<<EOS
@media ....) {
 code ...
}
EOS;
    );
*/
    $css->save();
    $this->SetPreference('listStyles', "{$me}_List");
} catch (CmsException $e) {
    audit('', $me, "Stylesheet '{$me}_List' installation error: ".$e->GetMessage());
}

$fn = cms_join_path($config['image_uploads_path'], $me); // basename is preference value
@mkdir($fn, 0775, true);
