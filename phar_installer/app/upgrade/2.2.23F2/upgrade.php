<?php
use function __appbase\joinpath;

//redundant folder
$dir = joinpath($config['root_path'],'lib','tasks');
if (is_dir($dir)) {
    //TODO c.f. 2.2 upgrade : $move_directory_files($dir, $dir-with-'tasks'>>'jobs') i.e. anything custom not handled by installer
    @unlink($dir.DIRECTORY_SEPARATOR.'index.html');
    @rmdir($dir); // fail if anything else still in there
}

//theme folder should've been renamed in 2.2.22F2
$dir = joinpath($config['themes_path'],'simplex');
if (is_dir($dir)) {
    $tp = joinpath($config['themes_path'],'Simplex');
    if (!is_dir($tp)) {
        @rename($dir,$tp);
    } else {
        //TODO maybe recursive delete $dir
    }
}

//new folders
$perms = 0777; //TODO suitable new-folder permissions e.g. 0777 & ~umask()
$dir = joinpath($config['assets_path'],'jobs');
if (!is_dir($dir)) {
    @mkdir($dir,$perms,true);
    touch($dir.DIRECTORY_SEPARATOR.'index.html');
}
$dir = joinpath($config['admin_path'],'configs','private');
if (!is_dir($dir)) {
    @mkdir($dir,$perms,true);
    touch($dir.DIRECTORY_SEPARATOR.'index.html');
}

//new preference related to database config
cms_siteprefs::set('privatePath',"\$config['admin_path'],configs,private");

Events::CreateEvent('Core','LoginPassed');
Events::CreateEvent('Core','LoginPre');
Events::CreateEvent('Core','LogoutPre');

$pref = CMS_DB_PREFIX;
$db->Execute("DROP TABLE IF EXISTS `{$pref}content_props_seq`");

