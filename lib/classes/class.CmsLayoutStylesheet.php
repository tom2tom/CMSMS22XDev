<?php
#CMS Made Simple class CmsLayoutStylesheet
#(c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

use CMSMS\HookManager;

/**
 * A class to represent a stylesheet.
 *
 * This class is capable of managing a single stylesheet, and has static methods for loading stylesheets from the database.
 * Loaded stylesheets are cached in internal memory to ensure that the same stylesheet is not loaded twice for a request.
 *
 * Stylesheets are (optionally) attached to designs (CmsLayoutCollection)
 * see the {cms_stylesheet} plugin for more information.
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell
 * @see also CmsLayoutCollection
 */
class CmsLayoutStylesheet
{
	/**
	 * @ignore
	 */
	const TABLENAME = 'layout_stylesheets';

	/**
	 * @ignore
	 */
	private $_dirty;

	/**
	 * @ignore
	 * @internal should be private but _load_from_data expects public
	 */
	public $_data = [
	'id' => 0,
	'name' => '',
	'content' => '',
	'description' => '',
	'media_type' => [], //TODO can it be a single-type string ?
	'media_query' => '',
	'created' => 0,
	'modified' => 0,
	];

	/**
	 * @ignore
	 * @internal should be private but _load_from_data expects public
	 */
	public $_design_assoc;

	/**
	 * @ignore
	 */
	private static $_name_cache = [];

	/**
	 * @ignore
	 */
	private static $_css_cache = [];

	/**
	 * @ignore
	 */
	private static $_lock_cache;

	/**
	 * @ignore
	 */
	private static $_lock_cache_loaded;

