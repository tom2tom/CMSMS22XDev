<?php
#-------------------------------------------------------------------------
# OneEleven- an admin theme for CMS Made Simple
# (c) 2012 Goran Ilic (ja@ich-mach-das.at) http://dev.cmsmadesimple.org/users/uniqu3
# (c) 2012 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

use CMSMS\AdminAlerts\Alert;

class OneElevenTheme extends CmsAdminThemeBase
{
	// admin menu item names with corresponding topfiles icons
	private $_topaliases = array (
/* no top icon for:
		'addbookmark' => '',
		'addgroup' => '',
		'adduser' => '',
		'editbookmark' => '',
		'editeventhandler' => '',
		'editgroup' => '',
		'edituser' => '',
		'editusertag' => '',
		'home' => '',
		'listjobs' => '',
		'logout' => '',
		'A' => 'blobs.png',
		'B' => 'cmsprinting.png',
		'C' => 'images.png',
		'D' => 'modules.png',
		'E' => 'pagedefaults.png',
		'F' => 'preferences.png',
		'G' => 'stylesheets.png',
		'H' => 'template.png',
		'I' => 'viewsite.png', //see pages.png
*/
		'adminlog' => 'adminlog.png',
		'checksum' => 'checksum.png',
		'content' => 'content.png',
		'ecommerce' => 'ecommerce.png',
		'eventhandlers' => 'eventhandlers.png',
		'extensions' => 'extensions.png',
		'files' => 'files.png',
		'groupmembers' => 'groupmembers.png',
		'groupperms' => 'groupperms.png',
		'groups' => 'groups.png',
		'layout' => 'layout.png',
		'main' => 'main.png',
		'managebookmarks' => 'managebookmarks.png',
		'myaccount' => 'myaccount.png',
		'myprefs' => 'myprefs.png',
		'siteadmin' => 'siteadmin.png',
		'siteprefs' => 'siteprefs.png',
		'systeminfo' => 'systeminfo.png',
		'systemmaintenance' => 'systemmaintenance.png',
		'tags' => 'tags.png',
		'users' => 'users.png',
		'usersgroups' => 'usersgroups.png',
		'usertags' => 'usertags.png',
		'viewsite' => 'pages.png'
	);

	public function ShowErrors($errors, $get_var = '') {
		// cache errors for use in the template.
		if ($get_var && !empty($_GET[$get_var])) {
			if (is_array($_GET[$get_var])) {
				foreach ($_GET[$get_var] as $one) {
					$this->_errors[] = lang(cleanValue($one));
				}
			} else {
				$this->_errors[] = lang(cleanValue($_GET[$get_var]));
			}
		} elseif (is_array($errors)) {
			foreach ($errors as $one) {
				$this->_errors[] = $one;
			}
		} elseif (is_string($errors)) {
			$this->_errors[] = $errors;
		}
		return '<!-- OneEleven::ShowErrors() called -->';
	}

	public function ShowMessage($message, $get_var = '') {
		// cache message for use in the template.
		if ($get_var && !empty($_GET[$get_var])) {
			if (is_array($_GET[$get_var])) {
				foreach ($_GET[$get_var] as $one) {
					$this->_messages[] = lang(cleanValue($one));
				}
			} else {
				$this->_messages[] = lang(cleanValue($_GET[$get_var]));
			}
		} elseif (is_array($message)) {
			foreach ($message as $one) {
				$this->_messages[] = $one;
			}
		} elseif (is_string($message)) {
			$this->_messages[] = $message;
		}
	}

