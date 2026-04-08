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
$secureparm = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

if (isset($_POST['cancel'])) {
	redirect('listbookmarks.php?'.$secureparm);
}

$title = '';
if (isset($_POST['title'])) {
	$title = trim(cleanValue($_POST['title']));
}
elseif (isset($_GET['title'])) {
	// adding an admin url from the bookmarks popup
	$tmp = trim($_GET['title']); //TODO support cleanValue()
	$title = urldecode($tmp);
}

$error = '';
$url = '';
if (isset($_POST['url'])) {
	$url = trim(cleanValue($_POST['url']));
}
elseif (isset($_GET['ref'])) {
	// adding an admin url
	$tmp = trim(cleanValue($_GET['ref']));
	$url = base64_decode($tmp, true);
}
if ($url) {
	$url = html_entity_decode($url);
	$url = urldecode($url);
	$url = str_replace('[ROOT_URL]', CMS_ROOT_URL, $url);
	if (strpos($url, '[SECURITYTAG]') !== false) { // deprecated
		$url = str_replace('[SECURITYTAG]', $secureparm, $url); // allow parsing
	}

	$res = cms_utils::validate_url($url, '!'.CMSMS\FileType::TYPE_EXECUTABLE);
	if ($res !== true) {
		$error = $res;
		unset($_POST['addbookmark']);
	}

	// reinstate placeholder if any
	$url = str_replace($secureparm, '[SECURITYTAG]', $url);
	$config = cms_config::get_instance();
	if (startswith($url, $config['admin_url'])) {
		//TODO somewhere apply a permission-check akin to admin menu generation
		if (strpos($url, '[SECURITYTAG]') === false) {
			unset($_POST['addbookmark']);
			$error = lang('error_badfield', lang('url')); //repetition ok
		}
	}
	elseif (strpos($url, '[SECURITYTAG]') !== false) {
		unset($_POST['addbookmark']);
		$error = lang('error_badfield', lang('url')); //repetition ok
	}
} // url

if (isset($_POST['addbookmark'])) {
	$validinfo = true;
	if ($title == '') {
		$error .= lang('nofieldgiven', lang('title'));
		$validinfo = false;
	}
	elseif ($url == '') {
		$error .= lang('nofieldgiven', lang('url')); // joined error string?
		$validinfo = false;
	}

	if ($validinfo) {
		$markobj = new Bookmark();
		$markobj->title = $title;
		$markobj->url = $url; // revert any encoding removed during parsing ?
		$markobj->user_id = get_userid();

		$result = $markobj->save();

		if ($result) {
			redirect('listbookmarks.php?'.$secureparm);
		}
		else {
			$error .= lang('errorinsertingbookmark');
		}
	}
}

require_once 'header.php';
$themeObject->set_value('pagetitle', 'addbookmark');

$tpl = $smarty->createTemplate('admin_tpl:addbookmark.tpl', null, null, $smarty, false);
$tpl->assign('error', $error);
$tpl->assign('securename', CMS_SECURE_PARAM_NAME); // see also $smarty-assigned var $secureparam
$tpl->assign('secureval', $_SESSION[CMS_USER_KEY]);
$tpl->assign('title', $title);
$tpl->assign('url', $url);
$tpl->display();

require_once 'footer.php';
