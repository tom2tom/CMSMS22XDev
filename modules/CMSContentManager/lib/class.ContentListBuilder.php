<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# CMSContentManager module classes: ContentListBuilder, ContentListFilter, ContentListQuery
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#-------------------------------------------------------------------------
#END_LICENSE

namespace CMSContentManager;

use CMSContentManager; //module class in global space
use CMSMS\internal\global_cache;

/**
 * A simple class defining a content filter.
 * @final
 * @internal
 * @ignore
 * @package CMS
 */
final class ContentListFilter
{
	const EXPR_OWNER = 'OWNER_UID';
	const EXPR_EDITOR = 'EDITOR_UID';
	const EXPR_TEMPLATE = 'TEMPLATE_ID';
	const EXPR_DESIGN = 'DESIGN_ID';

	private $_type;
	private $_expr; // string

	#[\ReturnTypeWillChange]
	public function __get($key)
	{
		switch( $key ) {
		case 'type':
		case 'expr':
			$key = '_'.$key;
			return $this->$key;

		default:
			throw new \LogicException("$key is not a gettable member of ".__CLASS__);
		}
	}

	#[\ReturnTypeWillChange]
	public function __set($key,$val)
	{
		switch( $key ) {
		case 'type':
			switch( $val ) {
			case self::EXPR_OWNER:
			case self::EXPR_EDITOR:
			case self::EXPR_TEMPLATE:
			case self::EXPR_DESIGN:
				$this->_type = $val;
				break;
			default:
				throw new \LogicException("$val is an invalid type for ".__CLASS__);
			}
			break;

		case 'expr':
			$this->_expr = trim($val);
			break;

		default:
			throw new \LogicException("$key is not a settable member of ".__CLASS__);
		}
	}
} // end of class


/**
 * A class for querying the database about content items.
 * @final
 * @internal
 * @ignore
 * @package CMS
 */
final class ContentListQuery extends \CmsDbQueryBase
{
	protected $_filter;

	public function __construct(ContentListFilter $filter)
	{
		$this->_filter = $filter;
		$this->_limit = 500; // arbitrary initial guess
		$this->_offset = 0;
	}

	public function set_limit($limit)
	{
		$this->_limit = max(1,(int)$limit);
	}

	public function set_offset($offset)
	{
		$this->_offset = max(0,(int)$offset);
	}

	public function execute()
	{
		if( $this->_rs ) return;

		$sql = 'SELECT C.content_id FROM '.CMS_DB_PREFIX.'content C';
		$where = $parms = [];
		switch( $this->_filter->type ) {
		case ContentListFilter::EXPR_OWNER:
			$where[] = 'C.owner_id = ?';
			$parms[] = (int) $this->_filter->expr;
			break;
		case ContentListFilter::EXPR_EDITOR:
			$sql .= ' INNER JOIN '.CMS_DB_PREFIX.'additional_users A ON C.content_id = A.content_id AND A.user_id = ?';
			$parms[] = (int) $this->_filter->expr;
			break;
		case ContentListFilter::EXPR_TEMPLATE:
			$where[] = 'C.template_id = ?';
			$parms[] = (int) $this->_filter->expr;
			break;
		case ContentListFilter::EXPR_DESIGN:
			$sql .= ' INNER JOIN '.CMS_DB_PREFIX."content_props P ON C.content_id = P.content_id AND P.prop_name = 'design_id'";
			$where[] = 'P.content = ?';
			$parms[] = (int) $this->_filter->expr;
			break;
		}

		if( $where ) $sql .= ' WHERE '.implode(' AND ',$where);
		$sql .= ' ORDER BY C.id_hierarchy';

		$db = \cms_utils::get_db();
		$this->_rs = $db->SelectLimit($sql,$this->_limit,$this->_offset,$parms);
		if( $db->ErrorMsg() ) throw new \CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$this->_totalmatchingrows = $db->GetOne(str_replace('C.content_id','COUNT(*)',$sql));
	}

	public function GetObject()
	{
		$this->execute();
		if( !$this->_rs ) throw new \CmsLogicException('Cannot get pages from content query object');

		return (int)$this->_rs->fields['content_id'];
	}
}

/**
 * A class for building and managing content lists.
 * This class is not intended for use by non-core operations.
 * @final
 * @internal
 * @package CMS
 * @author Robert Campbell
 */
final class ContentListBuilder
{
	private $_opened_array = array();
	private $_module;
	private $_userid;
	private $_use_perms = TRUE;
	private $_filter = null; // no object
	private $_pagelimit = 500; // arbitrary initial guess
	private $_offset = 0;
	private $_pagelist;
	private $_seek_to;
	private $_locks;
	private $_display_columns = array();

