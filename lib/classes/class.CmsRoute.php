<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: CmsRoute
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
 * This file manages the route class.
 * @package CMS
 * @license GPL
 */

/**
 * Simple global convenience object to hold information for a single route.
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell
 * @since  1.9
 * @property string $term
 * @property string $key1
 * @property string $key2
 * @property string $key3
 * @property array  $defaults
 * @property string absolute
 * @property string results
 */
class CmsRoute implements ArrayAccess
{
	/**
	 * @ignore
	 */
	private $_data;

	/**
	 * @ignore
	 * Dynamic, not stored in $data despite the key there
	 */
	private $_results;

	/**
	 * @ignore
	 */
	static private $_keys = array('term','key1','key2','key3','defaults','absolute','results');

	/**
	 * Constructor.
	 *
	 * @param string $term The route string (or regular expression)
	 * @param string $key1 Optional first key. Usually a module name or numeric page-id.
	 * @param array  $defaults Optional parameter(s) for the module which processes the route. Only applicable when the destination is a module.
	 * @param bool   $is_absolute Optional flag indicating whether $term is a regular expression or an absolute string. Default FALSE.
	 * @param string $key2 Optional second key.
	 * @param string $key3 Optional third key.
	 */
	public function __construct($term,$key1 = '',$defaults = [],$is_absolute = FALSE,$key2 = '',$key3 = '')
	{
		$this->_data['term'] = $term;
		$this->_data['absolute'] = $is_absolute;

		if( is_numeric($key1) && !$key2 ) {
			$this->_data['key1'] = '__CONTENT__';
			$this->_data['key2'] = (int)$key1;
		}
		else {
			$this->_data['key1'] = $key1;
			$this->_data['key2'] = $key2;
		}
		if( $defaults && is_array($defaults) ) $this->_data['defaults'] = $defaults;
		if( $key3 ) $this->_data['key3'] = $key3;
	}

	/**
	 * Static convenience function to create a new CmsRoute object.
	 *
	 * @param string $term The route string (or regular expression)
	 * @param string $key1 The first key. Usually a module name or numeric page-id
	 * @param string $key2 Optional second key
	 * @param array  $defaults Optional parameter(s) for the module which processes the route. Only applicable when the destination is a module.
	 * @param bool   $is_absolute Optional flag indicating whether $term is a regular expression or an absolute string. Default FALSE.
	 * @param string $key3 Optional third key
	 */
	public static function new_builder($term,$key1,$key2 = '',$defaults = [],$is_absolute = FALSE,$key3 = '')
	{
		return new CmsRoute($term,$key1,$defaults,$is_absolute,$key2,$key3);
	}

	/**
	 * Return the signature of this CmsRoute
	 */
	public function signature()
	{
		$tmp = serialize($this->_data);
		$tmp = md5($tmp);
		return $tmp;
	}

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($key)
	{
		if( in_array($key,self::$_keys) && isset($this->_data[$key]) ) return $this->_data[$key];
		return null; // no value for unrecognised key
	}

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($key,$value)
	{
		if( in_array($key,self::$_keys) ) $this->_data[$key] = $value;
	}

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists($key)
	{
		if( in_array($key,self::$_keys) && isset($this->_data[$key]) ) return TRUE;
		return FALSE;
	}

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($key)
	{
		if( in_array($key,self::$_keys) && isset($this->_data[$key]) ) unset($this->_data[$key]);
	}

	/**
	 * Returns the route term (string or regex)
	 *
	 * @deprecated
	 * @return string
	 */
	public function get_term()
	{
		return $this->_term;
	}

	/**
	 * Retrieve the destination module name (if any).
	 *
	 * @deprecated
	 * @return string Destination module name, or empty string.
	 */
	public function get_dest()
	{
		if( isset($this->_data['key1']) && !$this->is_content() ) return $this->_data['key1'];
		return '';
	}

	/**
	 * Retrieve the page id, if the destination is a content page.
	 *
	 * @deprecated
	 * @return int Page id, or 0.
	 */
	public function get_content()
	{
		if( $this->is_content() ) return $this->_data['key2'];
		return 0;
	}

	/**
	 * Retrieve the default parameters for this route
	 *
	 * @deprecated
	 * @return array The default parameters for the route, or empty if no defaults specified.
	 */
	public function get_defaults()
	{
		if( isset($this->_data['defaults']) ) return $this->_data['defaults'];
		return [];
	}

	/**
	 * Test whether this route is for a page.
	 *
	 * @deprecated
	 * @return bool
	 */
	public function is_content()
	{
		return (isset($this->_data['key1']) && $this->_data['key1'] === '__CONTENT__');
	}

	/**
	 * Get matching parameter results.
	 *
	 * @deprecated
	 * @return array Matching parameters... or empty
	 */
	public function get_results()
	{
		if( isset($this->_results) ) return $this->_results;
		return [];
	}

	/**
	 * Test whether this route matches the specified string
	 * Depending upon the route, either a string comparison or regular
	 * expression match is performed.
	 *
	 * @param string $str The input string
	 * @param bool $exact Perform an exact string match, not a regular expression match.
	 * @return bool
	 */
	public function matches($str,$exact = false)
	{
		if( $exact || !empty($this->_data['absolute']) ) {
			$a = trim((string)$this->_data['term']);
			$a = trim($a,'/');
			$b = trim((string)$str);
			$b = trim($b,'/');

			if ( strcasecmp($a,$b) == 0 ) { //too bad if any non-ASCII in there!
				$this->_results = ['module' => $this->get_dest()] + $this->get_defaults();
				return true;
			}
			else {
				$this->_results = [];
				return false;
			}
		}

		$tmp = [];
		if( preg_match((string)$this->_data['term'],$str,$tmp) ) {
			$this->_results = ['module' => $this->get_dest()] + $this->get_defaults();
			if( $tmp ) { $this->_results += $tmp; }
			return true;
		}
		$this->_results = [];
		return false;
	}

} // end of class

?>
