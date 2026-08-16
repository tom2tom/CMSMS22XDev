<?php

if (isset($CMS_INSTALL_DROP_TABLES)) {

	status_msg(ilang('install_dropping_tables'));
	$table_ids = array(
	'additional_users',
	'admin_bookmarks',
	'adminlog',
	'content',
	'event_handlers',
	'events',
	'group_perms',
	'groups',
	'jobs',
	'jobs_record',
	'module_deps',
	'module_smarty_plugins',
	'module_templates',
	'modules',
	'permissions',
	'routes',
	'siteprefs',
	'user_groups',
	'userplugins',
	'userprefs',
	'users',
	'version',
	CmsLayoutTemplateType::TABLENAME,
	CmsLayoutTemplateCategory::TABLENAME,
	CmsLayoutTemplate::TABLENAME,
	CmsLayoutTemplate::ADDUSERSTABLE,
	CmsLayoutStylesheet::TABLENAME,
	CmsLayoutCollection::TABLENAME,
	CmsLayoutCollection::TPLTABLE,
	CmsLayoutCollection::CSSTABLE,
	CmsLock::LOCK_TABLE
	);
	$pref = CMS_DB_PREFIX;
	$fmt = "DROP TABLE IF EXISTS `{$pref}%s`";
	foreach ($table_ids as $tablename) {
		$sql = sprintf($fmt, $tablename);
		$db->Execute($sql);
		usleep(20000);
	}
}

if (isset($CMS_INSTALL_CREATE_TABLES)) {

	status_msg(ilang('install_createtablesindexes'));

	@$db->Execute("ALTER DATABASE `" . $db->database . "` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci"); // OR 4 ?

	$dbdict = NewDataDictionary($db);
	$taboptarray = array('mysqli' => 'ENGINE MyISAM');
	$success = ilang('done');
	$failed = ilang('failed');

	$flds = "
additional_users_id I KEY,
user_id I,
page_id I,
content_id I";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."additional_users", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'additional_users', $ado_ret));

	$flds = "
bookmark_id I KEY,
user_id I,
title C(255),
url C(255)";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."admin_bookmarks", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'admin_bookmarks', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'index_admin_bookmarks_by_user_id', CMS_DB_PREFIX."admin_bookmarks", 'user_id');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'admin_bookmarks', $ado_ret));

	$flds = "
timestamp I,
user_id I,
username C(25),
item_id I,
item_name C(50),
action C(255),
ip_addr C(40)";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."adminlog", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	if( $return == 2 ) {
		$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'index_adminlog1',CMS_DB_PREFIX."adminlog",'timestamp');
		$return = $dbdict->ExecuteSQLArray($sqlarray);
	}
	verbose_msg(ilang('install_created_table', 'adminlog', $ado_ret));

	$flds = "
content_id I KEY,
content_name C(255),
type C(25),
owner_id I,
parent_id I,
template_id I,
item_order I,
hierarchy C(255),
default_content I1 UNSIGNED DEFAULT 0,
menu_text C(255),
content_alias C(255),
show_in_menu I1 UNSIGNED DEFAULT 1,
active I1 UNSIGNED DEFAULT 1,
cachable I1 UNSIGNED DEFAULT 1,
id_hierarchy C(255),
hierarchy_path X,
prop_names X,
metadata X,
titleattribute C(255),
tabindex C(10),
accesskey C(5),
last_modified_by I,
create_date DT,
modified_date DT,
secure I1 UNSIGNED DEFAULT 0,
page_url C(255)";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."content", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'content', $ado_ret));
	//fields: type,hierarchy,id_hierarchy,tabindex (and maybe single-char accesskey)
	//could/should be stored as ascii instead of multi-byte
	$sql = <<<EOS
ALTER TABLE `{$pref}content`
MODIFY COLUMN `type` varchar(25) CHARACTER SET ascii COLLATE ascii_bin,
MODIFY COLUMN `hierarchy` varchar(255) CHARACTER SET ascii COLLATE ascii_bin,
MODIFY COLUMN `id_hierarchy` varchar(255) CHARACTER SET ascii COLLATE ascii_bin,
MODIFY COLUMN `tabindex` varchar(10) CHARACTER SET ascii COLLATE ascii_bin
EOS;
	$db->Execute($sql);

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_content_by_alias_active', CMS_DB_PREFIX."content", 'content_alias, active');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_content_by_alias_active', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_content_default_content', CMS_DB_PREFIX."content", 'default_content');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_content_default_content', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_content_by_parent_id', CMS_DB_PREFIX."content", 'parent_id');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_content_by_parent_id', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_content_by_hier', CMS_DB_PREFIX."content", 'hierarchy');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_content_by_hier', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'index_content_by_idhier', CMS_DB_PREFIX."content", 'content_id, hierarchy');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_content_by_idhier', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_content_by_modified', CMS_DB_PREFIX."content", 'modified_date');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_content_by_modified', $ado_ret));

	$flds = "
