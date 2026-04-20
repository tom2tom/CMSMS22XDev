<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/
// record positions of guides re-ordered by DnD and submitted via ajax

if (!isset($gCms)) {
    exit;
}
if (!$this->CheckPermission(UserGuide::MANAGE_PERM)) {
    exit;
}
if (!empty($params['idlist'])) {
    $pos = 1;
    $allids = explode(',', trim($params['idlist'], ' []'));
    $sql = 'UPDATE '.CMS_DB_PREFIX.'module_userguide SET position=? WHERE id=?'; // TODO prepared statement
    foreach ($allids as $gid) {
        $db->Execute($sql, [$pos++, (int)$gid]);
    }
}
exit;
