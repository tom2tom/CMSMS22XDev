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
elseif (isset($_GET['title'])) {
	// adding an admin url from the bookmarks popup
	$tmp = trim($_GET['title']); //TODO support cleanValue()
	$title = urldecode($tmp);
}

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
	//this validation should be in a standalone function, for use by
	//both add- and edit-bookmark scripts
	//see also cms_utils::validate_url()
	$url = str_replace('[ROOT_URL]', CMS_ROOT_URL, $url);
	$extsub = substr($urlext, 1);
	if (strpos($url, '[SECURITYTAG]') !== false) { // deprecated
		$url = str_replace('[SECURITYTAG]', $extsub, $url); // allow parsing
	}

	// mimic FILTER_SANITIZE_URL, but allowing relevant valid UTF-8 and extended-ASCII chars
	// see also cms_utils::cleanUrlPath(), cms_utils::validate_url()
	if (preg_match('/[^\x20-\x7e\pL\p{Nd}\p{Po}\x82-\x84\x88\x8a\x8c\x8e\x91-\x94\x96-\x98\x9a\x9c\x9e\x9f\xa8\xad\xb4\xb7\xb8\xc0-\xf6\xf8-\xff]/u', $url)) {
		unset($_POST['addbookmark']);
		$error = lang('illegalcharacters', lang('url'));
	}
	else {
		$validurl = function($checkurl, array $blockhosts = []) {
			$parts = parse_url($checkurl);
			if ($parts) {
				if (empty($parts['scheme'])) {
					return false;
				}
				$val = strtolower($parts['scheme']);
				// lots of valid schemes https://en.wikipedia.org/wiki/List_of_URI_schemes
				// TODO also exclude ws[s]?
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
/*
				// typo? TODO send correction upstream
				$p1 = 0.0;
				foreach (['https', 'http'] as $check) {
					similar_text($val, $check, $p1);
					if ($p1 > 60 && $p1 < 100) {
//						$url = str_replace($val, $check, $url);
//						$val = $check;
						break;
					}
				}
*/
				if (empty($parts['host']) ||
					($blockhosts && in_array(strtolower($parts['host']), array_map('strtolower', $blockhosts)))) { // caseless check
					return false;
				}
				//TODO other sanity checks, malevolence checks
				//e.g. refer to https://owasp.org/www-community/attacks/Forced_browsing
				//www.example.com/function.jsp?fwd=admin.jsp
				//www.example.com/example.php?url=http://malicious.example.com
				//TODO other url-part(s) ok ? i.e. port user pass query fragment
				return true;
			}
			return false;
		};

		//$sitehost = parse_url(CMS_ROOT_URL, PHP_URL_HOST);
		//treated as ok for frontend urls (MAMS aside?)
		//TODO blacklisted hosts? e.g. a site-preference
		if (!$validurl($url, [])) {
			unset($_POST['addbookmark']);
			$error = lang('error_badfield', lang('url'));
		}

		$url = str_replace($extsub, '[SECURITYTAG]', $url); // if any
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
	}
}

if (isset($_POST['addbookmark'])) {
	$validinfo = true;

	if ($title == '') {
		$error .= lang('nofieldgiven', [lang('title')]);
		$validinfo = false;
	}
	elseif ($url == '') {
		$error .= lang('nofieldgiven', [lang('url')]);
		$validinfo = false;
	}

	if ($validinfo) {
		$markobj = new Bookmark();
		$markobj->title = $title;
		$markobj->url = $url;
		$markobj->user_id = get_userid();

		$result = $markobj->save();

		if ($result) {
			redirect('listbookmarks.php'.$urlext);
		}
		else {
			$error .= lang('errorinsertingbookmark');
		}
	}
}

require_once 'header.php';

$smarty = Smarty_CMS::get_instance();
$smarty->assign('error',$error);
$smarty->assign('header',$themeObject->ShowHeader('addbookmark'));
$smarty->assign('hiddenname',CMS_SECURE_PARAM_NAME);
$smarty->assign('hiddenval',$_SESSION[CMS_USER_KEY]);
$smarty->assign('title',$title);
$smarty->assign('url',$url);
$smarty->display('addbookmark.tpl');

require_once 'footer.php';

?>
