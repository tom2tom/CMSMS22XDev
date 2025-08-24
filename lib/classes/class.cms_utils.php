<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: cms_utils
# (c) 2010 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
#END_LICENSE

/**
 * A convenience class for CMS Made Simple.
 *
 * The methods in this class provide simple wrappers over other class methods.
 *
 * @package CMS
 * @license GPL
 */

/**
 * A class of static methods providing various convenience utilities.
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell
 * @since 1.9
 */
final class cms_utils
{
	/**
	 * @ignore
	 */
	private static $_vars;

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * Get data that was stored elsewhere in the application.
	 *
	 * @since 1.9
	 * @param string $key The key to get.
	 * @return mixed The stored data, or null
	 */
	public static function get_app_data($key)
	{
		if( is_array( self::$_vars ) && isset(self::$_vars[$key]) ) return self::$_vars[$key];
		return null;
	}

	/**
	 * Set data for later use.
	 *
	 * This method is typically used to store data for later use by another part of the application.
	 * This data is not stored in the session, so it only exists for one request.
	 *
	 * @since 1.9
	 * @param string $key The name of this data.
	 * @param mixed  $value The data to store.
	 */
	public static function set_app_data($key,$value)
	{
		if( $key == '' ) return;
		if( !is_array(self::$_vars) ) self::$_vars = array();
		self::$_vars[$key] = $value;
	}

	/**
	 * A convenience function to return the object representing an installed module.
	 *
	 * If a version string is passed, a matching object will only be returned IF
	 * the installed version is greater than or equal to the supplied version.
	 *
	 * @see version_compare()
	 * @see ModuleOperations::get_module_instance
	 * @since 1.9
	 * @param string $name The module name
	 * @param string $version An optional version string
	 * @return CmsModule The matching module object or null
	 */
	public static function get_module($name,$version = '')
	{
		return ModuleOperations::get_instance()->get_module_instance($name,$version);
	}

	/**
	 * A convenience function to return an indication if a module is available.
	 *
	 * @see also get_module()
	 * @final
	 * @author Robert Campbell
	 * @since 1.11
	 * @param string $name The module name
	 * @return bool
	 */
	final public static function module_available($name)
	{
		return ModuleOperations::get_instance()->IsModuleActive($name);
	}

	/**
	 * A convenience function to return the current database-connection.
	 *
	 * @link http://phplens.com/lens/adodb/docs-adodb.htm
	 * @since 1.9
	 * @return \CMSMS\Database\Connection
	 * @throws \Exception
	 */
	final public static function get_db()
	{
		return \CmsApp::get_instance()->GetDb();
	}

	/**
	 * A convenience function to return the global CMSMS config.
	 *
	 * @see also CmsApp::GetDb()
	 * @since 1.9
	 * @final
	 * @return cms_config The global configuration object.
	 */
	final public static function get_config()
	{
		return \cms_config::get_instance();
	}

	/**
	 * A convenience function to return the CMSMS Smarty object.
	 *
	 * @see also CmsApp::GetSmarty()
	 * @since 1.9
	 * @final
	 * @return Smarty_CMS Handle to the Smarty object
	 */
	final public static function get_smarty()
	{
		return \Smarty_CMS::get_instance();
	}

	/**
	 * A convenience function to return the current content object.
	 *
	 * This function will always return NULL if called from an admin action
	 *
	 * @since 1.9
	 * @final
	 * @return Content The current content object, or null
	 */
	final public static function get_current_content()
	{
		return CmsApp::get_instance()->get_content_object();
	}

	/**
	 * A convenience function to return the alias of the current page.
	 *
	 * This function will return an empty string if called from an admin action.
	 *
	 * @since 1.9
	 * @final
	 * @return string
	 */
	final public static function get_current_alias()
	{
		$obj = CmsApp::get_instance()->get_content_object();
		if( $obj ) return $obj->Alias();
		return '';
	}

