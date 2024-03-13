<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

if (!defined('CMS_VERSION')) exit;
if (!$this->CheckPermission('Modify Modules')) exit;

// remove the files folder, if any
$name = $this->GetPreference('filesFolder');
if ($name) {
    $dir = $config['image_uploads_path'];
    if ($dir && is_dir($dir)) {
        $dir .= DIRECTORY_SEPARATOR.$name;
        if (is_dir($dir)) {
            recursive_delete($dir);
        }
    }
}

$me = $this->GetName();

// remove templates and template types
try {
    $types = CmsLayoutTemplateType::load_all_by_originator($me);
    if ($types && is_array($types)) {
        foreach ($types as $type) {
            $templates = $type->get_template_list();
            if ($templates && is_array($templates)) {
                foreach ($templates as $template) {
                    $template->delete();
                }
            }
            $type->delete();
        }
    }
} catch (Exception $e) {
    audit('', $me, 'Template uninstallation error: '.$e->GetMessage());
}

// remove stylesheets
$sql = 'DELETE FROM '.CMS_DB_PREFIX."layout_stylesheets WHERE name LIKE '{$me}_%'";
$db->Execute($sql);

// remove the database table
$dict = NewDataDictionary($db);
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_userguide');
$dict->ExecuteSQLArray($sqlarray);

// remove permissions
$this->RemovePermission(UserGuide::MANAGE_PERM);
$this->RemovePermission(UserGuide::RESTRICT_PERM);
$this->RemovePermission(UserGuide::SETTINGS_PERM);

// remove module-tag handling
$this->RemoveSmartyPlugin();

// remove all preferences
$this->RemovePreference();
