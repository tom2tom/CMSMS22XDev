<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

use UserGuide\UserGuideItem;
use UserGuide\UserGuideQuery;

if (!isset($gCms)) {
    exit;
}
if (!$this->CheckPermission(UserGuide::MANAGE_PERM)) {
    return;
}

if (isset($params['gid']) && $params['gid'] > 0) {
    $item = UserGuideItem::load_by_id((int)$params['gid']);
    if ($item) {
        if ($item->delete()) {
            $query = new UserGuideQuery();
            $updated = $query->updatePositions(); // $updated unused
            $this->SetMessage($this->Lang('item_deleted'));
        } else {
            //TODO report error
        }
    } else {
        //TODO report error
    }
}

$this->RedirectToAdminTab('list');