	/**
	 * Arrange to show a themed header in the content area of a page
	 *
	 * @deprecated since 1.11
	 * @param string $title_name The actual title, or translation-key of the
	 * title, to show in the header. This will be processed as a translation-key
	 * if $module_help_type is FALSE, or otherwise used verbatim.
	 * @see postprocess()
	 * @param array  $extra_lang_params Optional extra parameters to pass to
	 *  lang if $title_name is a lang key . Ignored unless $module_help_type is FALSE
	 * @param string $link_text Text to show in the module help link (depends on $module_help_type)
	 * @param mixed  $module_help_type Indicator for how to display module help types.
	 *  Recognized values are: TRUE to display a simple link, FALSE for no help,
	 *  and 'both' for both types of links.
	 * @return void
	 */
	public function ShowHeader($title_name, $extra_lang_params = array(), $link_text = '', $module_help_type = FALSE) {
		if ($title_name) $this->set_value('pagetitle', $title_name);
		if ($extra_lang_params && is_array($extra_lang_params)) $this->set_value('extra_lang_params', $extra_lang_params);

		$module = '';
		if (isset($_REQUEST['module'])) {
			$module = $_REQUEST['module'];
		} elseif (isset($_REQUEST['mact'])) {
			$tmp = explode(',', $_REQUEST['mact']);
			$module = $tmp[0];
		}

		// get the image url.
		if ($module) {
			$path = cms_join_path(CMS_ROOT_PATH, 'modules', $module, 'images', 'icon.png');
			if (!file_exists($path)) {
				$path = substr($path, 0, -3) . 'gif';
			}
			if (file_exists($path)) {
				$url = str_replace([CMS_ROOT_PATH, '\\'], [CMS_ROOT_URL, '/'], $path);
				$this->set_value('module_icon_url', $url);
			}

			$this->set_value('module_help_type', $module_help_type);
			if ($module_help_type) {
				// set the module help url (this should be supplied TO the theme)
				$module_help_url = $this->get_module_help_url();
				$this->set_value('module_help_url', $module_help_url);
			}
		}

		$bc = $this->get_breadcrumbs();
		if ($bc) {
			for ($i = 0; $i < count($bc); $i++) {
				$rec = $bc[$i];
				$title = $rec['title'];
				if ($module_help_type && $i + 1 == count($bc)) {
					$module_name = '';
					if (!empty($_GET['module'])) {
						$module_name = trim($_GET['module']);
					} else {
						$tmp = explode(',', $_REQUEST['mact']);
						$module_name = $tmp[0];
					}
					$orig_module_name = $module_name;
					$module_name = preg_replace('/([A-Z])/', "_$1", $module_name);
					$module_name = preg_replace('/_([A-Z])_/', "$1", $module_name);
					if ($module_name[0] == '_')
						$module_name = substr($module_name, 1);
				} else {
					if (($p = strrchr($title, ':')) !== FALSE) {
						$title = substr($title, 0, $p);
					}
					// find the key of the item with this title.
					$title_key = $this->find_menuitem_by_title($title);
				}
			}// for
		}
	}

	public function do_header() {
	}

	public function do_footer() {
	}

	public function do_toppage($section_name) {
		$smarty = Smarty_CMS::get_instance();
		$tpl = $smarty->createTemplate($this->GetTemplateResource('topcontent.tpl'), null, null, $smarty, FALSE);
		if ($section_name) {
			$tpl->assign('section_name', $section_name);
			$tpl->assign('pagetitle', lang($section_name));
			$nodes = $this->get_navigation_tree($section_name, -1, FALSE);
		} else {
			$nodes = $this->get_navigation_tree(-1, 2, FALSE);
		}
		foreach ($nodes as &$one) {
			$nm = $one['name'];
			$path = cms_join_path(CMS_ROOT_PATH, 'modules', $nm, 'images', 'icon.png');
			if (!file_exists($path)) {
				$path = substr($path, 0, -3) . 'gif';
			}
			if (file_exists($path)) {
				$one['img'] = str_replace([CMS_ROOT_PATH, '\\'], [CMS_ROOT_URL, '/'], $path);
			} elseif (isset($this->_topaliases[$nm])) {
				$one['img'] = "themes/{$this->themeName}/images/icons/topfiles/{$this->_topaliases[$nm]}";
			}
		}
		unset($one);
		$tpl->assign('nodes', $nodes);
		$tpl->assign('config', cms_config::get_instance());
		$tpl->assign('theme', $this);

		// is the website set down for maintenance?
		if (cms_siteprefs::get('enablesitedownmessage') == '1') { $tpl->assign('is_sitedown', 'true'); }

		$tpl->display();
	}

	public function do_login($params) {
		$smarty = Smarty_CMS::get_instance();
		$smarty->changeCaching(false);
		global $error,$warningLogin,$acceptLogin,$changepwhash; // needed?
		require_once __DIR__ . DIRECTORY_SEPARATOR . 'login.php';
		$tpl = $smarty->createTemplate($this->GetTemplateResource('login.tpl'), null, null, $smarty, false);
		$tpl->display();
	}

