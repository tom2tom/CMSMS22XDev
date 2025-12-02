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

//deal with deprecated css media-types
$deprec = ['aural','speech','braille','embossed','handheld','projection','tty','tv'];
$repl =   ['','','','','screen','','print','screen'];
$sql = "UPDATE {$pre}flayout_stylesheets SET media_type=? WHERE id=?";
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
                $db->execute($sql, [$newtype, $id]);
            }
        } else {
            $db->execute($sql2, [$id]);
            $db->execute($sql3, [$id]);
        }
    }
}