	/**
	 * Constructor
	 * Caches the current user's id and pages (if any) opened by that user
	 *
	 * @param $mod CMSContentManager object
	 */
	public function __construct(CMSContentManager $mod)
	{
		$this->_module = $mod;
		$this->_userid = get_userid();
		$tmp = \cms_userprefs::get('opened_pages');
		if( $tmp ) $this->_opened_array = explode(',',$tmp);
	}

	/**
	 * [Un]set the displayed-flag for the specified column
	 *
	 * @param string $column 'expand' etc
	 * @param bool $state Default true
	 */
	public function column_state($column,$state = TRUE)
	{
		$this->_display_columns[$column] = $state;
	}

	/**
	 * Expand a specified section of the list.
	 * Results in the children of this page being visible in the content list.
	 *
	 * @param int $parent_page_id Page identifier
	 */
	public function expand_section($parent_page_id)
	{
		$parent_page_id = (int)$parent_page_id;
		if( $parent_page_id < 1 ) return;

		$tmp = $this->_opened_array;
		$tmp[] = $parent_page_id;
		asort($tmp);
		$this->_opened_array = array_unique($tmp);
		\cms_userprefs::set('opened_pages',implode(',',$this->_opened_array));
	}

	/**
	 * Mark all parent-pages as expanded.
	 * Results in all content pages being visible.
	 */
	public function expand_all()
	{
		$hm = \CmsApp::get_instance()->GetHierarchyManager();
		// find all the pages (recursively) that have children.

		// anonymous, recursive function.
		$func = function($node) use(&$func) {
			$out = [];
			if( $node->has_children() ) {
				if( $node->get_tag('id') ) $out[] = $node->get_tag('id');
				$children = $node->get_children();
				for( $i = 0, $iMax = count($children); $i < $iMax; $i++ ) {
					$tmp = $func($children[$i]);
					if( is_array($tmp) && count($tmp) ) $out = array_merge($out,$tmp);
				}
				$out = array_unique($out);
			}
			return $out;
		};

		$this->_opened_array = $func($hm);
		\cms_userprefs::set('opened_pages',implode(',',$this->_opened_array));
	}

	/**
	 * Mark all parent-pages as collapsed.
	 * Results in no child pages beng visible.
	 */
	public function collapse_all()
	{
		$this->_opened_array = array();
		\cms_userprefs::remove('opened_pages');
	}

	/**
	 * Collapse the specified section of the list.
	 * Results in its child pages not being visible.
	 *
	 * @param int $parent_page_id Page identifier
	 */
	public function collapse_section($parent_page_id)
	{
		$parent_page_id = (int)$parent_page_id;
		if( $parent_page_id < 1 ) return FALSE;

		$tmp = array();
		foreach( $this->_opened_array as $one ) {
			if( $one != $parent_page_id ) $tmp[] = $one;
		}
		asort($tmp);
		$this->_opened_array = array_unique($tmp);
		if( count($this->_opened_array) ) {
			\cms_userprefs::set('opened_pages',implode(',',$this->_opened_array));
		}
		else {
			\cms_userprefs::remove('opened_pages');
		}
		return TRUE;
	}

	/**
	 * [Un]set the active-flag for the specified page
	 *
	 * @param int $page_id Page identifier (>0)
	 * @param bool $state Default true
	 */
	public function set_active($page_id,$state = TRUE)
	{
		$state = (bool)$state;
		$page_id = (int)$page_id;
		if( $page_id < 1 ) return FALSE;
		if( !$this->_module->CheckPermission('Manage All Content') ) return FALSE;

		$contentops = \ContentOperations::get_instance();
		$node = $contentops->quickfind_node_by_id($page_id);
		if( !$node ) return FALSE;
		$content = $node->GetContent(FALSE,FALSE,FALSE);
		if( !$content ) return FALSE;

		$content->SetActive($state);
		$content->Save();
		return TRUE;
	}

	/**
	 * Set or clear the pages-list filter
	 *
	 * @param mixed ContentListFilter | null $filter an optional filter
	 *            or null to invalidate any filter.
	 */
	public function set_filter(/*?ContentListFilter */$filter = null) // uncomment for PHP 7.1+ .. 8.4+
	{
		$this->_filter = $filter;
	}

	/**
	 * Set the page limit/max-length/max-rows.
	 * This must be called before get_content_list() is called.
	 *
	 * @param int $n The page limit (min 1, max 500), typically 10|25|50|100
	 * @return void
	 */
	public function set_pagelimit($n)
	{
		$this->_pagelimit = max(1,min(500,(int)$n));
	}

