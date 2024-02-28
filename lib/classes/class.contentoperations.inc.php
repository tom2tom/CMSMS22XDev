<?php
#CMS Made Simple class ContentOperations
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

use CMSMS\internal\global_cachable;
use CMSMS\internal\global_cache;

/**
 * Singleton class of properties and methods related to content pages
 *
 * @abstract
 * @since 0.8
 * @package CMS
 * @license GPL
 */
class ContentOperations
{
	/**
	 * @ignore
	 */
	private $_quickfind;

	/**
	 * @ignore
	 */
	private $_content_types;

	/**
	 * @ignore
	 */
	private static $_instance;

	/**
	 * @ignore
	 */
	private $_authorpages;

	/**
	 * @ignore
	 */
	private $_ownedpages;

	/**
	 * @ignore
	 */
	protected function __construct() {}

	/**
	 * Return the only allowed object of this class
	 *
	 * @return ContentOperations
	 */
	public static function get_instance()
	{
		if( !self::$_instance ) self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * @ignore
	 */
	public static function setup_cache()
	{
		// three caches, the flat list, the tree and the quicklist
		$obj = new global_cachable('content_flatlist',function()
			{
				$query = 'SELECT content_id,parent_id,item_order,content_alias,active FROM '.CMS_DB_PREFIX.'content ORDER BY hierarchy';
				$db = CmsApp::get_instance()->GetDb();
				return $db->GetArray($query);
			});
		global_cache::add_cachable($obj);

		$obj = new global_cachable('content_tree',function()
			{
				$flatlist = global_cache::get('content_flatlist');
				// todo, embed this herer
				return cms_tree_operations::load_from_list($flatlist);
			});
		global_cache::add_cachable($obj);

		$obj = new global_cachable('content_quicklist',function()
			{
				$tree = global_cache::get('content_tree');
				return $tree->getFlatList();
			});
		global_cache::add_cachable($obj);
	}

	/**
	 * Return a content object for the currently requested page.
	 *
	 * @since 1.9
	 * @return getContentObject()
	 */
	public function getContentObject()
	{
		return CmsApp::get_instance()->get_content_object();
	}

	/**
	 * Given an array of content_type and serialized_content, reconstructs a
	 * content object.  It will handled loading the content type if it hasn't
	 * already been loaded.
	 *
	 * Expects an associative array with 2 elements:
	 *   content_type: string A content type name
	 *   serialized_content: string Serialized form data
	 *
	 * @see ContentBase::ListContentTypes
	 * @param  array $data
	 * @return ContentBase A content object derived from ContentBase
	 */
	public function LoadContentFromSerializedData(&$data)
	{
		if( !isset($data['content_type']) && !isset($data['serialized_content']) ) return FALSE;

		$contenttype = 'content';
		if( isset($data['content_type']) ) $contenttype = $data['content_type'];

		$contentobj = $this->CreateNewContent($contenttype);
		$contentobj = unserialize($data['serialized_content']);
		return $contentobj;
	}

	/**
	 * Load a specific content type
	 *
	 * This method is called from the autoloader.  There is no need to call it internally
	 *
	 * @internal
	 * @access private
	 * @final
	 * @since 1.9
	 * @param mixed The type.  Either a string, or an instance of CmsContentTypePlaceHolder
	 */
	final public function LoadContentType($type)
	{
		if( is_object($type) && $type instanceof CmsContentTypePlaceHolder ) $type = $type->type;

		$ctph = $this->_get_content_type($type);
		if( is_object($ctph) ) {
			if( !class_exists( $ctph->class ) && file_exists( $ctph->filename ) ) include_once( $ctph->filename );
		}

		return $ctph;
	}

	/**
	 * Creates a new, empty content object of the given type.
	 *
	 * if the content type is registered with the system,
	 * and the class does not exist, the appropriate filename will be included
	 * and then, if possible a new object of the designated type will be
	 * instantiated.
	 *
	 * @param mixed $type The type.  Either a string, or an instance of CmsContentTypePlaceHolder
	 * @return ContentBase (A valid object derived from ContentBase)
	 */
	public function CreateNewContent($type)
	{
		if( is_object($type) && $type instanceof CmsContentTypePlaceHolder ) $type = $type->type;

		$result = null; // no object
		$ctph = $this->LoadContentType($type);
		if( is_object($ctph) && class_exists($ctph->class) ) $result = new $ctph->class();
		return $result;
	}

	/**
	 * Given a content id, load and return the loaded content object.
	 *
	 * @param int $id The id of the content object to load
	 * @param bool $loadprops Also load the properties of that content object. Defaults to false.
	 * @return mixed The loaded content object. If nothing is found, returns NULL.
	 */
	public function LoadContentFromId($id,$loadprops=false)
	{
		$id = (int) $id;
		if( $id < 1 ) $id = $this->GetDefaultContent();
		if( cms_content_cache::content_exists($id) ) return cms_content_cache::get_content($id);

		$db = CmsApp::get_instance()->GetDb();
		$query = "SELECT * FROM ".CMS_DB_PREFIX."content WHERE content_id = ?";
		$row = $db->GetRow($query, array($id));
		if ($row) {
			$classtype = strtolower($row['type']);
			$contentobj = $this->CreateNewContent($classtype);
			if ($contentobj) {
				$contentobj->LoadFromData($row, $loadprops);
				cms_content_cache::add_content($id,$row['content_alias'],$contentobj);
				return $contentobj;
			}
		}
		return null; //no object
	}

	/**
	 * Given a content alias, load and return the loaded content object.
	 *
	 * @param int $alias The alias of the content object to load
	 * @param bool $only_active If true, only return the object if it's active flag is true. Defaults to false.
	 * @return ContentBase The loaded content object. If nothing is found, returns NULL.
	 */
	public function LoadContentFromAlias($alias, $only_active = false)
	{
		if( cms_content_cache::content_exists($alias) ) return cms_content_cache::get_content($alias);

		$hm = Cmsapp::get_instance()->GetHierarchyManager();
		$node = $hm->sureGetNodeByAlias($alias);
		if( !$node ) return null;
		if( $only_active && !$node->get_tag('active') ) return null;
		return $this->LoadContentFromId($node->get_tag('id'));
	}

	/**
	 * Returns the id of the content marked as default.
	 *
	 * @return int The id of the default content page
	 */
	public function GetDefaultContent()
	{
		return global_cache::get('default_content');
	}

	/**
	 * Load standard CMS content types
	 *
	 * This internal method looks through the contenttypes directory
	 * and loads the placeholders for them.
	 *
	 * @since 1.9
	 * @access private
	 * @internal
	 */
	private function _get_std_content_types()
	{
		$result = array();
		$dir = __DIR__.'/contenttypes';
		$files = glob($dir.'/*.inc.php');
		if( is_array($files) ) {
			foreach( $files as $one ) {
				$obj = new CmsContentTypePlaceHolder();
				$class = basename($one,'.inc.php');
				$type  = strtolower($class);

				$obj->class = $class;
				$obj->type = $type;
				$obj->filename = $one;
				$obj->loaded = false;
				if( $obj->type == 'link' ) {
					// cough... big hack... cough.
					$obj->friendlyname_key = 'contenttype_redirlink';
				}
				else {
					$obj->friendlyname_key = 'contenttype_'.$type;
				}
				$result[$type] = $obj;
			}
		}

		return $result;
	}

	/**
	 * @ignore
	 */
	private function _get_content_types()
	{
		if( !is_array($this->_content_types) ) {
			// get the standard ones.
			$this->_content_types = $this->_get_std_content_types();

			// get the list of modules that have content types.
			// and load them.  content types from modules are
			// registered in the constructor.
			$module_list = ModuleOperations::get_instance()->get_modules_with_capability(CmsCoreCapabilities::CONTENT_TYPES);
			if( is_array($module_list) && count($module_list) ) {
				foreach( $module_list as $module_name ) {
					cms_utils::get_module($module_name);
				}
			}
		}

		return $this->_content_types;
	}

	/**
	 * Function to return a content type given it's name
	 *
	 * @since 1.9
	 * @access private
	 * @internal
	 * @param string The content type name
	 * @return CmsContentTypePlaceHolder placeholder object.
	 */
	private function _get_content_type($name)
	{
		$name = strtolower($name);
		$this->_get_content_types();
		if( is_array($this->_content_types) ) {
			if( isset($this->_content_types[$name]) && $this->_content_types[$name] instanceof CmsContentTypePlaceHolder ) {
				return $this->_content_types[$name];
			}
		}
		return null; // no object
	}

	/**
	 * Register a new content type
	 *
	 * @since 1.9
	 * @param CmsContentTypePlaceHolder object
	 * @return bool
	 */
	public function register_content_type(CmsContentTypePlaceHolder $obj)
	{
		$this->_get_content_types();
		if( isset($this->_content_types[$obj->type]) ) return FALSE;

		if( !class_exists( $obj->class ) && is_file( $obj->filename ) ) require_once $obj->filename;
		$this->_content_types[$obj->type] = $obj;
		return TRUE;
	}

	/**
	 * Returns a hash of valid content types (classes that extend ContentBase)
	 * Each key is the identifier of the class recorded in the database, or else the actual class name.
	 * Each value is the text returned by the type's FriendlyName() method.
	 *
	 * @param bool $byclassname Optional flag, whether to use classname as array key. Default false.
	 * @param bool $allowed Optional flag, whether to filter the listed types
	 *  in accord with the 'disallowed_contenttypes' site preference. Default false.
	 * @param bool $system Optional flag, whether to report only system content types. Default false.
	 * @return array content types registered in the system.
	 */
	public function ListContentTypes($byclassname = false,$allowed = false,$system = false)
	{
		$disallowed_a = array();
		if( $allowed ) {
			$tmp = cms_siteprefs::get('disallowed_contenttypes');
			if( $tmp ) $disallowed_a = explode(',',$tmp);
		}

		$result = array();
		$this->_get_content_types();
		$types = $this->_content_types;
		if( $types ) {
			foreach( $types as $obj ) {
				global $CMS_ADMIN_PAGE;
				if( !isset($obj->friendlyname) && isset($obj->friendlyname_key) && isset($CMS_ADMIN_PAGE) ) {
					$txt = lang($obj->friendlyname_key);
					$obj->friendlyname = $txt;
				}
				if( !$allowed || !$disallowed_a || !in_array($obj->type,$disallowed_a) ) {
					if( $byclassname ) {
						$result[$obj->class] = $obj->friendlyname;
					}
					else {
						$result[$obj->type] = $obj->friendlyname;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Update the hierarchy position of all content items, or a subtree of them.
	 * This is an expensive operation, but must be called each time one
	 * or more content pages' position in the page structure has changed.
	 *
	 * @param string $tophier Optional topmost hierarchy-value to use, to
	 *  enable processing a sub-tree of the entire pages hierarchy.
	 *  Since 2.2.19
	 */
	public function SetAllHierarchyPositions($tophier = '')
	{
		// load some data about all pages
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT content_id, parent_id, item_order, content_alias AS alias, hierarchy, id_hierarchy, hierarchy_path, COALESCE(modified_date,create_date) AS at FROM '.CMS_DB_PREFIX.'content';
		if( $tophier ) {
			$query .= " WHERE hierarchy LIKE '$tophier%'";
		}
		$query .= ' ORDER BY hierarchy'; // interim
		$list = $db->GetAssoc($query);
		if( !$list ) {
			if( $tophier ) {
				audit('','Pages hierarchy update','Cannot process unrecognised path '.$tophier);
			}
			return;// nothing to do
		}
		if( $tophier ) {
			reset($list);
			$current_id = key($list);
			if( $list[$current_id]['hierarchy'] != $tophier ) {
				audit('','Pages hierarchy update','Failed to find subtree for '.$tophier);
				return;
			}
			if( count($list) == 1 ) {
				return;// nothing to do
			}
			$list[$current_id]['parent_id'] = -1; // treat this one as top
			$list[$current_id]['V'] = 1; // populated-indicator, no further up-walk
		}
		/*hierarchy-value order will be like
		00001
		00001.00001
		00001.00002
		00001.00003
		00002
		00002.00001
		00002.00002
		etc
		possibly with gap(s) in the numbering (and if so, probably in the item_order's)
		and possibly inconsistent item_order and last-segment of hierarchy (if so, here we assume item_order prevails)
		normally, an item with parent id -1 would have a single-segment hierarchy like 00002, but things might be messed up after reordering
		*/
		foreach( $list as &$cur_row ) {
			$cur_row['phier'] = substr($cur_row['hierarchy'],0,-6); // too bad this can't readily be populated by db server
		}
		unset($cur_row);

		uasort($list,function($a,$b) {
			$v = strcmp($a['phier'],$b['phier']);
			if( $v == 0 ) {
				$v = $a['item_order'] - $b['item_order'];
				if( $v == 0 ) {
					$v = strcmp($a['at'],$b['at']); //older first
				}
			}
			return $v;
		});

		$lvlorders = [];
		$lastparent = '-'; // something unmatched in array TODO or $list[$current_id]['phier'] ?
		$cnt = 0;
		foreach( $list as $content_id => &$cur_row ) {
			if( $tophier && $tophier == $cur_row['hierarchy'] ) {
				continue; // no up-walk for this one
			}
			$current_id = $content_id;
			$hier = $idhier = $pathhier = ''; // build these up from lowest level

			while( $current_id > 0 ) {
				if( isset($list[$current_id]) ) {
					$row = $list[$current_id];
					// terminate ASAP
					if( !empty($row['V']) ) {
						$hier = $row['hierarchy'].'.'. $hier;
						$idhier = $row['id_hierarchy'].'.'.$idhier;
						$pathhier = $row['hierarchy_path'].'/'.$pathhier;
						break;
					}
					if( $row['phier'] != $lastparent ) {
						$lastparent = $row['phier']; //possibly '' or $tophier
						if (!isset($lvlorders[$lastparent])) {
							$lvlorders[$lastparent] = 1;
						}
					}
					$item_order = $lvlorders[$lastparent]++;
					$hier = str_pad($item_order,5,'0',STR_PAD_LEFT) . '.' . $hier; // in future, 3-wide would suffice
					$idhier = $current_id . '.' . $idhier;
					$pathhier = $row['alias'] . '/' . $pathhier;
					$current_id = $row['parent_id'];
				}
				else {
					throw new RuntimeException('Internal error - No hierarchy data for page id '.$current_id); //a dangling or non-existent page - how to remediate?
				}
			}
			//strip trailing separators
			if( $hier ) $hier = substr($hier, 0, -1);
			if( $idhier ) $idhier = substr($idhier, 0, -1);
			if( $pathhier ) $pathhier = substr($pathhier, 0, -1);
			// if we changed something, record the change(s)
			if( ($hier && $hier != $cur_row['hierarchy'])
			 || ($idhier && $idhier != $cur_row['id_hierarchy'])
			 || ($pathhier && $pathhier != $cur_row['hierarchy_path']) ) {
				$cur_row['item_order'] = $item_order;
				$cur_row['hierarchy'] = $hier;
				$cur_row['id_hierarchy'] = $idhier;
				$cur_row['hierarchy_path'] = $pathhier;
				$cur_row['W'] = 1;
				$cnt++;
			}
			$cur_row['V'] = 1;
		}
		unset($cur_row);
		if( $cnt > 0 ) {
			$stmt = $db->Prepare('UPDATE '.CMS_DB_PREFIX.'content SET item_order = ?, hierarchy = ?, id_hierarchy = ?, hierarchy_path = ? WHERE content_id = ?');
			$v = false;
			foreach( $list as $content_id => $row ) {
				if( !empty($row['W']) ) {
					$v = true;
					$stmt->Execute(array($row['item_order'],$row['hierarchy'],$row['id_hierarchy'],$row['hierarchy_path'],$content_id));
				}
			}
			if( $v ) $this->SetContentModified();
		}
		unset($list); // assist gc
		$list = null;
	}

	/**
	 * Get the date of last content modification
	 *
	 * @since 2.0
	 * @return unix timestamp representing the last time a content page was modified.
	 */
	public function GetLastContentModification()
	{
		return global_cache::get('latest_content_modification');
	}

	/**
	 * Clear all content-related caches so that during the next request they will be re-populated
	 *
	 * @internal
	 * @access private
	 */
	public function SetContentModified()
	{
		global_cache::clear('latest_content_modification');
		global_cache::clear('default_content');
		global_cache::clear('content_flatlist');
		global_cache::clear('content_tree');
		global_cache::clear('content_quicklist');
		cms_content_cache::clear();
	}

	/**
	 * Loads a set of content objects into the cached tree.
	 *
	 * @param bool $loadcontent If false, only create the nodes in the tree, don't load the content objects
	 * @return cms_content_tree The cached tree of content
	 * @deprecated
	 */
	public function GetAllContentAsHierarchy($loadcontent = false)
	{
		return global_cache::get('content_tree');
	}

	/**
	 * Load all content in the database into memory
	 * Use with caution this can chew up a lot of memory on larger sites.
	 *
	 * @param bool $loadprops Load extended content properties or just the page structure and basic properties
	 * @param bool $inactive  Load inactive pages as well
	 * @param bool $showinmenu Load pages marked as show in menu
	 */
	public function LoadAllContent($loadprops = FALSE,$inactive = FALSE,$showinmenu = FALSE)
	{
		static $_loaded = 0;
		if( $_loaded == 1 ) return;
		$_loaded = 1;

		$db = CmsApp::get_instance()->GetDb();

		$expr = array();
		$parms = array();
		if( !$inactive ) {
			$expr[] = 'active = ?';
			$parms[] = 1;
		}
		if( $showinmenu ) {
			$expr[] = 'show_in_menu = ?';
			$parms[] = 1;
		}

		$loaded_ids = cms_content_cache::get_loaded_page_ids();
		if( is_array($loaded_ids) && count($loaded_ids) ) {
			$expr[] = 'content_id NOT IN ('.implode(',',$loaded_ids).')';
		}

		$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content FORCE INDEX ('.CMS_DB_PREFIX.'index_content_by_idhier) WHERE ';
		$query .= implode(' AND ',$expr);
		$dbr = $db->Execute($query,$parms);

		if( $loadprops ) {
			$child_ids = array();
			while( !$dbr->EOF() ) {
				$child_ids[] = $dbr->fields['content_id'];
				$dbr->MoveNext();
			}
			$dbr->MoveFirst();

			$tmp = [];
			if( count($child_ids) ) {
				// get all the properties for the child_ids
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content_props WHERE content_id IN ('.implode(',',$child_ids).') ORDER BY content_id';
				$tmp = $db->GetArray($query);
			}

			// re-organize the tmp data into a hash of arrays of properties for each content id.
			if( $tmp ) {
				$contentprops = array();
				for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
					$content_id = $tmp[$i]['content_id'];
					if( in_array($content_id,$child_ids) ) {
						if( !isset($contentprops[$content_id]) ) $contentprops[$content_id] = array();
						$contentprops[$content_id][] = $tmp[$i];
					}
				}
				unset($tmp);
			}
		}

		// build the content objects
		$content_types = array_keys($this->ListContentTypes());
		while( !$dbr->EOF() ) {
			$row = $dbr->fields;
			$id = $row['content_id'];

			if( in_array($row['type'], $content_types)) {
				$contentobj = $this->CreateNewContent($row['type']);
				if ($contentobj) {
					$contentobj->LoadFromData($row, false);
					if( $loadprops && $contentprops && isset($contentprops[$id]) ) {
						// load the properties from local cache.
						$props = $contentprops[$id];
						foreach( $props as $oneprop ) {
							$contentobj->SetPropertyValueNoLoad($oneprop['prop_name'],$oneprop['content']);
						}
					}

					// cache the content objects
					cms_content_cache::add_content($id,$contentobj->Alias(),$contentobj);
				}
			}

			$dbr->MoveNext();
		}
		$dbr->Close();
	}

	/**
	 * Loads additional, active children into a given tree object
	 *
	 * @param int $id The parent of the content objects to load into the tree
	 * @param bool $loadprops If true, load the properties of all loaded content objects
	 * @param bool $all If true, load all content objects, even inactive ones.
	 * @param array   $explicit_ids (optional) array of explicit content ids to load
	 * @author Ted Kulp
	 */
	public function LoadChildren($id, $loadprops = false, $all = false, $explicit_ids = array() )
	{
		$db = CmsApp::get_instance()->GetDb();

		$contentrows = [];
		if( is_array($explicit_ids) && count($explicit_ids) ) {
			$loaded_ids = cms_content_cache::get_loaded_page_ids();
			if( is_array($loaded_ids) && count($loaded_ids) ) $explicit_ids = array_diff($explicit_ids,$loaded_ids);
		}
		if( is_array($explicit_ids) && count($explicit_ids) ) {
			$expr = 'content_id IN ('.implode(',',$explicit_ids).')';
			if( !$all ) $expr .= ' AND active = 1';

			// note, this is MySQL-specific SQL
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content FORCE INDEX ('.CMS_DB_PREFIX.'index_content_by_idhier) WHERE '.$expr.' ORDER BY hierarchy';
			$contentrows = $db->GetArray($query);
		}
		else {
			if( !$id ) $id = -1;
			// get the content rows
			$query = "SELECT * FROM ".CMS_DB_PREFIX."content WHERE parent_id = ? AND active = 1 ORDER BY hierarchy";
			if( $all ) $query = "SELECT * FROM ".CMS_DB_PREFIX."content WHERE parent_id = ? ORDER BY hierarchy";
			$contentrows = $db->GetArray($query, array($id));
		}

		// get the content ids from the returned data
		$contentprops = [];
		if( $loadprops ) {
			$child_ids = array();
			if(!is_array($contentrows) ) { $contentrows = []; }

			for( $i = 0, $n = count($contentrows); $i < $n; $i++ ) {
				$child_ids[] = $contentrows[$i]['content_id'];
			}

			$tmp = [];
			if( count($child_ids) ) {
				// get all the properties for the child_ids
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content_props WHERE content_id IN ('.implode(',',$child_ids).') ORDER BY content_id';
				$tmp = $db->GetArray($query);
			}

			// re-organize the tmp data into a hash of arrays of properties for each content id.
			if( $tmp ) {
				$contentprops = [];
				for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
					$content_id = $tmp[$i]['content_id'];
					if( in_array($content_id,$child_ids) ) {
						if( !isset($contentprops[$content_id]) ) $contentprops[$content_id] = array();
						$contentprops[$content_id][] = $tmp[$i];
					}
				}
				unset($tmp);
			}
		}

		// build the content objects
		for( $i = 0, $n = count($contentrows); $i < $n; $i++ ) {
			$row =& $contentrows[$i];
			$id = $row['content_id'];

			if (!in_array($row['type'], array_keys($this->ListContentTypes()))) continue;
//redundant			$contentobj = new Content();
			$contentobj = $this->CreateNewContent($row['type']);

			if ($contentobj) {
				$contentobj->LoadFromData($row, false);
				if( $loadprops && $contentprops && isset($contentprops[$id]) ) {
					// load the properties from local cache.
					foreach( $contentprops[$id] as $oneprop ) {
						$contentobj->SetPropertyValueNoLoad($oneprop['prop_name'],$oneprop['content']);
					}
					unset($contentprops[$id]);
				}

				// cache the content objects
				cms_content_cache::add_content($id,$contentobj->Alias(),$contentobj);
				unset($contentobj);
			}
		}

		unset($contentrows);
		unset($contentprops);
	}

	/**
	 * Sets the default content to the given id
	 *
	 * @param int $id The id to set as default
	 * @author Ted Kulp
	 */
	public function SetDefaultContent($id)
	{
		if ($id > 0) {
			$db = CmsApp::get_instance()->GetDb();
			$query = "SELECT content_id FROM ".CMS_DB_PREFIX."content WHERE default_content=1";
			$old_id = $db->GetOne($query);
			if ($old_id > 0 && $old_id != $id) {
				$one = $this->LoadContentFromId($old_id);
				$one->SetDefaultContent(false);
				$one->Save();
			}
			$one = $this->LoadContentFromId($id);
			$one->SetDefaultContent(true);
			$one->Save();
		}
	}

	/**
	 * Returns an array of all content objects in the system, active or not.
	 *
	 * Caution: it is entirely possible that this method (and other similar
	 * methods for loading content) will result in a memory outage if there is a
	 * large number of content objects and/or large number of content properties.
	 *
	 * @param bool $loadprops Whether to also load extended properties. Default true.
	 * @return array The array of content objects
	 */
	public function GetAllContent($loadprops=true)
	{
		debug_buffer('get all content...');
		$gCms = CmsApp::get_instance();
		$hm = $gCms->GetHierarchyManager();
		$list = $hm->getFlatList();

		$this->LoadAllContent($loadprops);
		$output = array();
		foreach( $list as &$one ) {
			$tmp = $one->GetContent(false,true,true);
			if( is_object($tmp) ) $output[] = $tmp;
		}
		unset($one);

		debug_buffer('end get all content...');
		return $output;
	}

	/**
	 * Create a hierarchical ordered dropdown of content objects.
	 * If $current (or $parent/$selected TBA) variables are passed, care is taken
	 * to ensure that children which could cause a loop are omitted from
	 * a dropdown created for changing a content object's parent.
	 *
	 * This method was rewritten for 2.0 to use the jquery hierselector widget
	 * to better accommodate larger websites, and rewritten again for 2.2 with
	 * a slightly different API.
	 * @todo Because $ignore_current parameter is now unused, consider a replacement for this method.
	 *
	 * @param int $current Numeric id of the content object we are working with.
	 *  Used with $allowcurrent to omit children of the current content object
	 *  or itself. Default 0
	 * @param int $selected Numeric id of the currently selected content object (new in 2.2). Default 0
	 *  (Before 2.2 this parameter was $parent)
	 * @param string $name The html name of the dropdown. Default 'parent_id'
	 * @param bool $allowcurrent If false, $current cannot be selected, nor
	 *  can its children. Used to prevent circular deadlocks. Default false
	 * @param bool $use_perms If true, shows only the items which the current
	 *  user is authorized to edit. Default false
	 * @param bool $ignore_current UNUSED since 2.0 See $allow_all
	 *  If true, totally ignore the $current parameter by treating every item as valid. Default false
	 * @param bool $allow_all If true, show all items, even the ones which
	 *  are inactive or don't have a valid link. Default false.
	 * @param bool $for_child If true, assume that we want to select a new child
	 *  item and obey the WantsChildren property of each ancestor item (new in 2.2). Default false
	 *  (Before 2.2 this parameter was $use_name)
	 * @return string Html for an input to be hidden plus js to populate a
	 *  dropdown of the hierarchy via ajax and the hierselector widget.
	 */
	public function CreateHierarchyDropdown($current = 0, $selected = 0,
			$name = 'parent_id', $allowcurrent = false, $use_perms = false,
			$ignore_current = false, $allow_all = false, $for_child = false)
	{
		static $count = 0;
		$count++;
		$id = 'cms_hierdropdown'.$count; //TODO API to allow setting this via method args
		$uid = get_userid(FALSE);
		$ttl = lang('title_hierselect');

		$opts = [
		 'current' => (int)$current,
		 'value' => (int)$selected,
		 'allowcurrent' => (bool)$allowcurrent,
		 'allow_all' => (bool)$allow_all,
		 'use_perms' => (bool)$use_perms,
		 'for_child' => (bool)$for_child
		];
//		$opts['use_simple'] = API to indicate that a 'userpages' selector is wanted
		$opts['is_manager'] = check_permission($uid,'Manage All Content') || check_permission($uid,'Modify Any Page');
		$str = '';
		foreach( $opts as $key => $val ) {
			if( is_bool($val) ) $val = ($val) ? 'true' : 'false';
			$str .= "$key:$val, ";
		}
		$str = rtrim($str,' ,'); // no redundant trailer

		$out = <<<EOS
<input type="text" id="$id" name="$name" class="cms_hierdropdown" title="$ttl" value="$selected" size="50" maxlength="50">
<script>
$(function() {
 $('#$id').hierselector({
  $str
 });
});
</script>
EOS;
		return $out;
	}

	/**
	 * Gets the content id of the page marked as default
	 *
	 * @return int The id of the default page. false if not found.
	 */
	public function GetDefaultPageID()
	{
		return $this->GetDefaultContent();
	}

	/**
	 * Returns the content id given a valid content alias.
	 *
	 * @param string $alias The alias to query
	 * @return int The resulting id.  null if not found.
	 */
	public function GetPageIDFromAlias( $alias )
	{
		$hm = CmsApp::get_instance()->GetHierarchyManager();
		$node = $hm->sureGetNodeByAlias($alias);
		if( $node ) return $node->get_tag('id');
	}

	/**
	 * Returns the content id given a valid hierarchical position.
	 *
	 * @param string $position The position to query
	 * @return int The resulting id.  false if not found.
	 */
	public function GetPageIDFromHierarchy($position)
	{
		$gCms = CmsApp::get_instance();
		$db = $gCms->GetDb();

		$query = "SELECT content_id FROM ".CMS_DB_PREFIX."content WHERE hierarchy = ?";
		$row = $db->GetRow($query, array($this->CreateUnfriendlyHierarchyPosition($position)));

		if (!$row) return false;
		return $row['content_id'];
	}

	/**
	 * Returns the content alias given a valid content id.
	 *
	 * @param int $id The content id to query
	 * @return string The resulting content alias, or empty if not found.
	 */
	public function GetPageAliasFromID( $id )
	{
		$node = $this->quickfind_node_by_id($id);
		if( $node ) return $node->getTag('alias');
		return '';
	}

	/**
	 * Check if a content alias is used
	 *
	 * @param string $alias The alias to check
	 * @param int $content_id The id of the current page, if any
	 * @return bool
	 * @since 2.2.2
	 */
	public function CheckAliasUsed($alias,$content_id = -1)
	{
		$alias = trim($alias);
		$content_id = (int) $content_id;

		$params = [ $alias ];
		$query = "SELECT content_id FROM ".CMS_DB_PREFIX."content WHERE content_alias = ?";
		if ($content_id > 0) {
			$query .= " AND content_id != ?";
			$params[] = $content_id;
		}
		$db = CmsApp::get_instance()->GetDb();
		$out = (int) $db->GetOne($query, $params);
		if( $out > 0 ) return TRUE;
	}

	/**
	 * Check if a potential alias is valid.
	 *
	 * @param string $alias The alias to check
	 * @return bool
	 * @since 2.2.2
	 */
	public function CheckAliasValid($alias)
	{
		if( ((int)$alias > 0 || (float)$alias > 0.00001) && is_numeric($alias) ) return FALSE;
		$tmp = munge_string_to_url($alias,TRUE);
		if( $tmp != mb_strtolower($alias) ) return FALSE;
		return TRUE;
	}

	/**
	 * Checks to see if a content alias is valid and not in use.
	 *
	 * @param string $alias The content alias to check
	 * @param int $content_id The id of the current page, for used alias checks on existing pages
	 * @return string The error, if any.  If there is no error, returns empty string.
	 */
	public function CheckAliasError($alias, $content_id = -1)
	{
		if( !$this->CheckAliasValid($alias) ) return lang('invalidalias2');
		if ($this->CheckAliasUsed($alias,$content_id)) return lang('aliasalreadyused');
		return '';
	}

	/**
	 * Converts an unfriendly hierarchy (00001.00001.00001) to friendly format
	 * (1.1.1) usually for display.
	 *
	 * @param string $position The hierarchy position to convert
	 * @return string The unfriendly version of the hierarchy string
	 */
	public function CreateFriendlyHierarchyPosition($position)
	{
		//Change padded numbers back into user-friendly values
		$tmp = '';
		$levels = explode('.',$position);

		foreach ($levels as $onelevel) {
			$tmp .= ltrim($onelevel, '0') . '.';
		}
		return rtrim($tmp, '.');
	}

	/**
	 * Converts a friendly hierarchy (1.1.1) to unfriendly format (00001.00001.00001)
	 * for operational use and storage in the database.
	 *
	 * @param string $position The hierarchy position to convert
	 * @return string The friendly version of the hierarchy string
	 */
	public function CreateUnfriendlyHierarchyPosition($position)
	{
		//Change user-friendly values into padded numbers
		$tmp = '';
		$levels = explode('.',$position);

		foreach ($levels as $onelevel) {
			$tmp .= str_pad($onelevel, 5, '0', STR_PAD_LEFT) . '.'; // or in future, 3-wide would suffice
		}
		return rtrim($tmp, '.');
	}

	/**
	 * Check if the supplied page id is a parent of the specified base page (or the current page)
	 *
	 * @since 2.0
	 * @author Robert Campbell
	 * @param int $test_id Page ID to test
	 * @param int $base_id (optional) Page ID to act as the base page.  The current page is used if not specified.
	 * @return bool
	 */
	public function CheckParentage($test_id,$base_id = 0)
	{
		$gCms = CmsApp::get_instance();
		if( !$base_id ) $base_id = $gCms->get_content_id();
		$base_id = (int)$base_id;
		if( $base_id < 1 ) return FALSE;

		$node = $this->quickfind_node_by_id($base_id);
		while( $node ) {
			if( $node->get_tag('id') == $test_id ) return TRUE;
			$node = $node->get_parent();
		}
		return FALSE;
	}

	/**
	 * Grab URLs from the content table and register them with the route manager.
	 *
	 * @since 1.9
	 * @author Robert Campbell
	 * @internal
	 * @access private
	 */
	public function register_routes()
	{
		$gCms = CmsApp::get_instance();
 		$db = $gCms->GetDb();

		$query = 'SELECT content_id,page_url FROM '.CMS_DB_PREFIX.'content
WHERE active = 1 AND default_content = 0 AND page_url != \'\'';
		$data = $db->GetArray($query);
 		if( is_array($data) ) {
 			foreach( $data as $onerow ) {
 				$route = new CmsRoute($onerow['page_url'],$onerow['content_id'],'',TRUE);
				cms_route_manager::register($route);
			}
		}
	}

	/**
	 * Return a list of pages that the user is owner of.
	 *
	 * @since 2.0
	 * @author Robert Campbell
	 * @param int $userid The userid
	 * @return array Array of integer page id's
	 */
	public function GetOwnedPages($userid)
	{
		if( !is_array($this->_ownedpages) ) {
			$this->_ownedpages = array();

			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE owner_id = ? ORDER BY hierarchy';
			$tmp = $db->GetCol($query,array($userid));
			$data = array();
			for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
				if( $tmp[$i] > 0 ) $data[] = $tmp[$i];
			}

			if( count($data) ) $this->_ownedpages = $data;
		}
		return $this->_ownedpages;
	}

	/**
	 * Test if the user specified owns the specified page
	 *
	 * @param int $userid
	 * @param int $pageid
	 * @return bool
	 */
	public function CheckPageOwnership($userid,$pageid)
	{
		$pagelist = $this->GetOwnedPages($userid);
		return in_array($pageid,$pagelist);
	}

	/**
	 * Return a list of pages that the user has edit access to.
	 *
	 * @since 2.0
	 * @author Robert Campbell
	 * @param int $userid The userid
	 * @return int[] Array of page id's
	 */
	public function GetPageAccessForUser($userid)
	{
		if( !is_array($this->_authorpages) ) {
			$this->_authorpages = array();
			$data = $this->GetOwnedPages($userid);

			// Get all of the pages this user has access to.
			$groups = UserOperations::get_instance()->GetMemberGroups($userid);
			$list = array($userid);
			if( is_array($groups) && count($groups) ) {
				foreach( $groups as $group ) {
					$list[] = $group * -1;
				}
			}

			$db = CmsApp::get_instance()->GetDb();
			$query = "SELECT A.content_id FROM ".CMS_DB_PREFIX.'additional_users A
LEFT JOIN '.CMS_DB_PREFIX.'content B ON A.content_id = B.content_id
WHERE A.user_id IN ('.implode(',',$list).')
ORDER BY B.hierarchy';
			$tmp = $db->GetCol($query);
			for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
				if( $tmp[$i] > 0 && !in_array($tmp[$i],$data) ) $data[] = $tmp[$i];
			}

			if( count($data) ) asort($data);
			$this->_authorpages = $data;
		}
		return $this->_authorpages;
	}

	/**
	 * Check if the specified user has the ability to edit the specified page id
	 *
	 * @param int $userid
	 * @param int $contentid
	 * @return bool
	 */
	public function CheckPageAuthorship($userid,$contentid)
	{
		$author_pages = $this->GetPageAccessForUser($userid);
		return in_array($contentid,$author_pages);
	}

	/**
	 * Test if the specified user account has edit access to all of the peers of the specified page id
	 *
	 * @param int $userid
	 * @param int $contentid
	 * @return bool
	 */
	public function CheckPeerAuthorship($userid,$contentid)
	{
		if( check_permission($userid,'Manage All Content') ) return TRUE;

		$access = $this->GetPageAccessForUser($userid);
		if( !is_array($access) || count($access) == 0 ) return FALSE;

		$node = $this->quickfind_node_by_id($contentid);
		if( !$node ) return FALSE;
		$parent = $node->get_parent();
		if( !$parent ) return FALSE;

		$peers = $parent->get_children();
		if( is_array($peers) && count($peers) ) {
			for( $i = 0, $n = count($peers); $i < $n; $i++ ) {
				if( !in_array($peers[$i]->get_tag('id'),$access) ) return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * A convenience function to find a hierarchy node given the page id
	 * This method will be moved to cms_content_tree at a later date.
	 *
	 * @param int $id The page id
	 * @return mixed cms_content_tree | null
	 */
	public function quickfind_node_by_id($id)
	{
		$list = global_cache::get('content_quicklist');
		if( isset($list[$id]) ) return $list[$id];
		return null;
	}

	/**
	 * Return the name or menu-label of the content whose id is specified.
	 * @since 2.2.19#2
	 *
	 * @param int $id The content id to query
	 * @return string The resulting identifier, or 'Anonymous' if nothing useful found.
	 */
	public function GetPageDescriptor($id)
	{
		$content = $this->LoadContentFromId($id);
		if( $content ) {
			return ($content->Name()) ?: (($content->MenuText()) ?: 'Anonymous');
		}
		return '';
	}
}

/**
 * An alias for the ContentOperations class
 * @package CMS
 * @ignore
 */
class_alias ('ContentOperations', 'ContentManager', false);

/* SUPERSEDED hierarchy updater, to be removed when there is definitely
   no further need for validation of such updating
	/* *
	 * Update the hierarchy position of one item
	 *
	 * @internal
	 * @ignore
	 * @param integer $content_id The content id to update
	 * @param array $hash A hash of all content objects (only certain fields)
	 * @return array maybe empty
	 * /
	private function _set_hierarchy_position($content_id,$hash)
	{
		$row = $hash[$content_id];
		$saved_row = $row;
		$hier = $idhier = $pathhier = '';
		$current_parent_id = $content_id;

		while( $current_parent_id > 0 ) {
			$item_order = max($row['item_order'],1);
			$hier = str_pad($item_order, 5, '0', STR_PAD_LEFT) . "." . $hier;
			$idhier = $current_parent_id . '.' . $idhier;
			$pathhier = $row['alias'] . '/' . $pathhier;
			$current_parent_id = $row['parent_id'];
			if( $current_parent_id < 1 ) break;
			$row = $hash[$current_parent_id];
		}

		if (strlen($hier) > 0) $hier = substr($hier, 0, strlen($hier) - 1);
		if (strlen($idhier) > 0) $idhier = substr($idhier, 0, strlen($idhier) - 1);
		if (strlen($pathhier) > 0) $pathhier = substr($pathhier, 0, strlen($pathhier) - 1);

		// if we actually did something, return the row.
		static $_cnt;
		$a = ($hier == $saved_row['hierarchy']);
		$b = ($idhier == $saved_row['id_hierarchy']);
		$c = ($pathhier == $saved_row['hierarchy_path']);
		if( !$a || !$b || !$c ) {
			$_cnt++;
			$saved_row['hierarchy'] = $hier;
			$saved_row['id_hierarchy'] = $idhier;
			$saved_row['hierarchy_path'] = $pathhier;
			return $saved_row;
		}
		return [];
	}

	/* *
	 * Update the hierarchy position of all content items.
	 * This is an expensive operation on the database, but must be called
	 * once each time one or more content pages are updated if positions
	 * have changed in the page structure.
	 * /
	public function SetAllHierarchyPositions()
	{
		// load some data about all pages into memory... and convert into a hash.
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT content_id, parent_id, item_order, content_alias AS alias, hierarchy, id_hierarchy, hierarchy_path FROM '.CMS_DB_PREFIX.'content ORDER BY hierarchy';
		$list = $db->GetArray($query);
		if( !$list ) {
			// nothing to do, get outa here.
			return;
		}
		$hash = array();
		foreach( $list as $row ) {
			$hash[$row['content_id']] = $row;
		}
		unset($list);

		// would be nice to use a transaction here.
		static $_n;
		$usql = "UPDATE ".CMS_DB_PREFIX."content SET hierarchy = ?, id_hierarchy = ?, hierarchy_path = ? WHERE content_id = ?";
		foreach( $hash as $content_id => $row ) {
			$changed = $this->_set_hierarchy_position($content_id,$hash);
			if( $changed ) {
				$db->Execute($usql, array($changed['hierarchy'], $changed['id_hierarchy'], $changed['hierarchy_path'], $changed['content_id']));
			}
		}
		$this->SetContentModified();
	}
*/
?>
