<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: cms_admin_utils
# (c) 2010 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program. If not, read the license online at
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
#END_LICENSE

if( !CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE) )
	throw new CmsLogicException('Attempt to use cms_admin_utils class from an invalid request');

/**
 * A class of static utility-methods for admin requests.
 * @since 2.0
 * @final
 * @see also CmsAdminUtils class having more methods and less usage
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell
 */
final class cms_admin_utils
{
	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * Get the complete URL to an admin icon
	 *
	 * @param string $icon the basename of the desired icon
	 * @return string
	 */
	public static function get_icon($icon)
	{
		$theme = cms_utils::get_theme_object();
		if( !is_object($theme) ) return '';

		$smarty = \Smarty_CMS::get_instance();
		$module = $smarty->getTemplateVars('actionmodule');

		$dirs = array();
		if( $module ) {
			$obj = cms_utils::get_module($module);
			if( is_object($obj) ) {
				$img = basename($icon);
				$dirs[] = array(cms_join_path($obj->GetModulePath(),'icons',"{$img}"),$obj->GetModuleURLPath()."/icons/{$img}");
				$dirs[] = array(cms_join_path($obj->GetModulePath(),'images',"{$img}"),$obj->GetModuleURLPath()."/images/{$img}");
			}
		}
		if( basename($icon) == $icon ) $icon = "icons/system/{$icon}";
		$config = cms_config::get_instance();
		$dirs[] = array(cms_join_path($config['admin_path'],"themes/{$theme->themeName}/images/{$icon}"),
						$config['admin_url']."/themes/{$theme->themeName}/images/{$icon}");

		$fnd = '';
		foreach( $dirs as $one ) {
			if( file_exists($one[0]) ) {
				$fnd = $one[1];
				break;
			}
		}
		return $fnd;
	}

	/**
	 * Get a tag for displaying a popup dialog.
	 *
	 * This method accepts variable arguments.
	 * If only one string-argument is passed that is assumed to be the lang key
	 * for the help tag and the lang realm is assumed to be the current module name.
	 * If two or more string-arguments are passed the first of them is assumed
	 * to be the lang realm, the second to be the lang key and the third, if any,
	 * to be the title value.
	 * If more than three arguments are provided, args 4+ are assumed to be
	 * dialog options/properties. See https://api.jqueryui.com/dialog/#quick-nav
	 * If one array-argument is passed that is assumed to provide all pertinent
	 * parameters
	 *
	 * @param varargs $args [$key2] | [$key1,$key2, ....]| assoc. array
	 *
	 * @return string HTML content of the help tag or empty
	 */
	public static function get_help_tag(...$args)
	{
		if( !CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE) ) return '';

		$theme = cms_utils::get_theme_object();
		if( !is_object($theme) ) return '';

		$params = [];
		$extras = [];
		if( count($args) >= 2 ) {
			if( is_string($args[0]) && is_string($args[1]) ) {
				$params['key1'] = $args[0];
				$params['key2'] = $args[1];
				if( !empty($args[2]) ) $params['title'] = $args[2];
				$extras = array_diff_key($args,['key'=>1,'key1'=>1,'key2'=>1,'realm'=>1,'title'=>1,'titlekey'=>1]);
			}
		}
		elseif( count($args) == 1 ) {
			if( !is_array($args[0]) ) {
				$params['key2'] = (string)$args[0];
			}
			else {
				$params = $args[0];
				$extras = array_diff_key($params,['key'=>1,'key1'=>1,'key2'=>1,'realm'=>1,'title'=>1,'titlekey'=>1]);
			}
		}
		else {
			return '';
		}

		$key1 = '';
		$key2 = '';
		$title = '';
		foreach( $params as $key => $value ) {
			switch( $key ) {
			case 'key1':
			case 'realm':
				$key1 = trim($value);
				break;
			case 'key':
			case 'key2':
				$key2 = trim($value);
				break;
			case 'title':
			case 'titlekey':
				$title = trim($value);
				break;
			}
		}

		if( !$key1 ) {
			$smarty = \Smarty_CMS::get_instance();
			$module = $smarty->getTemplateVars('actionmodule');
			if( $module ) {
				$key1 = $module;
			}
			else {
				$key1 = 'help';
			}
		}

		if( !$key1 ) return '';
		if( !($key1 == 'none' || $key1 == 'direct') ) {
			$key = $key1;
			if( $key2 !== '' ) $key .= '__'.$key2;
		}
		elseif( $key2 !== '') {
			$key = $key2;
			$title = '';
		}
		else {
			return '';
		}
		if( $title === '' ) $title = $key2;
		$title = preg_replace('/\s*\(.*\)\s*$/','',strip_tags($title)); // omit any trailing detail
		$icon = self::get_icon('info.gif');
		if( !$icon ) return '';

		$custom = '';
		if( $extras ) {
			foreach( $extras as $xk => $xv ) {
				//workaround jQ lowercase entire keys in .data object, except after '-'
				$xxk = preg_replace_callback('/[A-Z]/', function($matches) {
					return '-'.strtolower($matches[0]);
				} ,trim($xk,' "\''));
				if( is_numeric($xv) ) {
					$xxv = 0 + $xv;
				}
				elseif( is_bool($xv) ) {
					$xxv = (string)$xv;
				}
				else {
					$xxv = trim((string)$xv,' "\'');
				}
				$custom .= " data-dialog$xxk=\"$xxv\"";
			}
		}

		return '<span class="cms_help" data-cmshelp-key="'.$key.'" data-cmshelp-title="'.$title.'"'.$custom.'><img class="cms_helpicon" src="'.$icon.'" alt="'.$title.'"></span>';
	}
} // class