	/**
	 * Get the page limit
	 *
	 * @return int
	 */
	public function get_pagelimit()
	{
		return $this->_pagelimit;
	}

	/**
	 * Get page limits/lengths tailored for the maximum no. of displayed rows.
	 * For use in a page-length selector.
	 * @since 2.2.23F2
	 *
	 * $param int $n Maximum number of displayable rows
	 *
	 * @return array each member like N => N, where N >= 10
	 */
	public function get_pagelengths($n)
	{
		$lengths = [10 => 10];
		if( $n > 10 ) { $lengths[25] = 25; }
		if( $n > 25 ) { $lengths[50] = 50; }
		if( $n > 50 ) { $lengths[100] = 100; }
		if( $n > 100 ) { $lengths[500] = 500; }
		return $lengths;
	}

	/**
	 * Set the page offset.
	 * This must be called before get_content_list() is called.
	 *
	 * @param int page offset (min 0, max is set by get_content_list())
	 */
	public function set_offset($n)
	{
		$this->_offset = max(0,(int)$n);
	}

	/**
	 * Get the page offset
	 *
	 * @return int
	 */
	public function get_offset()
	{
		return $this->_offset;
	}

	/**
	 * Set the seek-to property
	 *
	 * @param int seek to (>= 1)
	 */
	public function seek_to($n)
	{
		$this->_seek_to = max(1,(int)$n);
	}

	/**
	 * Set the offset corresponding to the specified page
	 *
	 * @param int page (>= 1)
	 */
	public function set_page($n)
	{
		$n = max(1,(int)$n);
		$this->_offset = $this->_pagelimit * ($n-1);
	}

	/**
	 * Get the page corresponding to the current offset and page-size.
	 * This can be called after the content list is returned because
	 * the offset can be adjusted due to seeking to a content id.
	 *
	 * @return int
	 */
	public function get_page()
	{
		return (int)($this->_offset / $this->_pagelimit) + 1;
	}

	/**
	 * Get the number of pages.
	 * This can only be called after get_content_list() has been called.
	 *
	 * @return int
	 */
	public function get_numpages()
	{
		if( !$this->_pagelist ) return 0;
		return (int)ceil((count($this->_pagelist) / $this->_pagelimit) - 0.001);
	}

