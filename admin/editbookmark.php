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
	//this validation should be in a standalone function, for use by both
	//add- and edit-bookmark scripts
	$url = str_replace('[ROOT_URL]', CMS_ROOT_URL, $url);
	$extsub = substr($urlext, 1);
	if (strpos($url, '[SECURITYTAG]') !== false) { // deprecated
		$url = str_replace('[SECURITYTAG]', $extsub, $url);
	}

	// mimic FILTER_SANITIZE_URL, allowing valid UTF-8 and extended-ASCII chars
	if (preg_match('/[^\x21-\x7e\p{L}\p{N}\p{Po}\x82-\x84\x88\x8a\x8c\x8e\x91-\x94\x96-\x98\x9a\x9c\x9e\x9f\xa8\xad\xb4\xb7\xb8\xc0-\xf6\xf8-\xff]/u', $url)) {
		unset($_POST['editbookmark']);
		$error .= '<li>'.lang('illegalcharacters', lang('url')).'</li>';
	}
	else {
		$validurl = function(string $checkurl, array $blockhosts = []): bool {
			$parts = parse_url($checkurl);
			if ($parts) {
				if (empty($parts['scheme'])) {
					return false;
				}
				$val = strtolower($parts['scheme']);
				// lots of valid schemes https://en.wikipedia.org/wiki/List_of_URI_schemes
				if (in_array($val, [
				'attachment',
				'blob',
				'chrome',
				'cid',
				'data',
				'dns',
				'example',
				'file',
				'filesystem',
				'ftp',
				'query',
				'sftp',
				'tel',
				'tftp',
				'view-source',
				])) {
					return false;
				}
				// some typo checks
				similar_text($val, 'https', $p1);
				$near = ($p1 > 60 && $p1 < 100);
				if ($near) {
					similar_text($val, 'http', $p2);
					$near = ($p2 > 60 && $p2 < 100);
				}
				if ($near) {
					return false;
				}

				if (empty($parts['host'])
				 || in_array($parts['host'], $blockhosts)) {
					return false;
				}
				//TODO other sanity checks, malevolence checks
				//e.g. refer to https://owasp.org/www-community/attacks/Forced_browsing
				//www.example.com/function.jsp?fwd=admin.jsp
				//www.example.com/example.php?url=http://malicious.example.com
				return true;
			}
			return false;
		};

		//$sitehost = parse_url(CMS_ROOT_URL, PHP_URL_HOST);
		//treated as ok for frontend urls (MAMS aside?)
		//TODO blacklisted hosts?
		$reported = false;
		if (!$validurl($url, [])) {
			unset($_POST['editbookmark']);
			$error .= '<li>'.lang('error_badfield', lang('url')).'</li>';
			$reported = true;
		}

		$url = str_replace($extsub, '[SECURITYTAG]', $url); // if any
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
	}
}

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
		$markobj->url = $url;
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
