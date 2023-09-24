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
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------

use CMSMS\AdminAlerts\Alert;

class OneElevenTheme extends CmsAdminThemeBase {
	private $_errors = array();
	private $_messages = array();
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
		if ($get_var != '' && isset($_GET[$get_var]) && !empty($_GET[$get_var])) {
			if (is_array($_GET[$get_var])) {
				foreach ($_GET[$get_var] as $one) {
					$this->_errors[] = lang(cleanValue($one));
				}
			} else {
				$this->_errors[] = lang(cleanValue($_GET[$get_var]));
			}
		} else if (is_array($errors)) {
			foreach ($errors as $one) {
				$this->_errors[] = $one;
			}
		} else if (is_string($errors)) {
			$this->_errors[] = $errors;
		}
		return '<!-- OneEleven::ShowErrors() called -->';
	}

	public function ShowMessage($message, $get_var = '') {
		// cache message for use in the template.
		if ($get_var != '' && isset($_GET[$get_var]) && !empty($_GET[$get_var])) {
			if (is_array($_GET[$get_var])) {
				foreach ($_GET[$get_var] as $one) {
					$this->_messages[] = lang(cleanValue($one));
				}
			} else {
				$this->_messages[] = lang(cleanValue($_GET[$get_var]));
			}
		} else if (is_array($message)) {
			foreach ($message as $one) {
				$this->_messages[] = $one;
			}
		} else if (is_string($message)) {
			$this->_messages[] = $message;
		}
	}

	public function ShowHeader($title_name, $extra_lang_params = array(), $link_text = '', $module_help_type = FALSE) {
		if ($title_name) $this->set_value('pagetitle', $title_name);
		if ($extra_lang_params && is_array($extra_lang_params)) $this->set_value('extra_lang_params', $extra_lang_params);

		$module = '';
		if (isset($_REQUEST['module'])) {
			$module = $_REQUEST['module'];
		} else if (isset($_REQUEST['mact'])) {
			$tmp = explode(',', $_REQUEST['mact']);
			$module = $tmp[0];
		}

		// get the image url.
		if ($module) {
			$ext = 'png';
			$path = cms_join_path(CMS_ROOT_PATH, 'modules', $module, 'images', 'icon.png');
			if (!file_exists($path)) {
				$ext = 'gif';
				$path = substr($path, 0, -3) . 'gif';
			}
			if (file_exists($path)) {
				$config = cms_config::get_instance();
				$url = $config->smart_root_url() . "/modules/{$module}/images/icon.{$ext}";
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
			}// for loop.
		}
	}

	public function do_header() {
	}

	public function do_footer() {
	}

	public function do_toppage($section_name) {
		$config = cms_config::get_instance();
		$smarty = Smarty_CMS::get_instance();
		$otd = $smarty->template_dir;
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
		if ($section_name) {
			$smarty->assign('section_name', $section_name);
			$smarty->assign('pagetitle', lang($section_name));
			$nodes = $this->get_navigation_tree($section_name, -1, FALSE);
		} else {
			$nodes = $this->get_navigation_tree(-1, 2, FALSE);
		}
		foreach ($nodes as &$one) {
			$nm = $one['name'];
			$ext = 'png';
			$path = cms_join_path(CMS_ROOT_PATH, 'modules', $nm, 'images', 'icon.png');
			if (!file_exists($path)) {
				$ext = 'gif';
				$path = substr($path, 0, -3) . 'gif';
			}
			if (file_exists($path)) {
				$one['img'] = $config->smart_root_url() . "/modules/{$nm}/images/icon.{$ext}";
			} else if (isset($this->_topaliases[$nm])) {
				$one['img'] = "themes/{$this->themeName}/images/icons/topfiles/{$this->_topaliases[$nm]}";
			}
		}
		unset($one);
		$smarty->assign('nodes', $nodes);
		$smarty->assign('config', $config);
		$smarty->assign('theme', $this);

		// is the website set down for maintenance?
		if (get_site_preference('enablesitedownmessage') == '1') { $smarty->assign('is_sitedown', 'true'); }

		$_contents = $smarty->fetch('topcontent.tpl');
		$smarty->template_dir = $otd;
		echo $_contents;
	}


	public function do_login($params) {
//		$config = cms_config::get_instance();
		$smarty = Smarty_CMS::get_instance();
		$otd = $smarty->template_dir;
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
		global $error,$warningLogin,$acceptLogin,$changepwhash;
		$path = __DIR__ . DIRECTORY_SEPARATOR . 'login.php';
		include_once $path;
//		$tmp = get_site_preference('frontendlang');
		$tmp = CmsNlsOperations::get_frontend_language();
		if (!$tmp) { $tmp = 'en'; } // default global english
		$lang = CmsNlsOperations::get_lang_attribute($tmp);
		$smarty->assign('lang', $lang);
		$smarty->display('login.tpl');
		$smarty->template_dir = $otd;
	}

	public function postprocess($html) {
		$smarty = Smarty_CMS::get_instance();
		$otd = $smarty->template_dir;
		$smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
		$module_help_type = $this->get_value('module_help_type');

		// get a page title
		$title = $this->get_value('pagetitle');
		if ($title) {
			if (!$module_help_type) {
				// if not doing module help, translate the string.
				$extra = $this->get_value('extra_lang_params');
				if (!$extra)
					$extra = array();
				$title = lang($title, $extra);
			}
		} else {
			if ($this->title) {
				$title = $this->title;
			}
			else {
				// no title, get one from the breadcrumbs.
				$bc = $this->get_breadcrumbs();
				if (is_array($bc) && count($bc)) {
					$title = $bc[count($bc) - 1]['title'];
				}
			}
		}
		// page title and alias
		$smarty->assign('pagetitle', $title);
		$smarty->assign('subtitle', $this->subtitle);
		$alias = $this->get_value('pagetitle');
		$smarty->assign('pagealias', ($alias ? munge_string_to_url($alias) : ''));

		// module name?
		if (($module_name = $this->get_value('module_name'))) {
			$smarty->assign('module_name', $module_name);
		}

		// module icon?
		if (($module_icon_url = $this->get_value('module_icon_url'))) {
			$smarty->assign('module_icon_url', $module_icon_url);
		}

		$userid = get_userid();
		// module_help_url
		if (!cms_userprefs::get_for_user($userid,'hide_help_links',0)) {
			if (($module_help_url = $this->get_value('module_help_url'))) {
				$smarty->assign('module_help_url', $module_help_url);
			}
		}

		// user preferences
		if (check_permission($userid,'Manage My Settings')) {
			$smarty->assign('myaccount',1);
		}

		// if bookmarks
		if (cms_userprefs::get_for_user($userid, 'bookmarks') && check_permission($userid,'Manage My Bookmarks')) {
			$marks = $this->get_bookmarks(TRUE);
			$smarty->assign('marks', $marks);
		}
		$smarty->assign('headertext',$this->get_headtext());
		$smarty->assign('footertext',$this->get_footertext());

		// and some other common variables
		$smarty->assign('content', str_replace('</body></html>', '', $html));
		$smarty->assign('config', cms_config::get_instance());
		$smarty->assign('theme', $this);
		$smarty->assign('secureparam', CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY]);
		$userops = UserOperations::get_instance();
		$smarty->assign('user', $userops->LoadUserByID($userid));
		// prefer user selected language
		$tmp = cms_userprefs::get_for_user($userid, 'default_cms_language');
		if (!$tmp) {
			$tmp = CmsNlsOperations::get_current_language();
		}
		if ($tmp) {
			$lang = CmsNlsOperations::get_lang_attribute($tmp);
		}
		else {
			$lang = '';
		}
		$smarty->assign('lang', $lang);
		// get language direction
		$lang = CmsNlsOperations::get_current_language();
		$info = CmsNlsOperations::get_language_info($lang);
		$smarty->assign('lang_dir',$info->direction());

		if (is_array($this->_errors) && count($this->_errors))
			$smarty->assign('errors', $this->_errors);
		if (is_array($this->_messages) && count($this->_messages))
			$smarty->assign('messages', $this->_messages);

		// is the website set down for maintenance?
		if (get_site_preference('enablesitedownmessage') == '1') { $smarty->assign('is_sitedown', 'true'); }

		$_contents = $smarty->fetch('pagetemplate.tpl');
		$smarty->template_dir = $otd;
		return $_contents;
	}

	public function get_my_alerts() {
		return Alert::load_my_alerts();
	}
}
?>