	public function postprocess($html) {
		// get a page title
		$title = $this->get_value('pagetitle');
		if ($title) {
			$module_help_type = $this->get_value('module_help_type');
			if (!$module_help_type) {
				// if not doing module help, translate the string.
				$extra = $this->get_value('extra_lang_params');
				if (!$extra) {
					$extra = array();
				}
				$title = lang($title, $extra);
			}
		} else {
			if ($this->title) {
				$title = $this->title;
			} else {
				// no title, get one from the breadcrumbs.
				$bc = $this->get_breadcrumbs();
				if (is_array($bc) && count($bc)) {
					$title = $bc[count($bc) - 1]['title'];
				}
			}
		}

		$smarty = Smarty_CMS::get_instance();
		$tpl = $smarty->createTemplate($this->GetTemplateResource('pagetemplate.tpl'),null,null,$smarty,false);

		// page title and alias
		$tpl->assign('pagetitle', $title);
		$tpl->assign('subtitle', $this->subtitle);
		$alias = $this->get_value('pagetitle');
		$tpl->assign('pagealias', ($alias ? munge_string_to_url($alias) : '')); //for use in classname

		// module name?
		if (($module_name = $this->get_value('module_name'))) {
			$tpl->assign('module_name', $module_name);
		}

		// module icon?
		if (($module_icon_url = $this->get_value('module_icon_url'))) {
			$tpl->assign('module_icon_url', $module_icon_url);
		}

		$userid = get_userid();
		// module_help_url
		if (!cms_userprefs::get_for_user($userid, 'hide_help_links', 0)) {
			if (($module_help_url = $this->get_value('module_help_url'))) {
				$tpl->assign('module_help_url', $module_help_url);
			}
		}

		// user preferences
		if (check_permission($userid, 'Manage My Settings')) {
			$tpl->assign('myaccount', 1);
		}

		$config = cms_config::get_instance();
		$urlext = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

		// bookmarks
		$marks = [];
		if (cms_userprefs::get_for_user($userid, 'bookmarks', 0)) {
			$ops = CmsApp::get_instance()->GetBookmarkOperations();
			$list = $ops->LoadBookmarks($userid);
		} else {
			$list = [];
		}
		if ($list) {
			$icon = $this->DisplayImage('icons/system/bookmark.png', 'bookmark', '', '', 'systemicon'); //TODO relevant alt
			foreach ($list as $obj) {
				$one = (array)$obj;
				unset($one['user_id']);
				$one['icon'] = $icon;
//				if (sometest) { $one['spacer'] = 1; }
//				$one['title'] = ?; open this display this ...
//TODO smart root url relevance ?
				if (startswith($one['url'], $config['admin_url'])) {
					$one['admin'] = 1;
					$one['verifyurl'] = 'TODOscript.php?'.$urlext;
				}
				$marks[] = $one;
			}
		}
		if (check_permission($userid, 'Manage My Bookmarks')) {
			if ($list) {
				$one = array_pop($marks);
				$one['spacer'] = 1;
				$marks[] = $one;
			}
			$path = substr(realpath($_SERVER['SCRIPT_FILENAME']), strlen(CMS_ROOT_PATH));
			$source = CMS_ROOT_URL . strtr($path, '\\', '/');
			if( !empty($_SERVER['QUERY_STRING']) ) { $source .= '?'.$_SERVER['QUERY_STRING']; }
			$source = str_replace($urlext, '[SECURITYTAG]', $source);
			$url = 'addbookmark.php?'.$urlext;
			if (!empty($title)) {
				$url .= '&title='.rawurlencode($title);
			}
			$url .= '&ref='.base64_encode($source);
			$tmp = lang('addthismark');
			$marks[] = [
				'bookmark_id' => 0,
				'title' => $tmp,
				'url' => $url,
				'icon' => $this->DisplayImage('icons/system/newobject.gif', $tmp, '', '', 'systemicon'),
			];
			$tmp = lang('managebookmarks');
			$marks[] = [
				'bookmark_id' => 0,
				'title' => $tmp,
				'url' => 'listbookmarks.php?'.$urlext,
				'icon' => $this->DisplayImage('icons/system/document-list.png', $tmp, '', '', 'systemicon')
			];
		}
		$tpl->assign('marks', $marks);
		$tpl->assign('headertext', $this->get_headtext());
		$tpl->assign('footertext', $this->get_footertext());

		// and some other common variables
		$tpl->assign('content', str_replace('</body></html>', '', $html));
		$tpl->assign('config', $config);
		$tpl->assign('theme', $this);
		$tpl->assign('secureparam', $urlext, true);
		$userops = UserOperations::get_instance();
		$tpl->assign('user', $userops->LoadUserByID($userid));
		if ($this->_errors && is_array($this->_errors)) { $tpl->assign('errors', $this->_errors); }
		if ($this->_messages && is_array($this->_messages)) { $tpl->assign('messages', $this->_messages); }

		// is the website down for maintenance?
		if (cms_siteprefs::get('enablesitedownmessage') == '1') { $tpl->assign('is_sitedown', 'true'); }

		return $tpl->fetch();
	}

	public function get_my_alerts() {
		return Alert::load_my_alerts();
	}
}
