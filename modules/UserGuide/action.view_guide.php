<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/

use UserGuide\UserGuideUtils;

if (!isset($gCms)) exit;

/* any admin user may view content
if (!($this->CheckPermission(UserGuide::MANAGE_PERM) ||
      $this->CheckPermission(UserGuide::USE_PERM))) {
    return;
}
*/
if (isset($params['close'])) {
    $this->RedirectToAdminTab('list');
}

$sql = 'SELECT name,smarty,template_id,sheets,content FROM '.CMS_DB_PREFIX.'module_userguide WHERE id=? AND active>0';
$row = $db->GetRow($sql, [$params['gid']]);

if (!$row) {
    $this->SetError(lang('error_badfield', 'User Guide id'));
    $this->RedirectToAdminTab('list');
}

// adjust values for display e.g. strip_tags, entitize, ...
$clean = preg_replace(['/<[^>]*>/', '/<\s*\?\s*php.*$/i', '/<\s*\?\s*=?.*$/'], ['', '', ''], trim((string)$row['name']));
$name = strtr($clean, ["\0"=>'', "'"=>'&#39;', '"'=>'&#34;']);

$clean = UserGuideUtils::cleanContent($row['content']);
//format for display TODO common code to Utils method?
$content = preg_replace([
    '~<img src="(?!https?://)([^"]*)"~', //relative URLs to absolute
    '~ *<br ?/?>~',
    '~ *\r?\n~',
    '~ *\r~',
    '~\n{3,}~',
    '~([^>])\n\n~',
    '~([^>])\n~'
    ], [
    "<img src=\"{$config['root_url']}/\$1\"",
    "\n",
    "\n",
    "\n",
    "\n\n",
    "$1<br><br>\n",
    "$1<br>\n"
    ], $clean);

if ($row['smarty']) {
    try {
        $content2 = $smarty->fetch('string:'.$content);
    } catch (Exception $e) {
        $this->SetError($this->Lang('err_smarty').' ('.$e->GetMessage().')');
        $this->RedirectToAdminTab('list');
    }
    $tpl = $smarty->CreateTemplate($this->GetTemplateResource('view_guide.tpl'), null, null, $smarty);
    $tpl->assign('content', $content2);
    $tpl->assign('name', $name);
    try {
        $tpl->display();
    } catch (Exception $e) {
        $this->SetError('Smarty processing failed. '.$e->GetMessage()); //TODO langify
        $this->RedirectToAdminTab('list');
    }
} else {
    $tpl = $smarty->CreateTemplate($this->GetTemplateResource('view_guide.tpl'), null, null, $smarty);
    $tpl->assign('content', $content);
    $tpl->assign('name', $name);
    $tpl->display();
}
?>