//backup users table
$db->Execute("DROP TABLE IF EXISTS `{$pref}users_oldhashbackup`");
$db->Execute("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
$db->Execute("CREATE TABLE `{$pref}users_oldhashbackup` LIKE `{$pref}users`");
$db->Execute("INSERT INTO `{$pref}users_oldhashbackup` SELECT * FROM `{$pref}users`");

//content table fields: type,hierarchy,id_hierarchy,tabindex
//stored as ascii instead of multi-byte
$sql = <<<EOS
ALTER TABLE `{$pref}content`
MODIFY COLUMN `type` varchar(25) CHARACTER SET ascii COLLATE ascii_bin,
MODIFY COLUMN `hierarchy` varchar(255) CHARACTER SET ascii COLLATE ascii_bin,
MODIFY COLUMN `id_hierarchy` varchar(255) CHARACTER SET ascii COLLATE ascii_bin,
MODIFY COLUMN `tabindex` varchar(10) CHARACTER SET ascii COLLATE ascii_bin
EOS;
$db->Execute($sql);

//ibid for hashed user-passwords
$db->Execute("ALTER TABLE `{$pref}users` MODIFY COLUMN `password` varchar(128) CHARACTER SET ascii COLLATE ascii_bin");

foreach ([
['content','default_content',0],
['content','show_in_menu',1],
['content','active',1],
['content','cachable',1],
['content','secure',0],
//['event_handlers','handler_order',0], see below
//['event_handlers','removable',1], see below
['groups','active',1],
['modules','admin_only',0],
['modules','active',1],
['modules','allow_fe_lazyload',1],
['modules','allow_admin_lazyload',1],
['users','admin_access',1],
['users','active',1],
['module_smarty_plugins','available',1],
['module_smarty_plugins','cachable',1],
['layout_design_cssassoc','item_order',1],
['layout_tpl_categories','item_order',0],
['layout_tpl_type','has_dflt',0],
['layout_tpl_type','requires_contentblocks',0],
['layout_tpl_type','one_only',1],
['layout_templates','type_dflt',0],
['layout_templates','listable',1],
['layout_designs','dflt',0]
] as $props) {
    $db->Execute("ALTER TABLE `{$pref}{$props[0]}` MODIFY COLUMN `{$props[1]}` tinyint unsigned default {$props[2]}");
}

// consistent name for DT field
$db->Execute("ALTER TABLE `{$pref}routes` RENAME COLUMN `created` TO `create_date`");

// adjust event-handlers table fields
$db->Execute("ALTER TABLE `{$pref}event_handlers` ADD COLUMN `handler_type` tinyint unsigned AFTER `tag_name`");
$t1 = Events::HANDLERMOD;
$t2 = Events::HANDLERUDT;
$db->Execute("UPDATE {$pref}event_handlers SET handler_type=$t2 WHERE tag_name IS NOT NULL");
$db->Execute("UPDATE {$pref}event_handlers SET handler_type=$t1,tag_name=module_name WHERE module_name IS NOT NULL");
$sql = <<<EOS
ALTER TABLE `{$pref}event_handlers`
DROP `module_name`,
MODIFY COLUMN `handler_id` int NOT NULL first,
CHANGE `tag_name` `handler` varchar(255) NOT NULL,
MODIFY COLUMN `handler_type` tinyint unsigned NOT NULL,
MODIFY COLUMN `handler_order` tinyint unsigned NOT NULL default 0 after `handler_type`,
MODIFY COLUMN `removable` tinyint unsigned NOT NULL default 1
EOS;
$db->Execute($sql);

$dict = NewDataDictionary($db);
$sqlarray = $dict->RenameTableSQL("{$pref}event_handler_seq","{$pref}event_handlers_seq");
$dict->ExecuteSQLArray($sqlarray);

// new jobs-tables
$flds = '
id I UNSIGNED KEY,
name C(255) NOTNULL,
module C(160),
created I UNSIGNED NOTNULL,
start I UNSIGNED,
until I UNSIGNED,
priority I1 UNSIGNED NOTNULL DEFAULT 2,
errors I1 UNSIGNED NOTNULL DEFAULT 0,
recurs C(32),
data X';
$tbl = "{$pref}jobs";
$sqlarray = $dict->CreateTableSQL($tbl, $flds, ['mysqli' => 'ENGINE MyISAM']);
$dict->ExecuteSQLArray($sqlarray);
$db->Execute("ALTER TABLE `$tbl` MODIFY COLUMN `module` varchar(160) CHARACTER SET ascii COLLATE ascii_general_ci default NULL");

$db->CreateSequence("{$pref}jobs_seq");

$flds = '
id I UNSIGNED NOTNULL,
propname C(255) NOTNULL,
value I UNSIGNED';
$tbl = "{$pref}jobs_record";
$sqlarray = $dict->CreateTableSQL($tbl, $flds, ['mysqli' => 'ENGINE MyISAM CHARACTER SET ascii']);
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_id', $tbl, 'id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_propname', $tbl, 'propname');
$dict->ExecuteSQLArray($sqlarray);

$db->Execute("ALTER TABLE `{$pref}version` MODIFY COLUMN `version` smallint unsigned");
$db->Execute("UPDATE {$pref}version SET version = 203");

//deal with deprecated css media-types
$when = time();
$deprec = ['aural','speech','braille','embossed','handheld','projection','tty','tv'];
$repl =   ['','','','','screen','','print','screen'];
$sql = "UPDATE {$pref}layout_stylesheets SET media_type=?,modified=$when WHERE id=?";
$sql2 = "DELETE FROM {$pref}layout_stylesheets WHERE id=?";
$sql3 = "DELETE FROM {$pref}layout_design_cssassoc WHERE css_id=?";
$data = $db->GetAssoc("SELECT id,media_type FROM {$pref}layout_stylesheets");
foreach ($data as $id => $type) {
    $tmp = str_replace($deprec, $repl, $type);
    if ($tmp != $type) {
        $exp = explode(',', $tmp);
        $exp = array_filter($exp);
        $exp = array_unique($exp);
        if ($exp) {
            $newtype = implode(',', $exp);
            if ($newtype != $type) {
                $db->Execute($sql, [$newtype, $id]);
            }
        } else {
            $db->Execute($sql2, [$id]);
            $db->Execute($sql3, [$id]);
        }
    }
}

$db->Execute("ALTER TABLE `{$pref}layout_designs`
ADD COLUMN `version` varchar(20) default '1.0' after `description`,
ADD COLUMN `requires` varchar(320) after `version`");

$db->Execute("ALTER TABLE `{$pref}layout_tpl_type` MODIFY COLUMN `originator` varchar(160) NOT NULL");

// deal with renamed template-resource classes
$sql = "UPDATE {$pref}layout_tpl_type SET lang_cb=?,dflt_content_cb=?,help_content_cb=?,modified=$when WHERE id=?";
$data = $db->getAssoc(
"SELECT id,lang_cb,dflt_content_cb,help_content_cb FROM {$pref}layout_tpl_type
WHERE lang_cb LIKE '%Cms%Resource::%' OR dflt_content_cb LIKE '%Cms%Resource::%' OR help_content_cb LIKE '%Cms%Resource::%'");
foreach ($data as $id => $row) {
    $tmp = ($row['lang_cb']) ? unserialize($row['lang_cb']) : null;
    $v1 = ($tmp) ? serialize(str_replace('Cms', 'CMSMS\internal\\', $tmp)) : null;
    $tmp = ($row['dflt_content_cb']) ? unserialize($row['dflt_content_cb']) : null;
    $v2 = ($tmp) ? serialize(str_replace('Cms', 'CMSMS\internal\\', $tmp)) : null;
    $tmp = ($row['help_content_cb']) ? unserialize($row['help_content_cb']) : null;
    $v3 = ($tmp) ? serialize(str_replace('Cms', 'CMSMS\internal\\', $tmp)) : null;
    $db->Execute($sql, [$v1, $v2, $v3, $id]);
}

// migrate jobs-permission source
$db->Execute("UPDATE {$pref}permissions SET permission_source='Core' WHERE permission_name='Manage Jobs'");

// migrate event originator
$db->Execute("UPDATE {$pref}events SET originator='Core',event_name='OnJobFailed' WHERE event_name='CmsJobManager::OnJobFailed'");

$db->Execute("ALTER TABLE `{$pref}events`
MODIFY COLUMN `event_id` int NOT NULL first,
MODIFY COLUMN `originator` varchar(160) NOT NULL");
// remove duplicate index
$sqlarray = $dict->DropIndexSQL("{$pref}event_id", "{$pref}events");
$dict->ExecuteSQLArray($sqlarray);
// and another one
$sqlarray = $dict->DropIndexSQL("{$pref}idx_modules_by_name", "{$pref}modules");
$dict->ExecuteSQLArray($sqlarray);

// onetime registration
Events::AddEventTypedHandler('Core', 'ModuleUninstalled', 'CMSMS\JobOperations::clear_module', Events::HANDLERCALL, false);

// uninstall redundant jobs-module
$modops = ModuleOperations::get_instance();
$mod = $modops->get_module_instance('CmsJobManager', '', true);
if ($mod) { //should fail (files gone now)
    $mod->Uninstall();
} else {
    // relevant parts of $modops->UninstallModule()
    Events::RemoveEventTypedHandler('Core', 'ModuleUninstalled', 'CmsJobManager', Events::HANDLERMOD, true); // includes order-adjustment
    $db->Execute("DELETE FROM {$pref}events WHERE originator='CmsJobManager'");
    $db->Execute("DELETE FROM {$pref}siteprefs WHERE sitepref_name LIKE 'CmsJobManager_mapi_pref%'");
    $db->Execute("DELETE FROM {$pref}module_deps WHERE parent_module='CmsJobManager'"); // unlikely
    $db->Execute("DELETE FROM {$pref}modules WHERE module_name='CmsJobManager'");
    $sqlarray = $dict->DropTableSQL("{$pref}mod_cmsjobmgr");
    $dict->ExecuteSQLArray($sqlarray);
    CmsApp::get_instance()->clear_cached_files();
}
audit('', 'System 2.2.23F2 upgrade', 'CmsJobManager uninstalled');
