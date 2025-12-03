<?php
#CMS Made Simple admin console script
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$

$CMS_ADMIN_PAGE = 1;

require_once '../lib/include.php';
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

if (isset($_POST['cancel'])) {
	redirect('listbookmarks.php'.$urlext);
}

$error = '';
$title = '';
if (isset($_POST['title'])) {
	$title = trim(cleanValue($_POST['title']));
}

$url = '';
if (isset($_POST['url'])) {
	$url = trim(cleanValue($_POST['url']));
}
if ($url) {
	$url = html_entity_decode($url);
	$url = urldecode($url);
	$url = str_replace('[ROOT_URL]', CMS_ROOT_URL, $url);
	$extsub = substr($urlext, 1);
	if (strpos($url, '[SECURITYTAG]') !== false) { // deprecated
		$url = str_replace('[SECURITYTAG]', $extsub, $url); // allow parsing
	}

	$res = cms_utils::validate_url($url, '!executable'); // aka '!'.CMSMS\FileType::TYPE_EXECUTABLE
	if ($res !== true) {
		$error .= '<li>'.$res.'</li>';
		unset($_POST['editbookmark']);
	}

	// revert placeholder if any
	$url = str_replace($extsub, '[SECURITYTAG]', $url);
	$config = cms_config::get_instance();
	if (startswith($url, $config['admin_url'])) {
		//TODO somewhere apply a permission-check akin to admin menu generation
		if (strpos($url, '[SECURITYTAG]') === false) {
			unset($_POST['editbookmark']);
			if (!$reported) { // don't repeat same error
				$error .= '<li>'.lang('error_badfield', lang('url')).'</li>';
			}
		}
	}
	elseif (strpos($url, '[SECURITYTAG]') !== false) {
		unset($_POST['editbookmark']);
		if (!$reported) {
			$error .= '<li>'.lang('error_badfield', lang('url')).'</li>';
		}
	}
} // url

$bookmark_id = -1;
if (isset($_POST['bookmark_id'])) {
	$bookmark_id = (int)$_POST['bookmark_id'];
}
elseif (isset($_GET['bookmark_id'])) {
	$bookmark_id = (int)$_GET['bookmark_id'];
}

$userid = get_userid();

if (isset($_POST['editbookmark'])) {
	$validinfo = true;
	if ($title == '') {
		$validinfo = false;
		$error .= '<li>'.lang('nofieldgiven', [lang('title')]).'</li>';
	}
	if ($url == '') {
		$validinfo = false;
		$error .= '<li>'.lang('nofieldgiven', [lang('url')]).'</li>';
	}

	if ($validinfo) {
		$markobj = new Bookmark();
		$markobj->bookmark_id = $bookmark_id;
		$markobj->title = $title;
		$markobj->url = $url; // revert any encoding removed during parsing ?
		$markobj->user_id = $userid;

		$result = $markobj->save();

		if ($result) {
			redirect('listbookmarks.php'.$urlext);
		}
		else {
			$error .= '<li>'.lang('errorupdatingbookmark').'</li>';
		}
	}
}
elseif ($bookmark_id != -1) {
	$db = cmsms()->GetDb();
	$query = 'SELECT * from '.CMS_DB_PREFIX.'admin_bookmarks WHERE bookmark_id = ?';
	$result = $db->Execute($query, [$bookmark_id]);
	if ($result) {
		$row = $result->FetchRow();
		foreach (['title', 'url'] as $fld) {
			if ($row[$fld] === null) {
				$row[$fld] = '';
			}
		}
		$url = $row['url'];
		$title = $row['title'];
		$result->Close();
	}
}

require_once 'header.php';

$smarty = Smarty_CMS::get_instance();
$smarty->assign('error',$error);
$smarty->assign('header',$themeObject->ShowHeader('editbookmark'));
$smarty->assign('hiddenname',CMS_SECURE_PARAM_NAME);
$smarty->assign('hiddenval',$_SESSION[CMS_USER_KEY]);
$smarty->assign('bookmark_id',$bookmark_id);
$smarty->assign('userid',$userid);
$smarty->assign('title',$title);
$smarty->assign('url',$url);
$smarty->display('editbookmark.tpl');

require_once 'footer.php';

?>