	/**
	 * A convenience function to return the page id of the current page
	 *
	 * This function will return 0 if called from an admin action
	 *
	 * @since 1.9
	 * @final
	 * @return int
	 */
	final public static function get_current_pageid()
	{
		return CmsApp::get_instance()->get_content_id();
	}

	/**
	 * Get the currently selected WYSIWYG module.
	 *
	 * This method will return the currently selected frontend wysiwyg for frontend requests (or null if none is selected)
	 * For admin requests this method will return the user's currently selected wysiwyg module, or null.
	 *
	 * @since 1.10
	 * @param string $module_name The module name.
	 * @return CMSModule
	 */
	public static function get_wysiwyg_module($module_name = '')
	{
		return ModuleOperations::get_instance()->GetWYSIWYGModule($module_name);
	}

	/**
	 * Get the currently selected syntax highlighter module.
	 *
	 * @since 1.10
	 * @author Robert Campbell
	 * @return CMSModule
	 */
	public static function get_syntax_highlighter_module()
	{
		return ModuleOperations::get_instance()->GetSyntaxHighlighter();
	}

	/**
	 * Get the currently selected search module.
	 *
	 * @since 1.10
	 * @author Robert Campbell
	 * @return CMSModule
	 */
	public static function get_search_module()
	{
		return ModuleOperations::get_instance()->GetSearchModule();
	}

	/**
	 * Get the currently selected filepicker module.
	 *
	 * @since 2.2
	 * @author Robert Campbell
	 * @return CMSModule
	 */
	public static function get_filepicker_module()
	{
		return ModuleOperations::get_instance()->GetFilePickerModule();
	}