content_id I,
type C(25),
prop_name C(255),
param1 C(255),
param2 C(255),
param3 C(255),
content X2,
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."content_props", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'content_props', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_content_props_by_content', CMS_DB_PREFIX."content_props", 'content_id');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_content_props_by_content', $ado_ret));

	$flds = "
handler_id I KEY,
event_id I NOTNULL,
handler C(255) NOTNULL,
handler_type I1 UNSIGNED NOTNULL,
handler_order I1 UNSIGNED DEFAULT 0,
removable I1 UNSIGNED DEFAULT 1";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."event_handlers", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'event_handlers', $ado_ret));

	$flds = "
event_id I KEY,
originator C(160) NOTNULL,
event_name C(200) NOTNULL";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."events", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'events', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'originator', CMS_DB_PREFIX."events", 'originator');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'originator', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'event_name', CMS_DB_PREFIX."events", 'event_name');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'event_name', $ado_ret));

	$flds = "
group_perm_id I KEY,
group_id I,
permission_id I,
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."group_perms", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'group_perms', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_grp_perms_by_grp_id_perm_id', CMS_DB_PREFIX."group_perms", 'group_id, permission_id');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_grp_perms_by_grp_id_perm_id', $ado_ret));

	$flds = "
group_id I KEY,
group_name C(25),
group_desc C(255),
active I1 UNSIGNED DEFAULT 1,
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL("`".CMS_DB_PREFIX."groups`", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'groups', $ado_ret));

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
	$tbl = CMS_DB_PREFIX.'jobs';
	$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'jobs', $ado_ret));
	$sql = <<<EOS
ALTER TABLE `$tbl`
CHANGE `module` `module` varchar(160) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL
EOS;
	$db->Execute($sql);

	$flds = '
id I UNSIGNED NOTNULL,
propname C(255) NOTNULL,
value I UNSIGNED';
	$tbl = CMS_DB_PREFIX.'jobs_record';
	$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, ['mysqli' => 'ENGINE MyISAM CHARACTER SET ascii']);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'jobs_record', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL('i_id', $tbl, 'id');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dbdict->CreateIndexSQL('i_propname', $tbl, 'propname');
	$return = $dbdict->ExecuteSQLArray($sqlarray);

	$flds = "
module_name C(160) KEY,
status C(255),
version C(255),
admin_only I1 DEFAULT 0,
active I1 UNSIGNED DEFAULT 1,
allow_fe_lazyload I1 UNSIGNED DEFAULT 1,
allow_admin_lazyload I1 UNSIGNED DEFAULT 1";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."modules", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'modules', $ado_ret));

	$flds = "
parent_module C(25),
child_module C(25),
minimum_version C(25),
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."module_deps", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'module_deps', $ado_ret));

	// deprecated
	$flds = "
module_name C(160),
template_name C(160),
content X,
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."module_templates", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'module_templates', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_module_templates_by_module_and_tpl_name', CMS_DB_PREFIX."module_templates", 'module_name, template_name');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_module_templates_by_module_and_tpl_name', $ado_ret));

	$flds = "
permission_id I KEY,
permission_name C(255),
permission_text C(255),
permission_source C(160),
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."permissions", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'permissions', $ado_ret));

	$flds = "
sitepref_name C(255) KEY,
sitepref_value text,
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."siteprefs", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'siteprefs', $ado_ret));

	// compound primary index
	$flds = "
group_id I KEY,
user_id I KEY,
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."user_groups", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'user_groups', $ado_ret));


	// compound primary index
	$flds = "
user_id I KEY,
preference C(50) KEY,
value X,
type C(25)";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."userprefs", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'userprefs', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_userprefs_by_user_id', CMS_DB_PREFIX."userprefs", 'user_id');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_userprefs_by_user_id', $ado_ret));

	$flds = "
user_id I KEY,
username C(25),
password C(128),
admin_access I1 UNSIGNED DEFAULT 1,
first_name C(50),
last_name C(50),
email C(255),
active I1 UNSIGNED DEFAULT 1,
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."users", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	$pref = CMS_DB_PREFIX;
	$sql = <<<EOS
	ALTER TABLE `{$pref}users`
	CHANGE `password` `password` varchar(128) CHARACTER SET ascii COLLATE ascii_bin
EOS;
	$db->Execute($sql);
	verbose_msg(ilang('install_created_table', 'users', $ado_ret));

	$flds = "
userplugin_id I KEY,
userplugin_name C(255),
code X,
description X,
create_date DT,
modified_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."userplugins", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'userplugins', $ado_ret));

	$flds = "
version I2 UNSIGNED";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."version", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'version', $ado_ret));

	$flds = "
sig C(80) KEY,
name C(80) NOTNULL,
module C(160) NOTNULL,
type C(40) NOTNULL,
callback C(255) NOTNULL,
available I1 UNSIGNED DEFAULT 1,
cachable I1 UNSIGNED DEFAULT 1";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."module_smarty_plugins", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'module_smarty_plugins', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_smp_module', CMS_DB_PREFIX."module_smarty_plugins", 'module');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_smp_module', $ado_ret));

	// compound primary index
	$flds = "