	/**
	 * @ignore
	 */
	public function __clone()
	{
		$this->_data['id'] = 0;
		if( $this->_data['name'] ) $this->_data['name'] .= ' Copy';
		$this->_data['created'] = 0;
		$this->_data['modified'] = 0;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the integer id of this stylesheet
	 *
	 * @return int, possibly 0
	 */
	public function get_id()
	{
		return (int)$this->_data['id'];
	}

	/**
	 * Get the name of this stylesheet
	 *
	 * @return string
	 */
	public function get_name()
	{
		return (string)$this->_data['name'];
	}

	/**
	 * Set the name of this stylesheet
	 * The name cannot be empty, can only include suitable characters
	 * and must be unique in this system
	 *
	 * @throws CmsInvalidDataException
	 * @param string $str
	 */
	public function set_name($str)
	{
		if( !CmsAdminUtils::is_valid_itemname($str)) throw new CmsInvalidDataException("Invalid characters in name: $str");
		$this->_data['name'] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the content of this stylesheet
	 *
	 * @return string maybe empty
	 */
	public function get_content()
	{
		return (string)$this->_data['content'];
	}

	/**
	 * Set the content of this stylesheet
	 *
	 * @throws CmsInvalidDataException
	 * @param string $str
	 */
	public function set_content($str)
	{
		$str = trim((string)$str);
		if( !$str ) throw new CmsInvalidDataException('Template content cannot be empty');
		$this->_data['content'] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the description of this stylesheet
	 *
	 * @return string maybe empty
	 */
	public function get_description()
	{
		return (string)$this->_data['description'];
	}

	/**
	 * Set the description of this stylesheet
	 *
	 * @param string $str
	 */
	public function set_description($str)
	{
		$this->_data['description'] = trim((string)$str);
		$this->_dirty = TRUE;
	}

	/**
	 * Get the media types associated with this stylesheet
	 * Media types are used with the @media css rule
	 *
	 * @deprecated instead use media queries
	 * @return array
	 * @see http://www.w3schools.com/css/css_mediatypes.asp
	 */
	public function get_media_types()
	{
		return (array)$this->_data['media_type'];
	}

	/**
	 * Test if this stylesheet has the specified media type
	 * Media types are used with the @media css rule
	 *
	 * @deprecated instead use media queries
	 * @param string $str The media type name
	 * @return bool
	 */
	public function has_media_type($str)
	{
		$str = trim((string)$str);
		return $str && in_array($str,$this->_data['media_type']);
	}

	/**
	 * Add the specified media type to the list of media types for this stylesheet
	 * Media types are used with the @media css rule
	 *
	 * @deprecated instead use media queries
	 * @param string $str The media type name
	 * @return bool
	 */
	public function add_media_type($str)
	{
		$str = trim((string) $str);
		if( !$str ) return;
		if( !is_array($this->_data['media_type']) ) $this->_data['media_type'] = array();
		$this->_data['media_type'][] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Absolutely set the list of media types for this stylesheet
	 * Media types are used with the @media css rule
	 *
	 * @deprecated instead use media queries
	 * @param mixed $arr Either a string, or an array of strings.
	 */
	public function set_media_types($arr)
	{
		if( !is_array($arr) ) {
			if( !is_numeric($arr) && $arr && is_string($arr) ) {
				$arr = array($arr);
			}
			else {
				return;
			}
		}

		$this->_data['media_type'] = $arr;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the media query associated with this stylesheet
	 *
	 * @see http://en.wikipedia.org/wiki/Media_queries
	 * @return string
	 */
	public function get_media_query()
	{
		return (string)$this->_data['media_query'];
	}

	/**
	 * Set the media query associated with this stylesheet
	 *
	 * @see http://en.wikipedia.org/wiki/Media_queries
	 * @param string $str
	 */
	public function set_media_query($str)
	{
		$this->_data['media_query'] = trim((string)$str);
		$this->_dirty = TRUE;
	}

	/**
	 * Get the public/displayable media property for this stylesheet
	 * @since 2.2.22F2
	 *
	 * @return string
	 */
	public function get_media()
	{
		$type = (string)$this->_data['media_query'];
		if( $type ) { //e.g. @media screen and (min-device-width: 500px) { ...
			$p = strpos($type, '{');
			if( $p !== FALSE ) {
				$tmp = str_replace('@media', '', substr($type, 0, $p));
				$type = trim($tmp);
			}
			else {
				$type = ''; //TODO truncate & trim($type)
			}
		}
		if( !$type ) {
			$mt = $this->_data['media_type'];
			if( $mt ) {
				if( is_array($mt) ) {
					array_walk($mt, function(&$val) { $val = ($val) ? ucfirst($val) : null; });
					$type = implode(',', array_filter($mt));
				}
				else {
					$type = ucfirst(trim($mt));
				}
			}
			else {
				$type = 'All';
			}
		}
		return $type;
	}

	/**
	 * Get the timestamp representing when this stylesheet was first saved to the database
	 *
	 * @return int maybe 0
	 */
	public function get_created()
	{
		return (int)$this->_data['created'];
	}

	/**
	 * Get the timestamp representing when this stylesheet was last saved to the database
	 *
	 * @return int maybe 0
	 */
	public function get_modified()
	{
		return (int)$this->_data['modified'];
	}


	/**
	 * Get the design id's (if any) that this stylesheet is associated with
	 * @see CmsLayoutCollection
	 *
	 * @return array integer design ids, or empty
	 */
	public function get_designs()
	{
		if( !is_array($this->_design_assoc) ) {
			$sid = $this->get_id();
			if( $sid == 0 ) return [];
			$this->_design_assoc = []; // i.e. no further population
			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT design_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ? ORDER BY design_id';
			$dbr = $db->GetCol($query,array($sid));
			if( $dbr ) $this->_design_assoc = $dbr;
		}
		return $this->_design_assoc;
	}

	/**
	 * Set the design id's that this stylesheet is associated with
	 * @see CmsLayoutCollection
	 *
	 * @throws CmsInvalidDataException
	 * @param array $x integer design ids or empty
	 */
	public function set_designs($x)
	{
		if( !is_array($x) ) return;

		foreach( $x as $y ) {
			if( !is_numeric($y) || (int)$y < 1 ) throw new CmsInvalidDataException('Invalid data in design list.  Expect array of integers');
		}

		$this->_design_assoc = $x;
		$this->_dirty = TRUE;
	}

	/**
	 * Add a design association for this stylesheet
	 * @see CmsLayoutCollection
	 *
	 * @throws CmsLogicException
	 * @param mixed $a A CmsLayoutCollection object, or an integer design id, or a string design name
	 */
	public function add_design($a)
	{
		if( is_object($a) && is_a($a,'CmsLayoutCollection') ) {
			$n = $a->get_id();
		}
		elseif( is_numeric($a) && (int)$a > 0 ) {
			$n = $a;
		}
		elseif( (is_string($a) && strlen($a)) ) {
			$design = CmsLayoutCollection::load($a);
			$n = $design->get_id();
		}
		else {
			throw new CmsLogicException('Invalid data passed to '.__METHOD__);
		}

		// note: should load designs before adding.
		$designs = $this->get_designs();
		if( !in_array($n,$designs) ) {
			$this->_design_assoc[] = (int) $n;
			$this->_dirty = TRUE;
		}
	}

	/**
	 * Remove a design from the association list.
	 * @see CmsLayoutCollection
	 *
	 * @throws CmsLogicException
	 * @param mixed $a A CmsLayoutCollection object, or an integer design id, or a string design name
	 */
	public function remove_design($a)
	{
		// note: should load designs here before removing.
		if( !is_array($this->_design_assoc) || count($this->_design_assoc) == 0 ) return;

		if( is_object($a) && is_a($a,'CmsLayoutCollection') ) {
			$n = $a->get_id();
		}
		elseif( is_numeric($a) && (int)$a > 0 ) {
			$n = $a;
		}
		elseif( (is_string($a) && strlen($a)) ) {
			$design = CmsLayoutCollection::load($a);
			$n = $design->get_id();
		}
		else {
			throw new CmsLogicException('Invalid data passed to '.__METHOD__);
		}

		$designs = $this->get_designs();
		if( in_array($n,$designs) ) {
			$t = array();
			foreach( $designs as $one ) {
				if( $n == $one ) continue;
				$t[] = $one;
			}
			$this->_design_assoc = $t;
			$this->_dirty = TRUE;
		}
	}

	/**
	 * Get the content of this stylesheet after processing it via Smarty
	 * @since 2.2.22F2
	 *
	 * @return string
	 */
	public function process()
	{
		$sid = $this->get_id();
		if( $sid > 0 ) {
			$smarty = Smarty_CMS::get_instance();
			return $smarty->fetch('cms_stylesheet:id='.$sid);
		}
		return '';
	}

	/**
	 * Get the content of a named stylesheet after processing it via Smarty
	 * @since 2.2.22F2
	 *
	 * @param string $name
	 * @return string
	 */
	public static function process_by_name($name)
	{
		if( $name ) {
			return \Smarty_CMS::get_instance()->fetch('cms_stylesheet:name='.$name);
		}
		$sid = $this->get_id();
		if( $sid > 0 ) {
			return \Smarty_CMS::get_instance()->fetch('cms_stylesheet:id='.$sid);
		}
		return '';
	}

	/**
	 * Validate this stylesheet for suitability for saving to the database
	 * Stylesheet objects must have a valid name (only certain characters accepted, and must have at least some css content)
	 *
	 * @throws CmsInvalidDataException
	 */
	protected function validate()
	{
		if( !$this->get_name() ) throw new CmsInvalidDataException('Each stylesheet must have a name');
		if( !$this->get_content() ) throw new CmsInvalidDataException('Each stylesheet must have some content');
		if( endswith($this->get_name(),'.css') ) throw new CmsInvalidDataException('Invalid name for a database stylesheet');
		if( !CmsAdminUtils::is_valid_itemname($this->get_name()) ) {
			throw new CmsInvalidDataException('There are invalid characters in the stylesheet name.');
		}

		$db = CmsApp::get_instance()->GetDb();
		$sid = $this->get_id();
		if( $sid > 0 ) {
			// double check the name.
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
			$dbr = $db->GetOne($query,array($this->get_name(),$sid));
		}
		else {
			// double check the name.
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$dbr = $db->GetOne($query,array($this->get_name()));
		}
		if( (int)$dbr > 0 ) {
			throw new CmsInvalidDataException('Stylesheet with the same name already exists.');
		}
	}

	/**
	 * @ignore
	 */
	protected function _update()
	{
		if( !$this->_dirty ) return;
		$this->validate();
		$sid = $this->get_id();

		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.
			' SET name = ?, content = ?, description = ?, media_type = ?, media_query = ?, modified = ?
WHERE id = ?';
		$tmp = '';
		if( $this->_data['media_type'] ) $tmp = implode(',',$this->_data['media_type']);
		$db = CmsApp::get_instance()->GetDb();
		$db->Execute($query,array($this->get_name(),$this->get_content(),$this->get_description(),
								 $tmp,$this->get_media_query(),time(),$sid)); //return value unreliable after UPDATE
		if( $db->ErrorNo() != 0 ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

		// get the designs that have this stylesheet from the database again.
		$query = 'SELECT design_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WhERE css_id = ?';
		$design_list = $db->GetCol($query,array($sid));
		if( !is_array($design_list) ) $design_list = array();

		// cross reference design_list with $dl ... find designs in this object that aren't already known.
		$dl = $this->get_designs();
		$new_dl = array();
		$del_dl = array();
		foreach( $dl as $one ) {
			if( !in_array($one,$design_list) ) $new_dl[] = $one;
		}
		foreach( $design_list as $one ) {
			if( !in_array($one,$dl) ) $del_dl[] = $one;
		}

		if( $del_dl ) {
			// delete deleted items
			$query1 = 'SELECT item_order FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ? AND design_id = ?';
			$query2 = 'UPDATE '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' SET item_order = item_order - 1 WHERE design_id = ? AND item_order > ?';
			$query3 = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE design_id = ? AND css_id = ?';
			foreach( $del_dl as $design_id ) {
				$design_id = (int)$design_id;
				$item_order = (int)$db->GetOne($query1,array($sid,$design_id));
				$dbr = $db->Execute($query2,array($design_id,$item_order)); //TODO unreliable return value after UPDATE call Affected_Rows() and/or ErrorNo()
				if( !$dbr ) { throw new CmsSQLErrorException($db->sql.' '.$db->ErrorMsg()); }
				$dbr = $db->Execute($query3,array($design_id,$sid));
				if( !$dbr ) die($db->sql.' '.$db->ErrorMsg());
			}
		}

		if( $new_dl ) {
			// add new items
			$query1 = 'SELECT MAX(item_order) FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE design_id = ?';
			$query2 = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE." (css_id,design_id,item_order) VALUES($sid,?,?)";
			foreach( $new_dl as $one ) {
				$one = (int)$one;
				$num = (int)$db->GetOne($query1,array($one))+1;
				$dbr = $db->Execute($query2,array($one,$num));
				if( !$dbr ) { throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg()); }
			}
		}

		CmsTemplateCache::clear_cache();
		audit($sid,'Stylesheet',"Updated: {$this->get_name()}");
		$this->_dirty = FALSE;
	}

	/**
	 * @ignore
	 */
	protected function _insert()
	{
		if( !$this->_dirty ) return;
		$this->validate();

		// insert the record
		$now = time();
		$tmp = '';
		if( $this->_data['media_type'] ) $tmp = implode(',',$this->_data['media_type']);
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.
			' (name,content,description,media_type,media_query,created,modified)
VALUES (?,?,?,?,?,?,?)';
		$db = CmsApp::get_instance()->GetDb();
		$dbr = $db->Execute($query, array($this->get_name(),$this->get_content(),$this->get_description(),
										  $tmp,$this->get_media_query(),$now,$now));
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$this->_data['id'] = $db->Insert_ID();
		$sid = (int)$this->_data['id'];

		$dl = $this->get_designs();
		if( $dl ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE." (css_id,design_id,item_order) VALUES($sid,?,?)";
			$item_order = 1;
			foreach( $dl as $one ) {
				$dbr = $db->Execute($query,[ (int)$one,$item_order++ ] );
			}
		}

		$this->_dirty = FALSE;
		CmsTemplateCache::clear_cache();
		audit($sid,'Stylesheet',"Created: {$this->get_name()}");
	}

	/**
	 * Save this stylesheet to the database
	 * Objects are only saved if they are dirty (have been modified in some way, or have no id)
	 *
	 * This method sends events before and after saving.
	 * EditStylesheetPre is sent before an existing stylesheet is saved to the database
	 * EditStylesheetPost is sent after an existing stylesheet is saved to the database
	 * AddStylesheetPre is sent before a new stylesheet is saved to the database
	 * AddStylesheetPost is sent after a new stylesheet is saved to the database
	 *
	 * @throws CmsSQLErrorException
	 */
	public function save()
	{
		if( $this->get_id() > 0 ) {
			HookManager::do_hook('Core::EditStylesheetPre',array(get_class($this)=>$this));
			$this->_update();
			HookManager::do_hook('Core::EditStylesheetPost',array(get_class($this)=>$this));
			return;
		}
		HookManager::do_hook('Core::AddStylesheetPre',array(get_class($this)=>$this));
		$this->_insert();
		HookManager::do_hook('Core::AddStylesheetPost',array(get_class($this)=>$this));
	}

	/**
	 * Delete this stylesheet from the database
	 * This method deletes the appropriate records from the databas,
	 * deletes the id from this object, and marks the object as dirty so that it can be saved again
	 *
	 * This method triggers the DeleteStylesheetPre and DeleteStylesheetPost events
	 */
	public function delete()
	{
		$sid = $this->get_id();
		if( $sid == 0 ) return;

		HookManager::do_hook('Core::DeleteStylesheetPre',array(get_class($this)=>$this));
		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ?';
		$dbr = $db->Execute($query,array($sid));

		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->Execute($query,array($sid));

		@unlink($this->get_content_filename());

		CmsTemplateCache::clear_cache();
		audit($sid,'Stylesheet',"Deleted: {$this->get_name()}");
		// Events::SendEvent('Core','DeleteStylesheetPost',array(get_class($this)=>$this));
		HookManager::do_hook('Core::DeleteStylesheetPost',array(get_class($this)=>$this));
		$this->_data['id'] = 0;
		$this->_dirty = TRUE;
	}

	/**
	 * @ignore
	 */
	private static function get_locks()
	{
		if( !self::$_lock_cache_loaded ) {
			$tmp = CmsLockOperations::get_locks('stylesheet');
			if( $tmp && is_array($tmp) ) {
				self::$_lock_cache = array();
				foreach( $tmp as $one ) {
					self::$_lock_cache[$one['oid']] = $one;
				}
			}
			self::$_lock_cache_loaded = TRUE;
		}
		return self::$_lock_cache;
	}

	/**
	 * Get the lock (if any) for this stylesheet
	 * @see CmsLock
	 *
	 * @return mixed CmsLock | null
	 */
	public function get_lock()
	{
		$locks = self::get_locks();
		$sid = $this->get_id();
		return ( $locks && isset($locks[$sid]) ) ? $locks[$sid] : null;
	}

	/**
	 * Test if this stylesheet currently has a lock held by a user other
	 * than the specified one
	 *
	 * @param int $userid since 2.2.22F2 Optional user id Default 0
	 * @return bool
	 */
	public function locked($userid = 0)
	{
		$lock = $this->get_lock();
		if( is_object($lock) ) {
			if( $userid == 0 || $userid != $lock['uid'] ) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Test if this stylesheet is locked by an expired lock.
	 * If the object is not locked false is returned
	 *
	 * @return bool
	 */
	public function lock_expired()
	{
		$lock = $this->get_lock();
		if( !is_object($lock) ) return FALSE;
		return $lock->expired();
	}

	/**
	 * @ignore
	 */
	private static function _load_from_data($row,$design_list = [])
	{
		foreach( [
		'id' => 0,
		'name' => '',
		'content' => '',
		'description' => '',
		'media_type' => '', //comma-separated names
		'media_query' => '',
		'created' => 0,
		'modified' => 0] as $fld => $val) {
			if( !isset($row[$fld]) ) {
				$row[$fld] = $val;
			}
			elseif( $val === '' ) {
				$row[$fld] = (string)$row[$fld];
			}
			else {
				$row[$fld] = (int)$row[$fld];
			}
		}
		$row['media_type'] = explode(',',$row['media_type']);
		$ob = new self();
		$ob->_data = $row;
		$fn = $ob->get_content_filename();
		if( is_file($fn) && is_readable($fn) ) {
			$ob->_data['content'] = file_get_contents($fn);
			$ob->_data['modified'] = filemtime($fn);
		}
		if( $design_list && is_array($design_list) ) $ob->_design_assoc = $design_list;

		self::$_css_cache[$row['id']] = $ob;
		self::$_name_cache[$row['name']] = $row['id'];
		return $ob;
	}

	/**
	 * Get the specified stylesheet object
	 *
	 * @param mixed $a Either an integer stylesheet id, or a string stylesheet name.
	 * @param bool $force  @since 2.2.21F2 Whether to always re-generate the stylesheet object, ignoring any cache. Default false.
	 * @return CmsLayoutStylesheet
	 * @throws CmsInvalidDataException
	 */
	public static function load($a,$force = FALSE)
	{
		// check the cache first..
		$db = CmsApp::get_instance()->GetDb();
		$row = [];
		if( is_numeric($a) && (int)$a > 0 ) {
			$a = (int)$a;
			if( !$force && isset(self::$_css_cache[$a]) ) {
				return self::$_css_cache[$a];
			}
			// not in cache
			$query = 'SELECT id,name,content,description,media_type,media_query,created,modified FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->GetRow($query,array($a));
		}
		elseif( is_string($a) && strlen($a) > 0 ) {
			if( isset(self::$_name_cache[$a]) ) {
				$b = (int)self::$_name_cache[$a];
				if( !$force && isset(self::$_css_cache[$b]) ) {
					return self::$_css_cache[$b];
				}
			}
			// not in cache
			$query = 'SELECT id,name,content,description,media_type,media_query,created,modified FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$row = $db->GetRow($query,array($a));
		}
		if( !$row ) throw new CmsInvalidDataException('Could not find stylesheet identified by '.$a);

		return self::_load_from_data($row);
	}

	/**
	 * Load multiple stylesheets in an optimized fashion
	 *
	 * This method does not throw exceptions if one requested id, or name does not exist.
	 *
	 * @param array $ids Array of integer stylesheet ids or an array of string stylesheet names.
	 * @param bool $deep wether or not to load associated data. Default true.
	 * @return array Array of CmsLayoutStylesheet objects
	 * @throws CmsInvalidDataException
	 */
	public static function load_bulk($ids,$deep = TRUE)
	{
		if( !is_array($ids) || count($ids) == 0 ) return [];

		// clean up the input data
		$is_ints = FALSE;
		if( is_numeric($ids[0]) && (int)$ids[0] > 0 ) {
			$is_ints = TRUE;
			for( $i = 0, $n = count($ids); $i < $n; $i++ ) {
				$ids[$i] = (int)$ids[$i];
			}
		}
		elseif( is_string($ids[0]) && strlen($ids[0]) > 0 ) {
			for( $i = 0, $n = count($ids); $i < $n; $i++ ) {
				$ids[$i] = "'".trim($ids[$i])."'";
			}
		}
		else {
			// what ??
			throw new CmsInvalidDataException('Invalid data passed to '.__CLASS__.'::'.__METHOD__);
		}
		$ids = array_unique($ids);

		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT id,name,content,description,media_type,media_query,created,modified FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.implode(',',$ids).')';
		if( !$is_ints ) $query = 'SELECT id,name,content,description,media_type,media_query,created,modified FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name IN ('.implode(',',$ids).')';

		$dbr = $db->GetArray($query);
		$out = array();
		if( is_array($dbr) && count($dbr) ) {
			$designs_by_css = array();
			if( $deep ) {
				$ids2 = array();
				foreach( $dbr as $row ) {
					$ids2[] = $row['id'];
					$designs_by_css[$row['id']] = array();
				}
				$dquery = 'SELECT design_id,css_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id IN ('.implode(',',$ids2).') ORDER BY css_id';
				$dbr2 = $db->GetArray($dquery);
				foreach( $dbr2 as $row ) {
					$designs_by_css[$row['css_id']][] = $row['design_id'];
				}
			}

			// this makes sure that the returned array matches the order specified.
			foreach( $ids as $one ) {
				$found = [];
				if( $is_ints ) {
					// find item in $dbr by id
					foreach( $dbr as $row ) {
						if( $row['id'] == $one ) {
							$found = $row;
							break;
						}
					}
				}
				else {
					$one = trim($one,"'");
					// find item in $dbr by name
					foreach( $dbr as $row ) {
						if( $row['name'] == $one ) {
							$found = $row;
							break;
						}
					}
				}

				$id = $found['id'];
				$tmp = self::_load_from_data($found,(isset($designs_by_css[$id]))?$designs_by_css[$id]:[]);
				if( is_object($tmp) ) $out[] = $tmp;
			}
		}

		return $out;
	}

	/**
	 * Get all stylesheets
	 *
	 * @param bool $as_names Flag indicating the wanted output format. Default false.
	 * @return array If $as_names is true then the array will be a map of
	 *  stylesheet ids to names, suitable for use in an html select element.
	 *  Otherwise, a map of ids to CmsLayoutStylesheet objects.
	 */
	public static function get_all($as_names = FALSE)
	{
		$db = CmsApp::get_instance()->GetDb();

		if( $as_names ) {
			$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY name';
			return $db->GetAssoc($query);
		}
		else {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY modified DESC';
			$ids = $db->GetCol($query);
			return self::load_bulk($ids,FALSE);
		}
	}

	/**
	 * Test if the specific stylesheet (by name or id) is loaded
	 *
	 * @param mixed $id Either an integer stylesheet id, or a string stylesheet name
	 * @return bool
	 */
	public static function is_loaded($id)
	{
		if( is_numeric($id) && (int)$id > 0 ) {
			if( isset(self::$_css_cache[$id]) ) return TRUE;
		}
		elseif( is_string($id) && strlen($id) > 0 ) {
			if( isset(self::$_name_cache[$id]) ) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Generate a unique name for a stylesheet
	 *
	 * @throws CmsInvalidDataException
	 * @throws CmsLogicException
	 * @param string $prototype A prototype template name
	 * @param string $prefix An optional name prefix.
	 */
	public static function generate_unique_name($prototype,$prefix = '')
	{
		if( !$prototype ) throw new CmsInvalidDataException('Prototype name cannot be empty');
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
		for( $i = 0; $i < 25; $i++ ) {
			$name = $prefix.$prototype; // $i = 1
			if( $i == 0 ) $name = $prototype;
			elseif( $i > 1 ) $name = $prefix.$prototype.' '.$i;
			$dbr = $db->GetOne($query,array($name));
			if( !$dbr ) return $name;
		}
		throw new CmsLogicException('Could not generate a template name for '.$prototype);
	}

	/**
	 * Get the name of the file used or potentially used to record this stylsheet's contents.
	 *
	 * @since 2.2
	 * @return string
	 */
	public function get_content_filename()
	{
		$config = cms_config::get_instance();
		$name = munge_string_to_url($this->get_name()).'.'.$this->get_id().'.css';
		return cms_join_path($config['assets_path'],'css',$name);
	}

	/**
	 * Is this stylesheet recorded in a filesystem file?
	 *
	 * @since 2.2
	 * @return bool
	 */
	public function has_content_file()
	{
		$fn = $this->get_content_filename();
		return is_file($fn) && is_readable($fn);
	}
} // end of class

#
# EOF
#