	/**
	 * Attempt to retrieve the IP address of the connected user.
	 * This function attempts to compensate for proxy servers.
	 *
	 * @author Robert Campbell
	 * @since 1.10
	 * @return string IP address in dotted notation, or empty
	 */
	public static function get_real_ip()
	{
		$ip = '';
		if( !empty($_SERVER['REMOTE_ADDR']) ) $ip = $_SERVER['REMOTE_ADDR'];
		elseif( empty($ip) && !empty($_SERVER['HTTP_CLIENT_IP']) ) $ip = $_SERVER['HTTP_CLIENT_IP'];
		elseif( empty($ip) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

		if( filter_var($ip,FILTER_VALIDATE_IP) ) return $ip;
		return '';
	}

	/**
	 * Get the current admintheme object
	 *
	 * @author Robert Campbell
	 * @since 1.11
	 * @return CmsAdminThemeBase derived object, or null
	 */
	public static function get_theme_object()
	{
		return CmsAdminThemeBase::GetThemeObject();
	}

	/**
	 * Convert the supplied string into something appropriate for an url-path
	 * for page or News item etc, or a page-alias (with $withslash = false).
	 * Mimics PHP's FILTER_SANITIZE_URL, but also allows relevant valid UTF-8
	 * and relevant extended-ASCII chars. Some chars ('-','_') are repetition-limited.
	 * @since 2.2.22F2
	 *
	 * @param string $str
	 * @param bool $withslash Whether slashes are allowed in $str. Default true.
	 * @return string
	 */
	public static function cleanUrlPath($str,$withslash = true)
	{
		// see also: bookmarks' url sanitisation
		if( !$str ) { return (string)$str; }
		$val = preg_replace([
		 '/[^\x20-\x7e\pL\p{Nd}\p{Po}\x82-\x84\x88\x8a\x8c\x8e\x91-\x94\x96-\x98\x9a\x9c\x9e\x9f\xa8\xad\xb4\xb7\xb8\xc0-\xf6\xf8-\xff]/u',
		 '/\s+/',
		 '/_{3,}/',
		 '/\-{3,}/',
		 '/\\+/',
		 '~/{2,}~'
		 ],[
		 '',
		 ' ',
		 '__',
		 '--',
		 '/',
		 '/'
		 ], (string)$str);
		// escape 'non-verbatim' url-chars (preserving '%' and '/')
		$val = preg_replace_callback('/[^\w\-.~%\/\x80-\xff]/', function($matches) {
			return rawurlencode($matches[0]);
		}, $val);
		// don't double-escape '%'
		$val = preg_replace_callback('/%(?![0-9a-fA-F]{2})/', function($matches) {
			return rawurlencode($matches[0]);
		}, $val);
		if ($withslash) {
			return ltrim($val, '/'); //trailing '/' acceptable
		}
		return strtr($val, ['/' => '']);
	}

	/**
	 * Report whether the specified url is acceptable
	 * @since 2.2.22F2
	 *
	 * @param string $url trim()'d, absolute or '[ROOT_URL]'-prefixed
	 *  or (risky!) '//'-prefixed or site-root-relative or data
	 * @param string $type Optional item-type known to FileTypeHelper
	 *  e.g. 'image' Used only if $url refers to this site.
	 * @return array 2 members:
	 *  [0] = bool indicating acceptability
	 *  [1] = error message or empty
	 */
	public static function validate_url($url,$type = '')
	{
		if ($url) {
			if (startswith($url,CMS_ROOT_URL)) {
				$local = true;
			} elseif ($url[0] == '/' && $url[1] != '/') {
				$url = CMS_ROOT_URL . $url;
				$local = true;
			} elseif (startswith($url,'[ROOT_URL]')) {
				$url = str_replace('[ROOT_URL]',CMS_ROOT_URL,$url);
				$local = true;
			} else {
				$local = false;
			}

			// parsing a data url like "data:" [ mediatype ] [ ";base64" ] "," data
			// finds just $scheme='data' and 'path'=everything after ':' and $host=null
			// parsing a protocol-relative url like '//....'
			// finds no 'scheme' property and explicit $scheme=null

			$host = parse_url($url,PHP_URL_HOST);
			if (startswith($url,'//')) {
				$prel = true; // protocol-relative flag
				$local = ($host == parse_url(CMS_ROOT_URL,PHP_URL_HOST));
			} else {
				$prel = false;
			}

			$scheme = parse_url($url,PHP_URL_SCHEME);
			if ($scheme) {
				if (!in_array(strtolower($scheme),['https','http'])) { // maybe also 'data' for image only ? maybe ws[s] ? but prob'ly no websocket interaction for CMSMS
					// typo checks
					$pc = 0.0;
					foreach (['https','http'] as $s) {
						similar_text($scheme,$s,$pc);
						if ($pc > 60 && $pc < 100) {
							$url = str_replace($scheme,$s,$url); // correct scheme
							break;
						}
						return [false,'The URL scheme is not appropriate'];//TODO langify 'urlschemenotvalid'
					}
				}
			} elseif (!$prel) {
				return [false,'The URL has no scheme'];//TODO langify 'urlschemenone'
			}
			if ($host) {
				//validate or return false ....
/*
				// blacklisted hosts? e.g. a site-preference
				if ($blockhosts && in_array(strtolower($parts['host']),array_map('strtolower',$blockhosts))) { // caseless check
					return [false,'The URL host is prohibited'];//TODO langify
				}
				//TODO other sanity checks, malevolence checks
				//e.g. refer to https://owasp.org/www-community/attacks/Forced_browsing
				//www.example.com/function.jsp?fwd=admin.jsp
				//www.example.com/example.php?url=http://malicious.example.com
*/
			} elseif ($scheme != 'data') { //for a data url, $host = null
				return [false,'The URL has no host']; //TODO langify 'urlhostnotvalid'
			}
			// deal with
			// malicious payload as part of the URL, executed immediately when the URL is accessed c.f. sanitize funcs e.g. news_ops::execSpecialize(), UserGuideUtils::cleanContent()
			// malicious payload in data, executed when the data is retrieved and rendered on the page
			if ($scheme != 'data') {
				$p = strpos($url,$host) + strlen($host);
				$u2 = substr($url,$p);
				// check for tag(s) TODO check for other bad payloads
				if (preg_match('/<[^>]*>/',$u2)) {
					return [false,lang('illegalcharacters',lang('url'))];
				}
				// 2-stage escape (to prevent double-escaping '%')
				$u3 = preg_replace_callback('~[^\w:/?#\]\'[@!$&()*+;=%]~',function($matches) {
					return rawurlencode($matches[0]);
				},$u2);
				$u4 = preg_replace_callback('/%(?![0-9a-fA-F]{2})/',function($matches) {
					return rawurlencode($matches[0]);
				},$u3);
				$url2 = substr($url,0,$p).$u4;
				if (!filter_var($url2,FILTER_VALIDATE_URL)) { // no 'extended-chars' support cuz local url maps to filesystem
					return [false,lang('illegalcharacters',lang('url'))];
				} elseif ($local) {
					$parts = parse_url($url2);
					if (!$prel) {
						if (!$parts || count($parts) > 3 || $parts != array_intersect_key($parts,['scheme'=>1,'host'=>1,'path'=>1])) { // we've already detected scheme and host, also want path but nothing else
							return [false,'The URL is not acceptable in this context']; //TODO langify 'urlnotvalid'
						}
					} elseif (!$parts || count($parts) > 2 || $parts != array_intersect_key($parts,['host'=>1,'path'=>1])) { // we've already detected host, also want path but nothing else
						return [false,'The URL is not acceptable in this context']; //TODO langify 'urlnotvalid'
					}
					if ($type) {
						// validate file extension, using part of original url
						$p = strrpos($u2,'/');
						$fn = substr($u2,$p+1);
						$p = ($fn) ? strrpos($fn,'.') : -1;
						$ext = ($p > 0) ? substr($fn,$p+1): '';
						if (!$ext) {
							return [false,lang('typenotvalid')];
						} else {
							$helper = new CMSMS\FileTypeHelper();
							$imgexts = $helper->get_file_type_extensions($type);
								if (!$imgexts || !in_array(strtolower($ext),$imgexts)) {
								return [false,lang('typenotvalid')];
							}
						}
					}
					// confirm access
					if (!$prel) {
						$fp = str_replace([CMS_ROOT_URL,'/'],[CMS_ROOT_PATH,DIRECTORY_SEPARATOR],$url);
					} else {
						preg_match("/^.*?{$host}(.*)$/",CMS_ROOT_URL,$matches);
						$fp = str_replace(["//{$host}{$matches[1]}",'/'],[CMS_ROOT_PATH,DIRECTORY_SEPARATOR],$url);
					}
					if (!is_readable($fp)) {
						return [false,lang('CMSEX_F001')];
					}
				} else {
					// offsite-url check
					$req = new cms_http_request(['timeout' => 3]);
					$res1 = $req->execute($url2,CMS_ROOT_URL,'HEAD');// for a GET, add nocache header
					$res2 = $req->getStatus();
					if ($res1 != 'OK') {
						return [false,'The URL is not accessible']; //TODO langify
					}
				}
				//TODO data-payload content check ?
			} elseif (!preg_match(
				'/^data:([a-z]+\/[a-z0-9-+.]+(;[a-z0-9-.!#$%*+.{}|~`]+=[a-z0-9-.!#$%*+.{}()_|~`]+)*)?(;base64)?,([a-z0-9!$&\',()*+;=\-._~:@\/?%\s<>]*?)$/i',
				$url)) {
				// data url validation regex adapted from github.com/killmenot/valid-data-url/blob/master/index.js
				return [false,lang('illegalcharacters',lang('url'))];
			}
			return [true,''];
		} // url

		return [false,lang('informationmissing').': image url'];
	}
} // end of class

?>
