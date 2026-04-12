<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# CMSContentManager module class: ContentListBuilder
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
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl.2.0.html
#-------------------------------------------------------------------------
#END_LICENSE

namespace CMSContentManager;

use cms_config;
use cms_tree;
use CmsApp;
use CMSContentManager; //module class in global space
use CmsContentManagerUtils;
use CmsLayoutTemplate;
use CmsLockOperations;
use CMSMS\internal\global_cache;
use CmsSQLErrorException;
use ContentOperations;
use Exception;
use UserOperations;
use const CMS_CONTENT_HIDDEN_NAME;
use const CMS_DB_PREFIX;
use function audit;
use function check_authorship;
use function check_permission;
use function get_userid;

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
	private $_display_columns = [];
	private $_filter = null; // no object
	private $_locks;
	private $_module; // CMSContentManager object
	private $_offset = 0;
	private $_opened_pages = []; // ids of pages with displayed children
	private $_pagelength = 20; // arbitrary initial guess
	private $_pagelist; // ids of displayable pages
	private $_seek_to;
	private $_space; // $_SESSION key
	private $_userid; // current user
//	private $_use_perms = TRUE; // never changed c.f. ContentOperations::CreateHierarchyDropdown() argument

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
		$this->_space = 'cb'.hash('crc32',$this->_userid.__FILE__);
		if( isset($_SESSION[$this->_space]['opened_pages']) ) {
			$this->_opened_pages = $_SESSION[$this->_space]['opened_pages'];
		}
		else {
			$this->expand_all();
			$_SESSION[$this->_space]['opened_pages'] = $this->_opened_pages;
		}
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
	 * Results in the children of the specified page, and possibly other descendants,
	 * being visible in the content list.
	 *
	 * @param int $parent_page_id Page identifier
	 * @return bool indicating success
	 */
	public function expand_section($parent_page_id)
	{
		$parent_page_id = (int)$parent_page_id;
		if( $parent_page_id < 1 ) return FALSE;

		$tmp = [-1 => $parent_page_id] + $this->_opened_pages;
		$tmp = array_unique($tmp,SORT_NUMERIC);
		sort($tmp,SORT_NUMERIC);
		$this->_opened_pages = $tmp;
		$_SESSION[$this->_space]['opened_pages'] = $this->_opened_pages;
		return TRUE;
	}

	/**
	 * Mark all parent-pages as expanded.
	 * Results in all content pages being visible.
	 */
	public function expand_all()
	{
		// anonymous, recursive function
		$func = function($node) use(&$func) {
			$out = [];
			if( $node->has_children() ) {
				if( $node->get_tag('id') ) $out[] = $node->get_tag('id');
				$children = $node->get_children();
				for( $i = 0, $iMax = count($children); $i < $iMax; $i++ ) {
					$tmp = $func($children[$i]);
					if( $tmp && is_array($tmp) ) {
						$out = array_merge($out,$tmp);
					}
				}
				$out = array_unique($out,SORT_NUMERIC);
			}
			return $out;
		};

		// recursively find all the pages that have children.
		$hm = CmsApp::get_instance()->GetHierarchyManager();
		$this->_opened_pages = $func($hm);
		$_SESSION[$this->_space]['opened_pages'] = $this->_opened_pages;
	}

	/**
	 * Mark all parent-pages as collapsed.
	 * Results in no child-page being visible.
	 */
	public function collapse_all()
	{
		$this->_opened_pages = array();
		$_SESSION[$this->_space]['opened_pages'] = [];
	}

	/**
	 * Collapse the specified section of the list.
	 * Results in descendent pages of the specified page not being visible.
	 *
	 * @param int $parent_page_id Page identifier
	 * @return bool indicating success
	 */
	public function collapse_section($parent_page_id)
	{
		$parent_page_id = (int)$parent_page_id;
		if( $parent_page_id < 1 ) return FALSE;
		if($this->_opened_pages ) {
			$key = array_search($parent_page_id, $this->_opened_pages);
			if( $key !== FALSE ) {
				unset($this->_opened_pages[$key]);
				if( $this->_opened_pages ) {
					$this->_opened_pages = array_values($this->_opened_pages);
					$_SESSION[$this->_space]['opened_pages'] = $this->_opened_pages;
				}
				else {
					$_SESSION[$this->_space]['opened_pages'] = [];
				}
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * [Un]set the active-flag for the specified page
	 *
	 * @param int $page_id Page identifier (>0)
	 * @param bool $state Default true
	 * @return bool indicating success
	 */
	public function set_active($page_id,$state = TRUE)
	{
		$state = (bool)$state;
		$page_id = (int)$page_id;
		if( $page_id < 1 ) return FALSE;
		if( !check_permission($this->_userid,'Manage All Content') ) return FALSE;

		$contentops = ContentOperations::get_instance();
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
	 *            or null to remove any filtering.
	 */
	public function set_filter(/*?ContentListFilter */$filter = null) // uncomment for PHP 7.1+ .. 8.4+
	{
		$this->_filter = $filter;
	}

	/**
	 * Set the page limit/max-length/max-rows.
	 * This must be called before get_content_list() is called.
	 *
	 * @param int $n The page limit (min 1, max 500), typically 10|25
	 */
	public function set_pagelimit($n) // c.f. set_limit()
	{
		$this->_pagelength = max(1,min(500,(int)$n));
	}

	/**
	 * Get the page limit
	 *
	 * @return int
	 */
	public function get_pagelimit()
	{
		return $this->_pagelength;
	}

/* TODO new method to get displayed-rows-per-page, with minimal overhead/delay
 for tailoring pagelengths via $this->get_pagelengths()
	public function get_content_length()
	{
		return func($this->_pagelength);
	}
*/
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
	 * @param int Page identifier (>= 1)
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
		$this->_offset = $this->_pagelength * ($n-1);
	}

	/**
	 * Get the page corresponding to the current offset and page-length.
	 * This can be called after the content list is returned because
	 * the offset can be adjusted due to seeking to a content id.
	 *
	 * @return int
	 */
	public function get_page()
	{
		return (int)($this->_offset / $this->_pagelength) + 1;
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
		return (int)ceil((count($this->_pagelist) / $this->_pagelength) - 0.001);
	}

	/**
	 * Set the specified page as the default page, if possible
	 *
	 * @param int $page_id Page identifier (> 0)
	 * @return bool
	 */
	public function set_default($page_id)
	{
		$page_id = (int)$page_id;
		if( $page_id < 1 ) return FALSE;

		if( !check_permission($this->_userid,'Manage All Content') ) return FALSE;

		$contentops = ContentOperations::get_instance();
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
	 * Move the specified content page up or down relative to its peers.
	 *
	 * @param int $page_id Page identifier (>0)
	 * @param int $direction <0 (up) or >0 (down)
	 * @return bool indicating success
	 */
	public function move_content($page_id,$direction)
	{
		$page_id = (int)$page_id;
		if( $page_id < 1 ) return FALSE;
		$direction = (int)$direction;
		if( $direction == 0 ) return FALSE;
		$contentops = ContentOperations::get_instance();

		$test = FALSE;
		if( check_permission($this->_userid,'Manage All Content') ) {
			$test = TRUE;
		}
		elseif( check_permission($this->_userid,'Reorder Content') && $contentops->CheckPeerAuthorship($this->_userid,$page_id) ) {
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
	 * @return mixed string error message on failure | null on success
	 */
	public function delete_content($page_id)
	{
		$page_id = (int)$page_id;
		if( $page_id < 1 ) return $this->_module->Lang('error_invalidpageid');

		$test = FALSE;
		if( check_permission($this->_userid,'Manage All Content') ) {
			$test = TRUE;
		}
		elseif( check_permission($this->_userid,'Remove Pages') && check_authorship($this->_userid,$page_id) ) {
			$test = TRUE;
		}

		if( !$test ) return $this->_module->Lang('error_delete_permission');

		$contentops = ContentOperations::get_instance();
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
		$config = cms_config::get_instance();
		return ($config['url_rewriting'] != 'none');
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
		$cols = explode(',',$this->_module->GetPreference('list_visiblecolumns',$dflt));
		$userid = $this->_userid;

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
		$columnstodisplay['active'] = (in_array('active',$cols) && check_permission($userid,'Manage All Content')) ? 'icon' : '';
		$columnstodisplay['default'] = (in_array('default',$cols) && check_permission($userid,'Manage All Content')) ? 'icon' : '';
		$columnstodisplay['move'] = (in_array('move',$cols) && (check_permission($userid,'Manage All Content') || check_permission($userid,'Reorder Content'))) ? 'icon' : '';
		$columnstodisplay['view'] = in_array('view',$cols) ? 'icon' : '';
		$columnstodisplay['copy'] = (in_array('copy',$cols) && (check_permission($userid,'Add Pages') || check_permission($userid,'Manage All Content'))) ? 'icon' : '';
		$columnstodisplay['edit'] = in_array('edit',$cols) ? 'icon' : '';
		$columnstodisplay['delete'] = (in_array('delete',$cols) && (check_permission($userid,'Remove Pages') || check_permission($userid,'Manage All Content'))) ? 'icon' : '';
		$columnstodisplay['multiselect'] = (in_array('multiselect',$cols) && (check_permission($userid,'Remove Pages') || check_permission($userid,'Manage All Content'))) ? 'icon' : '';
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
	 * @return array
	 */
	private function _get_all_pages(cms_tree $node)
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
	 * Load all content for which the current user has modify-permission
	 * and which complies with current filter etc.
	 *
	 * @return array page numeric ids (as strings)
	 */
	private function _load_editable_content()
	{
		// get possibly-filtered editable-page ids
		$db = CmsApp::get_instance()->GetDb();

		if( $this->_opened_pages ) {
			$sql = 'SELECT C.content_id,C.id_hierarchy FROM '.CMS_DB_PREFIX.'content C';
			$where = [];
		}
		else {
			$sql = 'SELECT C.content_id FROM '.CMS_DB_PREFIX.'content C';
			$where = ['C.parent_id = -1'];
		}
		$parms = [];
		$modify_any_page = check_permission($this->_userid,'Manage All Content') ||
			check_permission($this->_userid,'Modify Any Page');
		if( !$modify_any_page ) {
			// akin to EXPR_OWNER or EXPR_EDITOR
			$sql .= ' JOIN '.CMS_DB_PREFIX.'additional_users A ON C.content_id = A.content_id';
			$where[] = '(C.owner_id = ? OR A.user_id = ?)';
			$parms[] = (int)$this->_userid;
			$parms[] = (int)$this->_userid;
		}
		if( $this->_filter ) {
			switch( $this->_filter->type ) {
			case ContentListFilter::EXPR_OWNER:
				if( $editorid != $this->_filter->expr ) {
					$where[] = 'C.owner_id = ?';
					$parms[] = (int) $this->_filter->expr;
				}
				break;
			case ContentListFilter::EXPR_EDITOR:
				if( $editorid != $this->_filter->expr ) {
					$sql .= ' JOIN '.CMS_DB_PREFIX.'additional_users AU ON C.content_id = AU.content_id';
					$where[] = 'AU.user_id = ?';
					$parms[] = (int) $this->_filter->expr;
				}
				break;
			case ContentListFilter::EXPR_TEMPLATE:
				$where[] = 'C.template_id = ?';
				$parms[] = (int) $this->_filter->expr;
				break;
			case ContentListFilter::EXPR_DESIGN:
				$sql .= ' JOIN '.CMS_DB_PREFIX.'content_props P ON C.content_id = P.content_id';
				$where[] = "P.prop_name = 'design_id'";
				$where[] = 'P.content = ?';
				$parms[] = (int) $this->_filter->expr;
				break;
			}
		}
		if( $where ) { $sql .= ' WHERE '.implode(' AND ',$where); }
		$sql .= ' ORDER BY C.hierarchy';

		$data = $db->GetArray($sql,$parms);
		if( $db->ErrorMsg() ) { throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg()); }
		if( $data ) {
			if( $this->_opened_pages ) {
				//reconcile $data with $this->_opened_pages
				foreach( $data as $key => $row ) {
					if( $row['id_hierarchy'] == $row['content_id'] ) {
						continue; // topmost pages always shown
					}
					//all ancestors must be open if this page is to shown
					//mebbe more effective if(array_diff(ancestorpages,$this->_opened_pages-to-strings)) omit
					$all = explode('.',$row['id_hierarchy']);
					for( $i = count($all) - 2; $i >= 0; --$i ) {
						if( !in_array((int)$all[$i],$this->_opened_pages)) {
							unset($data[$key]);
							continue 2;
						}
					}
				}
			}

			$this->_pagelist = array_column($data,'content_id');
			if( $this->_pagelist ) {
				if( $this->_seek_to > 0 ) {
					// re-calculate an offset
					$idx = array_search($this->_seek_to,$this->_pagelist);
					if( $idx === FALSE ) {
						// function adapted from https://www.geeksforgeeks.org/dsa/find-closest-number-array
						$findClosest = function($needle,$haystack) {
							$res = reset($haystack);
							$lo = 0;
							$hi = count($haystack) - 1;
							while( $lo <= $hi ) {
								$mid = $lo + (int)(($hi - $lo) / 2);
								if( abs($haystack[$mid] - $needle) < abs($res - $needle) ) {
									// update $res if $haystack[$mid] is closer to $needle
									$res = $haystack[$mid];
								}
								elseif( abs($haystack[$mid] - $needle) === abs($res - $needle)) {
									// if equi-distant, prefer larger value
									$res = max($res, $haystack[$mid]);
								}
								if( $haystack[$mid] == $needle ) {
									return $needle;
								}
								elseif( $haystack[$mid] < $needle ) {
									$lo = $mid + 1;
								}
								else {
									$hi = $mid - 1;
								}
							}
							return $res;
						};
						$idx = $findClosest($this->_seek_to,$this->_pagelist);
					}
					if( $idx > 0 ) {
						// item found
						$pagenum = (int)($idx / $this->_pagelength);
						$this->_offset = (int)($pagenum * $this->_pagelength);
					}
				}

				$offset = min(count($this->_pagelist),$this->_offset);
				$display = array_slice($this->_pagelist,$offset,$this->_pagelength);

				$parents = array_intersect($display,$this->_opened_pages);
				if( $parents ) {
					$contentops = ContentOperations::get_instance();
					$contentops->LoadChildren(-1,FALSE,TRUE,$parents);
				}
				return $display;
			}
		}
		return [];
	}

	/**
	 * Check whether the specified user has modify-permission for all
	 * peers of the specified content page
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
		return ContentOperations::get_instance()->CheckPeerAuthorship($userid,$content_id);
	}

	/**
	 * Check whether the specified user is the author of the specified
	 * content page
	 *
	 * @param int $content_id page identifier
	 * @param int $userid user identifier Default 0 (hence the recorded user)
	 *
	 * @return bool
	 */
	private function _check_authorship($content_id,$userid = 0)
	{
		if( $userid <= 0 ) $userid = $this->_userid;
		return ContentOperations::get_instance()->CheckPageAuthorship($userid,$content_id);
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
		$tmp = CmsLockOperations::get_locks('content');
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
	 * Locked if other-user-held lock is recorded, unlocked if no lock,
	 * or a $userid-held lock
	 * @ignore
	 * @param int $page_id Page identifier
	 * @param int $userid User identifier
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
	 * @param int $userid user identifier
	 * @return bool
	 */
	private function _is_default_locked($userid)
	{
		$locks = $this->get_locks();
		if( !$locks ) {
			return FALSE;
		}
		$dflt_content_id = ContentOperations::get_instance()->GetDefaultContent(); //value from cache
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
	 * @param int $page_id Page identifier
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
			$tmp = UserOperations::get_instance()->LoadUsers();
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
	 * @return array
	 */
	private function _get_display_data($page_list)
	{
		$contentops = ContentOperations::get_instance();
		$users = $this->_get_users();
		$userid = $this->_userid;
		$pmod = check_permission($userid,'Modify Any Page');
		$pman = check_permission($userid,'Manage All Content');
		$columns = $this->get_display_columns();

		// preload the templates
		$tpl_list = array();
		foreach( $page_list as $page_id ) {
			$node = $contentops->quickfind_node_by_id($page_id);
			if( !$node ) continue;
			$content = $node->GetContent(FALSE,FALSE,TRUE);
			if( !$content ) continue;
			$tpl_list[] = $content->TemplateId();
		}
		$tpl_list = array_values(array_unique(array_values($tpl_list)));
		$tpls = CmsLayoutTemplate::load_bulk($tpl_list);

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
			$rec['can_edit_tpl'] = check_permission($userid,'Modify Templates');
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
			$rec['can_delete'] = $rec['can_edit'] && check_permission($userid,'Remove Pages');

			foreach( $columns as $column => $displayable ) {
				switch( $column ) {
				case 'expand':
					$rec[$column] = 'none';
					if( $node->has_children() ) {
						if( in_array($page_id,$this->_opened_pages) ) {
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
						if( CmsContentManagerUtils::get_pagenav_display() == 'title' ) $rec[$column] = strip_tags($content->Name());
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
							$template = CmsLayoutTemplate::load($content->TemplateId());
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
						if( $rec['can_edit'] && ($pman || check_permission($userid,'Add Pages')) ) {
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
						elseif( $this->_check_authorship($page_id) && check_permission($userid,'Remove Pages') ) {
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
} // class