	/**
	 * Set the specified page as the default page, if possible
	 *
	 * @param int $page_id Page identifier (> 0)
	 *
	 * @return bool
	 */
	public function set_default($page_id)
	{
		$page_id = (int)$page_id;
		if( $page_id < 1 ) return FALSE;

		if( !$this->_module->CheckPermission('Manage All Content') ) return FALSE;

		$contentops = \ContentOperations::get_instance();
		$content1 = $contentops->LoadContentFromId($page_id);

		if( !$content1 ) return FALSE;
		if( !$content1->IsDefaultPossible() ) return FALSE;

		$page_id2 = $contentops->GetDefaultContent();
		if( $page_id != $page_id2 ) {
			$content1->SetDefaultContent(TRUE);
			$content1->SetActive(TRUE); //ensure this one too
			$content1->Save();
			$content2 = $contentops->LoadContentFromId($page_id2);
			if( $content2 ) {
				$content2->SetDefaultContent(FALSE);
				$content2->Save();
			}
			global_cache::clear('default_content');
			audit($page_id,'Default page',"Changed to $page_id2: ".$contentops->GetPageDescriptor($page_id2));
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Move the specified content page up or down wrt its peers.
	 *
	 * @param int $page_id Page identifier (>0)
	 * @param int $direction <0 (up) or >0 (down)
	 *
	 * @return bool indicating success
	 */
	public function move_content($page_id,$direction)
	{
		$page_id = (int)$page_id;
		if( $page_id < 1 ) return FALSE;
		$direction = (int)$direction;
		if( $direction == 0 ) return FALSE;
		$contentops = \ContentOperations::get_instance();

		$test = FALSE;
		if( $this->_module->CheckPermission('Manage All Content') ) {
			$test = TRUE;
		}
		elseif( $this->_module->CheckPermission('Reorder Content') && $contentops->CheckPeerAuthorship($this->_userid,$page_id) ) {
			$test = TRUE;
		}

		if( !$test ) return FALSE;

		$content = $contentops->LoadContentFromId($page_id);
		if( !$content ) return FALSE;

		$tophier = $content->ParentHierarchy();
		$content->ChangeItemOrder($direction);
		$contentops->SetAllHierarchyPositions($tophier);
		return TRUE;
	}

	/**
	 * Delete the specified content page
	 *
	 * @param int $page_id Page identifier (>0)
	 *
	 * @return mixed string error message on failure | null on success
	 */
	public function delete_content($page_id)
	{
		$page_id = (int)$page_id;
		if( $page_id < 1 ) return $this->_module->Lang('error_invalidpageid');

		$test = FALSE;
		if( $this->_module->CheckPermission('Manage All Content') ) {
			$test = TRUE;
		}
		elseif( $this->_module->CheckPermission('Remove Pages') && check_authorship($this->_userid,$page_id) ) {
			$test = TRUE;
		}

		if( !$test ) return $this->_module->Lang('error_delete_permission');

		$contentops = \ContentOperations::get_instance();
		$node = $contentops->quickfind_node_by_id($page_id);
		if( !$node ) return $this->_module->Lang('error_invalidpageid');
		if( $node->has_children() ) return $this->_module->Lang('error_delete_haschildren');

		$content = $node->GetContent(FALSE,FALSE,FALSE);
		if( $content->DefaultContent() ) return $this->_module->Lang('error_delete_defaultcontent');

		$parent = $node->get_parent();
		$parent_id = $node->get_tag('id');
		$childcount = 0;
		if( $parent ) $childcount = $parent->count_children();

		$tmp = $content->Name();
		$type = $content->FriendlyName();
		if( $tmp ) {
			$tmp = "Deleted $type page: $tmp";
		}
		else {
			$tmp = "Deleted anonymous $type page";
		}
		$tophier = $content->ParentHierarchy();
		$content->Delete();
		audit($page_id,$this->_module->GetName(),$tmp);

		if( $childcount == 1 && $parent_id > -1 ) $this->collapse_section($parent_id);
		$this->collapse_section($page_id);

		$contentops->SetAllHierarchyPositions($tophier);
	}

	/**
	 * Check whether URL re-writing is configured for this site
	 *
	 * @return bool
	 */
	public function pretty_urls_configured()
	{
		$config = \cms_config::get_instance();
		return (!empty($config['url_rewriting']) && $config['url_rewriting'] != 'none');
	}

	/**
	 * Get the content list columns with their respective visibility-indicators.
	 *
	 * @return array Each member having a string column key as its key,
	 *  and a mixed value: string ('icon' or 'normal') indicating how the
	 *  column header is to be displayed, or empty/null indicating that
	 *  the column should not be displayed.
	 */
	public function get_display_columns()
	{
//		$config = \cms_config::get_instance();
		$dflt = 'expand,icon1,hier,page,alias,url,template,friendlyname,owner,active,default,move,view,copy,edit,delete,multiselect';
		$mod = $this->_module;
		$cols = explode(',',$mod->GetPreference('list_visiblecolumns',$dflt));

		$columnstodisplay = array();
		$columnstodisplay['expand'] = (!$this->_filter && in_array('expand',$cols)) ? 'icon' : '';
		$columnstodisplay['icon1'] = in_array('icon1',$cols) ? 'icon' : '';
		$columnstodisplay['hier'] = in_array('hier',$cols) ? 'normal' : '';
		$columnstodisplay['page'] = in_array('page',$cols) ? 'normal' : '';
		$columnstodisplay['alias'] = in_array('alias',$cols) ? 'normal' : '';
		$columnstodisplay['url'] = in_array('url',$cols) ? 'normal' : '';
		$columnstodisplay['template'] = in_array('template',$cols) ? 'normal' : '';
		$columnstodisplay['friendlyname'] = in_array('friendlyname',$cols) ? 'normal' : '';
		$columnstodisplay['owner'] = in_array('owner',$cols) ? 'normal' : '';
		$columnstodisplay['active'] = (in_array('active',$cols) && $mod->CheckPermission('Manage All Content')) ? 'icon' : '';
		$columnstodisplay['default'] = (in_array('default',$cols) && $mod->CheckPermission('Manage All Content')) ? 'icon' : '';
		$columnstodisplay['move'] = (in_array('move',$cols) && ($mod->CheckPermission('Manage All Content') || $mod->CheckPermission('Reorder Content'))) ? 'icon' : '';
		$columnstodisplay['view'] = in_array('view',$cols) ? 'icon' : '';
		$columnstodisplay['copy'] = (in_array('copy',$cols) && ($mod->CheckPermission('Add Pages') || $mod->CheckPermission('Manage All Content'))) ? 'icon' : '';
		$columnstodisplay['edit'] = in_array('edit',$cols) ? 'icon' : '';
		$columnstodisplay['delete'] = (in_array('delete',$cols) && ($mod->CheckPermission('Remove Pages') || $mod->CheckPermission('Manage All Content'))) ? 'icon' : '';
		$columnstodisplay['multiselect'] = (in_array('multiselect',$cols) && ($mod->CheckPermission('Remove Pages') || $mod->CheckPermission('Manage All Content'))) ? 'icon' : '';
//		$columnstodisplay['created'] = (in_array('created',$cols)) ? 'normal' : '';
//		$columnstodisplay['lastmodified'] = (in_array('lastmodified',$cols)) ? 'normal' : '';

		foreach( $columnstodisplay as $key => $val ) {
			if( isset($this->_display_columns[$key]) ) $columnstodisplay[$key] = $val && $this->_display_columns[$key];
		}

		return $columnstodisplay;
	}

	/**
	 * Recursive function to generate a list of all content pages.
	 *
	 * @param cms_tree $node
	 *
	 * @return array
	 */
	private function _get_all_pages(\cms_tree $node)
	{
		$out = array();
		if( $node->get_tag('id') ) $out[] = $node->get_tag('id');
		if( $node->has_children() ) {
			$children = $node->get_children();
			for( $i = 0, $iMax = count($children); $i < $iMax; $i++ ) {
				$child = $children[$i];
				$tmp = $this->_get_all_pages($child);
				if( is_array($tmp) && count($tmp) ) $out = array_merge($out,$tmp);
			}
		}
		return $out;
	}

	/**
	 * Load all content that the current user has access to.
	 *
	 * @return array page numeric ids (as strings)
	 */
	private function _load_editable_content()
	{
		// build a display list
		// 1.  add in top level items (items with parent == -1) which cannot be closed
		// 2.  for each item in opened array
		//       for each parent
		//         if not in opened array break
		//     if got to root, add item's children
		// 3.  reduce list by items we are able to view (author pages)

		$contentops = \ContentOperations::get_instance();
		$hm = \CmsApp::get_instance()->GetHierarchyManager();
		$display = [];

		// filter the display list by what we're authorized to view.
		$modify_any_page = $this->_module->CheckPermission('Manage All Content') || $this->_module->CheckPermission('Modify Any Page');
		if( $this->_filter && $modify_any_page ) {
			// we display only the pages matching the filter
			$query = new ContentListQuery($this->_filter);
			while( !$query->EOF() ) {
				$display[] = $query->GetObject(); // in this case, just an integer id ?
				$query->MoveNext();
			}
		}
		elseif( $this->_use_perms && $modify_any_page ) {
			// we can display anything

			$is_opened = function( $node, $opened_array ) {
				while( $node && $node->get_tag('id') > 0 ) {
					if( $node && $node->get_tag('id') > 0 ) {
						if( !in_array($node->get_tag('id'),$opened_array) ) return FALSE;
					}
					$node = $node->get_parent();
				}
				return TRUE;
			};

			// add in top level items.
			$children = $hm->get_children();
			if( $children ) {
				foreach( $children as $child ) {
					$display[] = $child->get_tag('id');
				}
			}

			// add children of opened_array items to the list.
			foreach( $this->_opened_array as $one ) {
				$node = $contentops->quickfind_node_by_id($one);
				if( !$node ) continue;

				if( ! $is_opened( $node, $this->_opened_array ) ) continue;
				$display[] = $one;

				$children = $node->get_children();
				if( $children && is_array($children) ) {
					foreach( $children as $child ) {
						$display[] = $child->get_tag('id');
					}
				}
			}
		}
		else {
			//
			// we can only edit some pages.
			//

			/*
			for each item
			if in opened array or has no parent add item
			if all parents are opened add item
			*/
			$tmplist = $contentops->GetPageAccessForUser($this->_userid);
			$display = array();
			foreach( $tmplist as $item ) {
				// get all the parents
				$parents = array();
				$startnode = $node = $contentops->quickfind_node_by_id($item);
				while( $node && $node->get_tag('id') > 0 ) {
					$parents[] = $node->get_tag('id');
					$node = $node->getParent();
				}
				// start at root
				// push items from list on the stack if they are root, or the previous item is in the opened array.
				$parents = array_reverse($parents);
				for( $i = 0, $iMax = count($parents); $i < $iMax; $i++ ) {
					if( $i == 0 ) {
						$display[] = $parents[$i];
						continue;
					}
					if( $i > 0 && in_array($parents[$i-1],$this->_opened_array) && in_array($parents[$i-1],$display) ) {
						$display[] = $parents[$i];
					}
				}
			}
		}

		// now order the page id list by hierarchy. and make sure they are unique.
		$display = array_unique($display);
		usort($display,function($a,$b) use ($hm,$contentops) {
				$node_a = $contentops->quickfind_node_by_id($a);
				$node_b = $contentops->quickfind_node_by_id($b);
				if( $node_a && $node_b ) {
					$hier_a = $node_a->getHierarchy();
					$hier_b = $node_b->getHierarchy();
					return strcmp($hier_a,$hier_b);
				}
				return 0;
			});

		$this->_pagelist = $display;

		if( $this->_seek_to > 0 ) {
			// re-calculate an offset
			$idx = array_search($this->_seek_to,$this->_pagelist);
			if( $idx > 0 ) {
				// item found.
				$pagenum = (int)($idx / $this->_pagelimit);
				$this->_offset = (int)($pagenum * $this->_pagelimit);
			}
		}

		$offset = min(count($this->_pagelist),$this->_offset);
		$display = array_slice($display,$offset,$this->_pagelimit);

		$contentops->LoadChildren(-1,FALSE,TRUE,$display);
		return $display;
	}

	/**
	 * Check whether the specified user has access to all peers of the specified content page
	 *
	 * @param int $content_id page identifier
	 * @param int $userid user identifier Default 0 (hence the recorded user)
	 * @return bool
	 *
	 */
	private function _check_peer_authorship($content_id,$userid = 0)
	{
		if( $content_id < 1 ) return FALSE;
		if( $userid <= 0 ) $userid = $this->_userid;
		return \ContentOperations::get_instance()->CheckPeerAuthorship($userid,$content_id);
	}

	/**
	 * Check if the specified user is the author of the specified content page
	 *
	 * @param int $content_id page identifier
	 * @param int $userid user identifier Default 0 (hence the recorded user)
	 *
	 * @return bool
	 */
	private function _check_authorship($content_id,$userid = 0)
	{
		if( $userid <= 0 ) $userid = $this->_userid;
		return \ContentOperations::get_instance()->CheckPageAuthorship($userid,$content_id);
	}

	/**
	 * Get current page-locks for all users
	 *
	 * @return array
	 */
	public function get_locks()
	{
//		if( $this->_module->GetPreference('locktimeout') < 1 ) return [];
		if( isset($this->_locks) ) return $this->_locks;
		$this->_locks = array();
		$tmp = \CmsLockOperations::get_locks('content');
		if( $tmp && is_array($tmp) ) {
			foreach( $tmp as $lock_obj ) {
			$this->_locks[$lock_obj['oid']] = $lock_obj;
			}
		}
		return $this->_locks;
	}

	/**
	 * Test for lock(s), optionally excluding any held by a specified user
	 *
	 * @param int $userid user identifier Default 0 (hence no exclusion)
	 * @return bool
	 */
	public function have_locks($userid = 0)
	{
		if( $userid == 0 ) {
			return count($this->get_locks()) > 0;
		}
		else {
			$tmp = $this->get_locks();
			foreach( $tmp as $lock ) {
				if( $lock['uid'] != $userid ) { return TRUE; }
			}
			return FALSE;
		}
	}

	/**
	 * Check whether the specified page is locked for the specified user.
	 * Locked if other-user-held lock is recorded, unlocked if no lock, or
	 * a $userid-held lock
	 * @ignore
	 *
	 * @return bool
	 */
	private function _is_locked($page_id,$userid)
	{
//		if( $this->_module->GetPreference('locktimeout') < 1 ) return FALSE;
		$locks = $this->get_locks();
		if( $locks ) {
			if( isset($locks[$page_id]) ) {
				$lock_obj = $locks[$page_id];
				if( $lock_obj['uid'] != $userid ) {
					return TRUE;
				}
				unset($locks[$page_id]);
			}
		}
		return FALSE;
	}

	/**
	 * Check whether the site default-page (if any) is locked by a user
	 * other than the one specified (or if that's 0, whether locked by any user)
	 * @ignore
	 *
	 * @return bool
	 */
	private function _is_default_locked($userid)
	{
		$locks = $this->get_locks();
		if( !$locks ) {
			return FALSE;
		}
		$dflt_content_id = \ContentOperations::get_instance()->GetDefaultContent(); //value from cache
		if( $dflt_content_id === 0 ) {
			return FALSE;
		}
		if( !isset($locks[$dflt_content_id]) ) {
			return FALSE;
		}
		return $locks[$dflt_content_id]['uid'] != $userid;
	}

	/**
	 * Check whether the lock (if any) on the specified page has expired
	 * @ignore
	 *
	 * @return bool
	 */
	private function _is_lock_expired($page_id)
	{
		$locks = $this->get_locks();
		if( !$locks ) return FALSE;
		if( isset($locks[$page_id]) ) {
			$lock = $locks[$page_id];
			if( $lock->expired() ) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Load and cache all users
	 *
	 * @return array
	 */
	private function _get_users()
	{
		static $_users = null; // aka unset
		if( !$_users ) {
			$tmp = \UserOperations::get_instance()->LoadUsers();
			if( $tmp && is_array($tmp) ) {
				$_users = array();
				for( $i = 0, $iMax = count($tmp); $i < $iMax; $i++ ) {
					$oneuser = $tmp[$i];
					$_users[$oneuser->id] = $oneuser;
				}
			}
		}
		return $_users;
	}

	/**
	 * Build display info for each specified page
	 *
	 * @param array $page_list Numeric identifiers of the wanted pages
	 *
	 * @return array
	 */
	private function _get_display_data($page_list)
	{
		$users = $this->_get_users();
		$contentops = \ContentOperations::get_instance();
		$mod = $this->_module;
		$pmod = $this->_module->CheckPermission('Modify Any Page');
		$pman = $this->_module->CheckPermission('Manage All Content');
		$columns = $this->get_display_columns();
		$userid = $this->_userid;

		// preload the templates.
		$tpl_list = array();
		foreach( $page_list as $page_id ) {
			$node = $contentops->quickfind_node_by_id($page_id);
			if( !$node ) continue;
			$content = $node->GetContent(FALSE,FALSE,TRUE);
			if( !$content ) continue;
			$tpl_list[] = $content->TemplateId();
		}
		$tpl_list = array_values(array_unique(array_values($tpl_list)));
		$tpls = \CmsLayoutTemplate::load_bulk($tpl_list);

		$out = array();
		foreach( $page_list as $page_id ) {
			$node = $contentops->quickfind_node_by_id($page_id);
			if( !$node ) continue;
			$content = $node->GetContent(FALSE,TRUE,TRUE);
			if( !$content ) continue;

			$rec = array();
			$rec['depth'] = $node->get_level();
			$rec['hasusablelink'] = $content->HasUsableLink();
			$rec['hastemplate'] = $content->HasTemplate();
			$rec['menutext'] = strip_tags($content->MenuText());
			$rec['title'] = strip_tags($content->Name());
			$rec['template_id'] = $content->TemplateId();
			$rec['can_edit_tpl'] = $mod->CheckPermission('Modify Templates');
			$rec['id'] = $page_id;
			$rec['lastmodified'] = $content->GetModifiedDate();
			$rec['created'] = $content->GetCreationDate();
			$rec['secure'] = $content->Secure();
			$rec['cachable'] = $content->Cachable();
			$rec['showinmenu'] = $content->ShowInMenu();
			$rec['wantschildren'] = $content->WantsChildren();
			$rec['viewable'] = $content->IsViewable();
			if( $this->_is_locked($page_id,$userid) ) {
				$rec['lock'] = $this->_locks[$page_id];
				$uid = $rec['lock']['uid'];
				$rec['lockuser'] = (isset($users[$uid])) ? $users[$uid]->username : ''; //UNUSED in template
			}
			if( $page_id == $this->_seek_to ) $rec['selected'] = 1;
			if( $content->LastModifiedBy() > 0 && isset($users[$content->LastModifiedBy()]) ) {
				$rec['lastmodifiedby'] = strip_tags($users[$content->LastModifiedBy()]->username);
			}
			$rec['can_edit'] = ($pmod || $pman ||
								$this->_check_authorship($rec['id'])) && !$this->_is_locked($page_id,$userid);
			$rec['can_steal'] = ($pmod || $pman ||
								$this->_check_authorship($rec['id'])) && $this->_is_locked($page_id,$userid) && $this->_is_lock_expired($page_id);
			$rec['can_delete'] = $rec['can_edit'] && $mod->CheckPermission('Remove Pages');

			foreach( $columns as $column => $displayable ) {
				switch( $column ) {
				case 'expand':
					$rec[$column] = 'none';
					if( $node->has_children() ) {
						if( in_array($page_id,$this->_opened_array) ) {
							$rec[$column] = 'open';
						} else {
							$rec[$column] = 'closed';
						}
					}
					break;

				case 'hier':
					$rec[$column] = $content->Hierarchy();
					break;

				case 'page':
					if( $content->MenuText() != CMS_CONTENT_HIDDEN_NAME ) {
						$rec[$column] = strip_tags($content->MenuText());
						if( \CmsContentManagerUtils::get_pagenav_display() == 'title' ) $rec[$column] = strip_tags($content->Name());
					}
					break;

				case 'alias':
					if( $content->HasUsableLink() && $content->Alias() != '' ) $rec[$column] = strip_tags($content->Alias());
					break;

				case 'url':
					$rec[$column] = '';
					if( $content->HasUsableLink() && $content->URL() != '' ) $rec[$column] = strip_tags($content->URL());
					break;

				case 'template':
					if( $content->IsViewable() ) {
						try {
							$template = \CmsLayoutTemplate::load($content->TemplateId());
							$rec[$column] = $template->get_name();
						}
						catch( Exception $e ) {
							// can't edit this content object, cuz we can't get the template associated with it.
							$rec['can_edit'] = FALSE;
						}
					}
					break;

				case 'friendlyname':
					$rec[$column] = $content->FriendlyName();
					break;

				case 'owner':
					if( $content->Owner() > 0 ) $rec[$column] = strip_tags($users[$content->Owner()]->username);
					break;

				case 'active':
					$rec[$column] = '';
					if( $pman && !($this->_is_locked($page_id,$userid) || $content->IsSystemPage()) ) {
						if( $content->Active() ) {
							$rec[$column] = 'active';
							if( $content->DefaultContent() ) { $rec[$column] = 'default'; }
						} else {
							$rec[$column] = 'inactive';
						}
					}
					break;

				case 'default':
					$rec['can_default'] = FALSE;
					$rec[$column] = '';
					if( $pman && !($this->_is_locked($page_id,$userid) || $this->_is_default_locked($userid)) ) {
						$rec['can_default'] = $content->IsDefaultPossible();
						$rec[$column] = ($content->DefaultContent()) ? 'yes' : 'no';
					}
					break;

				case 'move':
					$rec[$column] = '';
					if( !$this->_is_locked($page_id,$userid) && $this->_check_peer_authorship($page_id) && ($nsiblings = $node->count_siblings()) > 1 ) {
						if( $content->ItemOrder() == 1 ) {
							$rec[$column] = 'down';
						}
						elseif( $content->ItemOrder() == $nsiblings ) {
							$rec[$column] = 'up';
						}
						else {
							$rec[$column] = 'both';
						}
					}
					break;

				case 'view':
					$rec[$column] = '';
					if( $content->HasUsableLink() && $content->IsViewable() && $content->Active() ) $rec[$column] = $content->GetURL();
					break;

				case 'copy':
					$rec[$column] = '';
					if( $content->IsCopyable() && !$this->_is_locked($page_id,$userid) ) {
						if( $rec['can_edit'] && ($pman || $mod->CheckPermission('Add Pages')) ) {
							$rec[$column] = 'yes';
						}
					}
					break;

				case 'edit':
					$rec[$column] = '';
					if( $rec['can_edit'] ) {
						$rec[$column] = 'yes';
					}
					elseif( $rec['can_steal'] ) {
						$rec[$column] = 'steal';
					}
					break;

				case 'delete':
					$rec[$column] = '';
					if( $rec['can_delete'] && !($content->DefaultContent() || $node->has_children() || $this->_is_locked($page_id,$userid)) ) {
						$rec[$column] = 'yes';
					}
					break;

				case 'multiselect':
					$rec[$column] = '';
					if( !($content->IsSystemPage() || $this->_is_locked($page_id,$userid)) ) {
						if( $pman || $pmod ) {
							$rec[$column] = 'yes';
						}
						elseif( $this->_check_authorship($page_id) && $mod->CheckPermission('Remove Pages') ) {
							$rec[$column] = 'yes';
						}
						elseif( $this->_check_authorship($page_id) ) { // && WHAT? TODO
							$rec[$column] = 'yes';
						}
					}
					break;
				} // switch
			} // foreach

			$out[] = $rec;
		} // foreach

		return $out;
	}

	/**
	 * Master function
	 *
	 * @return array Display data for viewable/editable content, or empty
	 */
	public function get_content_list()
	{
		$pagelist = $this->_load_editable_content();
		if( is_array($pagelist) && count($pagelist) ) return $this->_get_display_data($pagelist);
		return [];
	}

/* TODO new method to get displayed-rows, with minimal overhead/delay
 for tailoring pagelengths via $this->get_pagelengths()
	public function get_content_length()
	{
		return 0;
	}
*/
	/**
	 * Check whether this content list supports multiselect
	 *
	 * @return bool
	 */
	public function supports_multiselect()
	{
		$cols = $this->get_display_columns();
		return ($cols && !empty($cols['multiselect']));
	}

} // end of class