term C(255) KEY,
key1 C(50) KEY,
key2 C(50),
key3 C(50),
data X,
create_date DT";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX."routes", $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', 'routes', $ado_ret));

	$flds = "
id I KEY AUTO,
originator C(160) NOTNULL,
name C(100) NOTNULL,
has_dflt I1 UNSIGNED DEFAULT 0,
dflt_contents X2,
description X,
lang_cb C(255),
dflt_content_cb C(255),
requires_contentblocks I1 UNSIGNED DEFAULT 0,
help_content_cb C(255),
one_only I1 UNSIGNED DEFAULT 1,
owner I,
created I,
modified I";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME, $flds,
					 $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', CmsLayoutTemplateType::TABLENAME, $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_layout_tpl_type_1', CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME,
										'originator,name',array('UNIQUE'));
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_layout_tpl_type_1', $ado_ret));

	$flds = "
id I KEY AUTO,
name C(100) NOTNULL,
description X,
item_order I1 UNSIGNED DEFAULT 0,
modified I";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME, $flds,
					 $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', CmsLayoutTemplateCategory::TABLENAME, $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_layout_tpl_cat_1', CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME,
										'name',array('UNIQUE'));
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_layout_tpl_type_1', $ado_ret));

	$flds = "
id I KEY AUTO,
name C(100) NOTNULL,
content X2,
description X,
type_id I NOTNULL,
type_dflt I1 UNSIGNED DEFAULT 0,
category_id I,
owner_id I NOTNULL,
listable I1 UNSIGNED DEFAULT 1,
created I,
modified I";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME, $flds,
					 $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', CmsLayoutTemplate::TABLENAME, $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_layout_tpl_1', CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME, 'name',array('UNIQUE'));
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_layout_tpl_1', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_layout_tpl_2', CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME, 'type_id,type_dflt');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_creating_index', 'idx_layout_tpl_2', $ado_ret));

	$flds = "
id I KEY AUTO,
name C(100) NOTNULL,
content X2,
description X,
media_type C(255),
media_query X,
created I,
modified I";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.CmsLayoutStylesheet::TABLENAME, $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', CmsLayoutStylesheet::TABLENAME, $ado_ret));
	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_layout_css_1',CMS_DB_PREFIX.CmsLayoutStylesheet::TABLENAME, 'name', array('UNIQUE'));
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_index', 'idx_layout_css_1', $ado_ret));

	// compound primary index
	$flds = "
tpl_id I KEY,
user_id I KEY";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.CmsLayoutTemplate::ADDUSERSTABLE, $flds,
					 $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', CmsLayoutTemplate::ADDUSERSTABLE, $ado_ret));

	$flds = "
id I KEY AUTO,
name C(100) NOTNULL,
description X,
version C(20) DEFAULT '1.0',
requires C(320),
dflt I1 UNSIGNED DEFAULT 0,
created I,
modified I";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.CmsLayoutCollection::TABLENAME, $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', CmsLayoutCollection::TABLENAME, $ado_ret));
	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'idx_layout_dsn_1',CMS_DB_PREFIX.CmsLayoutCollection::TABLENAME, 'name', array('UNIQUE'));
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_index', 'idx_layout_dsn_1', $ado_ret));

	// compound primary index
	$flds = "
design_id I KEY,
tpl_id  I KEY";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE, $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', CmsLayoutCollection::TPLTABLE, $ado_ret));
	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'index_dsnassoc1', CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE, 'tpl_id');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_index', 'index_dsnassoc1', $ado_ret));

	// compound primary index
	$flds = "
design_id I KEY,
css_id I KEY,
item_order I1 UNSIGNED DEFAULT 1";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE, $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', CmsLayoutCollection::CSSTABLE, $ado_ret));

	$flds = "
id I KEY AUTO,
type C(20) NOTNULL,
oid I NOTNULL,
uid I NOTNULL,
created I NOTNULL,
modified I NOTNULL,
lifetime I NOTNULL,
expires I NOTNULL";
	$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.CmsLock::LOCK_TABLE, $flds, $taboptarray);
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_table', CmsLock::LOCK_TABLE, $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'index_locks1', CMS_DB_PREFIX."locks", 'type,oid', array('UNIQUE'));
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_index', 'index_locks1', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'index_locks2', CMS_DB_PREFIX."locks", 'expires');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_index', 'index_locks2', $ado_ret));

	$sqlarray = $dbdict->CreateIndexSQL(CMS_DB_PREFIX.'index_locks3', CMS_DB_PREFIX."locks", 'uid');
	$return = $dbdict->ExecuteSQLArray($sqlarray);
	$ado_ret = ($return == 2) ? $success : $failed;
	verbose_msg(ilang('install_created_index', 'index_locks3', $ado_ret));
}
?>
