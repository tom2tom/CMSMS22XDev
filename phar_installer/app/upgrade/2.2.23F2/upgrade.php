<?php
use function __appbase\joinpath;

//new folder
$fp = joinpath($config['admin_path'],'configs','private');
if (!is_dir($fp)) {
    $perms = 0777; //TODO suitable new-folder permissions e.g. 0777 & ~umask()
    @mkdir($fp,$perms,true);
    touch($fp.DIRECTORY_SEPARATOR.'index.html');
}
//new preference related to database config
cms_siteprefs::set('privatePath',"\$config['admin_path'],configs,private");

Events::CreateEvent('Core','LoginPre');
Events::CreateEvent('Core','LogoutPre');

//backup users table
$pref = CMS_DB_PREFIX;
$db->Execute("DROP TABLE IF EXISTS `{$pref}users_oldhashbackup`");
$db->Execute("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
$db->Execute("CREATE TABLE `{$pref}users_oldhashbackup` LIKE `{$pref}users`");
$db->Execute("INSERT INTO `{$pref}users_oldhashbackup` SELECT * FROM `{$pref}users`");

//content table fields: type,hierarchy,id_hierarchy,tabindex
//stored as ascii instead of multi-byte
$sql = <<<EOS
ALTER TABLE `{$pref}content`
CHANGE `type` `type` varchar(25) CHARACTER SET ascii COLLATE ascii_bin,
CHANGE `hierarchy` `hierarchy` varchar(255) CHARACTER SET ascii COLLATE ascii_bin,
CHANGE `id_hierarchy` `id_hierarchy` varchar(255) CHARACTER SET ascii COLLATE ascii_bin,
CHANGE `tabindex` `tabindex` varchar(10) CHARACTER SET ascii COLLATE ascii_bin
EOS;
$db->Execute($sql);

//ibid for hashed user-passwords
$sql = <<<EOS
ALTER TABLE `{$pref}users`
CHANGE `password` `password` varchar(128) CHARACTER SET ascii COLLATE ascii_bin
EOS;
$db->Execute($sql);

$db->Execute("UPDATE {$pref}version SET version = 203");
