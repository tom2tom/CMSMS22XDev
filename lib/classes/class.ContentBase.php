<?php
# Class ContentBase
# (c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# BUT withOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$

use CMSMS\HookManager;
use CMSMS\internal\global_cache;

/**
 * This file provides the base abstract content class
 * @package CMS
 */

/**
 * @ignore
 */
define('CMS_CONTENT_HIDDEN_NAME','--------');
define('__CMS_PREVIEW_PAGE__',-100);

/**
 * Base level content object.
 *
 * This is the base content class. It is an abstract object and cannot be
 * instantiated directly.
 * All content pages are required to be derived from this class.
 *
 * @since		0.8
 * @package		CMS
 */
abstract class ContentBase
{
	/**
	 * @ignore
	 */
	const TAB_MAIN = 'aa_main_tab__';

	/**
	 * @ignore
	 */
	const TAB_NAV = 'zz_1nav_tab__';

	/**
	 * @ignore
	 */
	const TAB_LOGIC = 'zz_2logic_tab__';

	/**
	 * @ignore
	 */
	const TAB_OPTIONS = 'zz_3options_tab__';

	/**
	 * @ignore
	 */
	const TAB_PERMS = 'zz_4perms_tab__';

	/**
	 * The unique identifier of this content item
	 * Integer > -2.
	 * -1 for an item being cloned, 0 for an item being added, > 0 for
	 * a recorded item
	 *
	 * @ignore
	 */
	protected $mId = -1;

	/**
	 * The name of this content item
	 * String
	 *
	 * @internal
	 */
	protected $mName = '';

	/**
	 * The owner of this content item
	 * Integer > 0
	 *
	 * @internal
	 */
	protected $mOwner = -1;

	/**
	 * The ID of the parent object, -1 if none
	 * Integer
	 */
	protected $mParentId = -2;

	/**
	 * The old parent ID.
	 * Integer
	 * Formerly used only on update to detect re-parenting.
	 * Not needed now.
	 *
	 * @internal
	 * @deprecated since 2.2.x
	 */
	protected $mOldParentId = -1;

	/**
	 * The unique identifier of the template used for this content item
	 * Integer
	 *
	 * @internal
	 */
	protected $mTemplateId = -1;

	/**
	 * The item order of this content relative to its peers who have
	 * the same (non-)parent
	 * Integer
	 */
	protected $mItemOrder = -1;

	/**
	 * The former value of item order. Only used on update
	 * Integer
	 *
	 * @internal
	 */
	protected $mOldItemOrder = -1;

	/**
	 * The metadata (head tags) for this content
	 * String
	 *
	 * @internal
	 */
	protected $mMetadata = '';

	/**
	 * The title of this content item
	 * String
	 *
	 * @internal
	 */
	protected $mTitleAttribute = '';

	/**
	 * The (single) key which may be pressed to display this item
	 * String
	 *
	 * @internal
	 */
	protected $mAccessKey = '';

	/**
	 * Integer
	 *
	 * @internal
	 */
	protected $mTabIndex = 0;

	/**
	 * The full hierarchy of the content
	 * A '0'-padded string of the form: 00003.00007.00002
	 *
	 * @internal
	 */
	protected $mHierarchy = '';

	/**
	 * The full hierarchy of the content ids
	 * String of the form: 1.4.3
	 *
	 * @internal
	 */
	protected $mIdHierarchy = '';

	/**
	 * The full path through the hierarchy
	 * String of the form : grandparentalias/parentalias/childalias
	 *
	 * @internal
	 */
	protected $mHierarchyPath = '';

	/**
	 * What should be displayed in a menu
	 *
	 * @internal
	 */
	protected $mMenuText = '';

	/**
	 * Is the content active ?
	 * Bool
	 *
	 * @internal
	 */
	protected $mActive = false;

	/**
	 * Alias of this content item
	 * String
	 *
	 * @internal
	 */
	protected $mAlias = '';

	/**
	 * Old alias of this item
	 * String
	 *
	 * @internal
	 */
	protected $mOldAlias;

	/**
	 * Is this content cachable?
	 * Bool
	 *
	 * @internal
	 */
	protected $mCachable = false;

	/**
	 * Is this content always accessed via https, regardless of the
	 * site root url?
	 * Bool
	 *
	 * @internal
	 */
	protected $mSecure = false;

	/**
	 * The URL which may be accessed to display this content item
	 * String
	 *
	 * @internal
	 */
	protected $mURL = '';

	/**
	 * Should it show up in the menu?
	 * Bool
	 *
	 * @internal
	 */
	protected $mShowInMenu = false;

	/**
	 * Is this content item the site-default?
	 * Bool
	 *
	 * @internal
	 */
	protected $mDefaultContent = false;

	/**
	 * Last user to modify this content
	 * Integer
	 *
	 * @internal
	 */
	protected $mLastModifiedBy = -1;

	/**
	 * This item's creation date/time
	 * String
	 *
	 * @internal
	 */
	protected $mCreationDate = '';

	/**
	 * This item's latest modification date/time
	 * String
	 *
	 * @internal
	 */
	protected $mModifiedDate = '';

	/**
	 * Authorized additional editors of this item
	 * Array, when set. Maybe empty
	 *
	 * @internal
	 */
	protected $mAdditionalEditors;

	/**
	 * The extra properties of this content item
	 * Array, when set. Maybe empty
	 *
	 * @internal
	 */
	protected $_props;

	/**
	 * Item property definitions
	 * Array, when set
	 *
	 * @internal
	 */
	private $_attributes;

	/**
	 * Item-property default values
	 * Array, when set
	 *
	 * @internal
	 */
	private $_prop_defaults;

	/**
	 * $_attributes or calculated basic attributes, sorted
	 * Array, when set
	 *
	 * @internal
	 */
	private $_editable_properties;

	/************************************************************************/
	/* Constructor related													*/
	/************************************************************************/

	/**
	 * Constructor. Runs the SetInitialValues method, among other things.
	 */
	public function __construct()
	{
		$this->SetInitialValues();
		$this->SetProperties();
	}

	/**
	 * Sets object properties to sane initial values
	 *
	 * @abstract
	 * @internal
	 */
	protected function SetInitialValues()
	{
	}

	/**
	 * Subclasses should override this to set their properties using
	 * AddProperty(), RemoveProperty() etc
	 *
	 * @abstract
	 */
	protected function SetProperties()
	{
		$this->AddProperty('title',1,self::TAB_MAIN,true);

		$this->AddProperty('menutext',1,self::TAB_NAV,true);
		$this->AddProperty('showinmenu',1,self::TAB_NAV);
		$this->AddProperty('parent',2,self::TAB_NAV,true);
		$this->AddProperty('page_url',3,self::TAB_NAV);
		$this->AddProperty('target',3,self::TAB_NAV);
		$this->AddProperty('accesskey',6,self::TAB_NAV);
		$this->AddProperty('tabindex',7,self::TAB_NAV);

		$this->AddProperty('alias',1,self::TAB_OPTIONS);
		$this->AddProperty('titleattribute',1,self::TAB_OPTIONS);
		$this->AddProperty('active',2,self::TAB_OPTIONS);
		$this->AddProperty('cachable',4,self::TAB_OPTIONS);
		$this->AddProperty('secure',4,self::TAB_OPTIONS);
		$this->AddProperty('image',5,self::TAB_OPTIONS);
		$this->AddProperty('thumbnail',6,self::TAB_OPTIONS);
		$this->AddProperty('extra1',7,self::TAB_OPTIONS);
		$this->AddProperty('extra2',8,self::TAB_OPTIONS);
		$this->AddProperty('extra3',9,self::TAB_OPTIONS);

		$this->AddProperty('owner',1,self::TAB_PERMS);
		$this->AddProperty('additionaleditors',2,self::TAB_PERMS);
	}

	/************************************************************************/
	/* Functions giving access to needed elements of the content			*/
	/************************************************************************/

	/**
	 * @ignore
	 */
	public function __clone()
	{
		$this->mId = -1;
		$this->mItemOrder = -1;
		$this->mOldItemOrder = -1;
		$this->mURL = '';
		$this->mAlias = '';
	}

	/**
	 * Returns the ID
	 */
	public function Id()
	{
		return $this->mId;
	}

	/**
	 * Set the numeric id of the content item
	 *
	 * @param int Integer id
	 * @access private
	 * @internal
	 */
	public function SetId($id)
	{
		$this->mId = $id;
	}

	/**
	 * Returns a friendly name for this content type
	 *
	 * Normally the content type returns a string representing the name of the content type translated into the users current language
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function FriendlyName();

	/**
	 * Returns the Name
	 *
	 * @return string
	 */
	public function Name()
	{
		return $this->mName;
	}

	/**
	 * Set the the page name
	 *
	 * @param string $name The name.
	 */
	public function SetName($name)
	{
		$this->mName = $name;
	}

	/**
	 * Returns the Alias
	 *
	 * @return string
	 */
	public function Alias()
	{
		return $this->mAlias;
	}

	/**
	 * Returns the OldAlias
	 * The old alias is used when editing pages to detect changes in page alias
	 *
	 * @internal
	 * @deprecated
	 * @return string
	 */
	public function OldAlias()
	{
		return $this->mOldAlias;
	}

	/**
	 * Returns the Type
	 *
	 * @return string
	 */
	public function Type()
	{
		return strtolower(get_class($this));
	}

	/**
	 * Returns the Owners user id
	 *
	 * @return int
	 */
	public function Owner()
	{
		return $this->mOwner;
	}

	/**
	 * Set the page owner.
	 * No validation is performed.
	 *
	 * @param int $owner Owner's user id
	 */
	public function SetOwner($owner)
	{
		$owner = (int)$owner;
		if( $owner <= 0 ) return;
		$this->mOwner = $owner;
	}

	/**
	 * Returns the Metadata
	 *
	 * @return string
	 */
	public function Metadata()
	{
		return $this->mMetadata;
	}

	/**
	 * Content object handles the alias
	 *
	 * @abstract
	 * @return bool default is false.
	 */
	public function HandlesAlias()
	{
		return false;
	}

	/**
	 * Set the page metadata
	 *
	 * @param string $metadata The metadata
	 */
	public function SetMetadata($metadata)
	{
		$this->mMetadata = $metadata;
	}

	/**
	 * Return the page tabindex value
	 *
	 * @return int
	 */
	public function TabIndex()
	{
		return $this->mTabIndex;
	}

	/**
	 * Set the page tabindex value
	 *
	 * @param int $tabindex tabindex
	 */
	public function SetTabIndex($tabindex)
	{
		$this->mTabIndex = $tabindex;
	}

	/**
	 * Return the page title attribute
	 *
	 * @return string
	 */
	public function TitleAttribute()
	{
		return $this->mTitleAttribute;
	}

	/**
	 * Retrieve the creation date of this content object.
	 *
	 * @return int Unix Timestamp of the creation date
	 */
	public function GetCreationDate()
	{
		return strtotime($this->mCreationDate);
	}

	/**
	 * Retrieve the date of the last modification of this content object.
	 *
	 * @return int Unix Timestamp of the modification date.
	 */
	public function GetModifiedDate()
	{
		return strtotime($this->mModifiedDate);
	}

	/**
	 * Set the title attribute of the page
	 *
	 * The title attribute can be used in navigations to set the "title=" attribute of a link
	 * some menu templates may ignore this.
	 *
	 * @param string $titleattribute The title attribute
	 */
	public function SetTitleAttribute($titleattribute)
	{
		$this->mTitleAttribute = $titleattribute;
	}

	/**
	 * Get the access key (for accessibility) for this page.
	 *
	 * @see http://www.w3schools.com/tags/att_global_accesskey.asp
	 * @return string
	 */
	public function AccessKey()
	{
		return $this->mAccessKey;
	}

	/**
	 * Set the access key (for accessibility) for this page
	 *
	 * @see http://www.w3schools.com/tags/att_global_accesskey.asp
	 * @param string $accesskey
	 */
	public function SetAccessKey($accesskey)
	{
		$this->mAccessKey = $accesskey;
	}

	/**
	 * Returns the id of this pages parent.
	 * The parent id may be -2 to indicate a new page.
	 * A parent id value of -1 indicates that the page has no parent.
	 * otherwise a positive integer is returned.
	 *
	 * @return int
	 */
	public function ParentId()
	{
		return $this->mParentId;
	}

	/**
	 * Sets the parent of this page.
	 *
	 * @param int $parentid The numeric page parent id.  Use -1 for no parent.
	 */
	public function SetParentId($parentid)
	{
		$parentid = (int) $parentid;
		if( $parentid < 1 ) $parentid = -1;
		$this->mParentId = $parentid;
	}

	/**
	 * Return the id of the template associated with this content page.
	 *
	 * @return int.
	 */
	public function TemplateId()
	{
		return $this->mTemplateId;
	}

	/**
	 * Set the id of the template associated with this content page.
	 *
	 * @param int $templateid
	 */
	public function SetTemplateId($templateid)
	{
		$templateid = (int)$templateid;
		if( $templateid > 0 ) $this->mTemplateId = $templateid;
	}

	/**
	 * Returns the ItemOrder
	 * This property specifies the order of this page relative to its peers
	 * having the same parent.
	 * A value of -1 indicates that a new item order will be calculated on save.
	 * Otherwise a positive integer is expected.
	 *
	 * @return int
	 */
	public function ItemOrder()
	{
		return $this->mItemOrder;
	}

	/**
	 * Sets the ItemOrder
	 * @see also ItemOrder()
	 *
	 * @internal
	 * @param int $itemorder
	 */
	public function SetItemOrder($itemorder)
	{
		$itemorder = (int)$itemorder;
		if( $itemorder > 0 || $itemorder == -1 ) $this->mItemOrder = $itemorder;
	}

	/**
	 * Returns the ItemOrder. OR should it be OldItemOrder?
	 * This property records the item order before changes were made
	 * @see also ItemOrder()
	 *
	 * @internal
	 * @deprecated since 2.2.19#2
	 * @return int
	 */
	public function OldItemOrder()
	{
		return $this->mItemOrder;
	}

	/**
	 * Sets the OldItemOrder
	 * @see also ItemOrder(), OldItemOrder()
	 *
	 * @internal
	 * @deprecated since 2.2.19#2
	 * @param int the itemorder.
	 */
	public function SetOldItemOrder($itemorder)
	{
		$this->mOldItemOrder = (int)$itemorder;
	}

	/**
	 * Returns the user-friendly form of the Hierarchy of this page.
	 * A string like ##.##.## indicating the path to this page and its order
	 * e.g. 3.3.3 to indicate the third grandchild of the third child of
	 * the third topmost page.
	 *
	 * @return string
	 */
	public function Hierarchy()
	{
		$contentops = ContentOperations::get_instance();
		return $contentops->CreateFriendlyHierarchyPosition($this->mHierarchy);
	}

	/**
	 * Returns the Hierarchy of the parent of this page.
	 * A string like ##.##.## indicating the path to that page
	 * @since 2.2.19#2
	 *
	 * @return string, possibly empty
	 */
	public function ParentHierarchy()
	{
		return substr($this->mHierarchy, 0, -6);
	}

	/**
	 * Sets the Hierarchy of this page.
	 *
	 * @internal
	 * @param string $hierarchy either friendly- or unfriendly-format
	 */
	public function SetHierarchy($hierarchy)
	{
		$n = substr_count($hierarchy, '.');
		$l = $n * 6 + 5; //i.e. $n + ($n+1) * 5;
		if( strlen($hierarchy) < $l ) {
			$contentops = ContentOperations::get_instance();
			$hierarchy = $contentops->CreateUnfriendlyHierarchyPosition($hierarchy);
		}
		$this->mHierarchy = $hierarchy;
	}

	/**
	 * Returns the Id Hierarchy.
	 * A string like ##.##.## indicating the path to the page and its order
	 * This property uses the id's of pages when calculating the output
	 * e.g. 21.5.17 to indicate that page id 17 is the child of the page
	 * with id 5 which is in turn the child of the page with id 21
	 *
	 * @return string
	 */
	final public function IdHierarchy()
	{
		return $this->mIdHierarchy;
	}

	/**
	 * Returns the Hierarchy Path.
	 * Similar to the Hierarchy and IdHierarchy this string uses page aliases
	 * and outputs a string like root_alias/parent_alias/page_alias
	 *
	 * @return string
	 */
	final public function HierarchyPath()
	{
		return $this->mHierarchyPath;
	}

	/**
	 * Returns the Active state
	 *
	 * @return bool
	 */
	public function Active()
	{
		return $this->mActive;
	}

	/**
	 * Sets this page as active
	 *
	 * @param bool $active
	 */
	public function SetActive($active)
	{
		$this->mActive = (bool)$active;
	}

	/**
	 * Returns whether preview should be available for this content type
	 *
	 * @abstract
	 * @return bool
	 */
	public function HasPreview()
	{
		return false;
	}

	/**
	 * Returns whether this content item should (by default) be shown in navigation menus.
	 *
	 * @abstract
	 * @return bool
	 */
	public function ShowInMenu()
	{
		return $this->mShowInMenu;
	}

	/**
	 * Sets whether this page should be (by default) shown in menus
	 *
	 * @param bool $showinmenu
	 */
	public function SetShowInMenu($showinmenu)
	{
		$this->mShowInMenu = (bool) $showinmenu;
	}

	/**
	 * Returns whether this page is the default.
	 * The default page is the one that is displayed when no alias or pageid is specified in the route
	 * Only one content page can be the default.
	 *
	 * @return bool
	 */
	final public function DefaultContent()
	{
		if( $this->IsDefaultPossible() ) return $this->mDefaultContent;
		return false;
	}

	/**
	 * Sets whether this page should be considered the default.
	 * Note: does not modify the flags for any other content page.
	 *
	 * @param bool $defaultcontent
	 */
	public function SetDefaultContent($defaultcontent)
	{
		$this->mDefaultContent = (bool) $defaultcontent;
	}

	/**
	 * Return whether this page is cachable.
	 * Cachable pages (when enabled in global settings) are cached by the browser
	 * (also server side caching of HTML output may be enabled)
	 *
	 * @return bool
	 */
	public function Cachable()
	{
		return $this->mCachable;
	}

	/**
	 * Set whether this page is cachable
	 *
	 * @param bool $cachable
	 */
	public function SetCachable($cachable)
	{
		$this->mCachable = (bool) $cachable;
	}

	/**
	 * Return whether this page should be accessed via a secure protocol.
	 * The secure flag effects whether the ssl protocol and appropriate config entries are used when generating urls to this page.
	 *
	 * @return bool
	 */
	public function Secure()
	{
		return $this->mSecure;
	}

	/**
	 * Set whether this page should be accessed via a secure protocol.
	 * The secure flag affects whether the ssl protocol and appropriate config entries are used when generating urls to this page.
	 *
	 * @param bool $secure
	 */
	public function SetSecure($secure)
	{
		$this->mSecure = $secure;
	}

	/**
	 * Return the page url (if any) associated with this content page.
	 * The page url is not the complete URL to the content page, but merely the 'stub' or 'slug' appended after the root url when accessing the site
	 * If the page is specified as the default page then the "page url" will be ignored.
	 * Some content types do not support page urls.
	 *
	 * @return string
	 */
	public function URL()
	{
		return $this->mURL;
	}

	/**
	 * Set the page url (if any) associated with this content page.
	 * Note: some content types do not support page urls.
	 * The url should be relative to the root url.  i.e: /some/path/to/the/page
	 *
	 * @param string $url
	 */
	public function SetURL($url)
	{
		$this->mURL = $url;
	}

	/**
	 * Return the integer id of the admin user that last modified this content item.
	 *
	 * @return int
	 */
	public function LastModifiedBy()
	{
		return $this->mLastModifiedBy;
	}

	/**
	 * Set the last modified date for this item
	 *
	 * @param int $lastmodifiedby
	 */
	public function SetLastModifiedBy($lastmodifiedby)
	{
		$lastmodifiedby = (int)$lastmodifiedby;
		if( $lastmodifiedby > 0 ) $this->mLastModifiedBy = $lastmodifiedby;
	}

	/**
	 * Indicates whether this content type requires an alias.
	 * Some content types that are not directly navigable do not require page aliases.
	 *
	 * @abstract
	 * @return bool
	 */
	public function RequiresAlias()
	{
		return true;
	}

	/**
	 * Indicates whether this content type is viewable (i.e: can be rendered).
	 * some content types (like redirection links) are not viewable.
	 *
	 * @abstract
	 * @return bool Default is True
	 */
	public function IsViewable()
	{
		return true;
	}

	/**
	 * Indicates wether the current user is permitted to view this content page.
	 *
	 * @since 1.11.12
	 * @abstract
	 * @return boolean
	 */
	public function IsPermitted()
	{
		return true;
	}

	/**
	 * Indicates whether this content type is searchable.
	 *
	 * Searchable pages can be indexed by the search module.
	 *
	 * This function by default uses a combination of other abstract methods to determine if the page is searchable
	 * but extended content types can override this.
	 *
	 * @since 2.0
	 * @return bool
	 */
	public function IsSearchable()
	{
		if( !$this->isPermitted() || !$this->IsViewable() || !$this->HasTemplate() || $this->IsSystemPage() ) return false;
		return $this->HasSearchableContent();
	}

	/**
	 * Indicates whether this content type may have content that can be used by a search module.
	 *
	 * Content types should override this method if they are special purpose content types and they cannot support searchable content
	 * in any way.  Content types such as ErrorPage, Section Header, and Separator are examples.
	 *
	 * @since 2.0
	 * @abstract
	 * @return bool
	 */
	protected function HasSearchableContent()
	{
		return true;
	}

	/**
	 * Indicates whether this content type can be the default page for a CMSMS website.
	 *
	 * The content editor module may adjust it's user interface to not allow setting pages that return false for this method as the default page.
	 *
	 * @abstract
	 * @returns bool Default is false
	 */
	public function IsDefaultPossible()
	{
		return false;
	}

	/**
	 * Set the page alias for this content page.
	 * If an empty alias is supplied, and depending upon the doAutoAliasIfEnabled flag, and config entries
	 * a suitable alias may be calculated from other data in the page object
	 * This method relies on the menutext and the name of the content page already being set.
	 *
	 * @param string $alias The alias
	 * @param bool $doAutoAliasIfEnabled Whether an alias should be calculated or not.
	 */
	public function SetAlias($alias = '',$doAutoAliasIfEnabled = true)
	{
		$contentops = ContentOperations::get_instance();
		$config = cms_config::get_instance();
		if ($alias == '' && $doAutoAliasIfEnabled && $config['auto_alias_content'] == true) {
			// auto generate an alias
			$alias = trim($this->mMenuText);
			if ($alias == '') $alias = trim($this->mName);
			$alias = strip_tags($alias ); // since 2.2.18 = breaker?
			$alias = preg_replace('/&#?[a-z0-9]{2,8};/i','',$alias); // ditto
			$tolower = true;
			$alias = munge_string_to_url($alias, $tolower);
			$res = $contentops->CheckAliasValid($alias);
			if( !$res ) {
				$alias = 'p'.$alias;
				$res = $contentops->CheckAliasValid($alias);
				if( !$res ) throw new CmsContentException(lang('invalidalias2'));
			}
		}

		if( $alias ) {
			// Make sure auto-generated new alias is not already in use on a different page, if it does, add "-2" to the alias

			// make sure we start with a valid alias.
			$res = $contentops->CheckAliasValid($alias);
			if( !$res ) throw new CmsContentException(lang('invalidalias2'));

			// now auto-increment the alias.
			$prefix = $alias;
			$num = 1;
			if( preg_match('/(.*)-([0-9]*)$/',$alias,$matches) ) {
				$prefix = $matches[1];
				$num = (int) $matches[2];
			}
			$test = $alias;
			$testnum = 1;
			do {
				if( !$contentops->CheckAliasUsed($test,$this->Id()) ) {
					$alias = $test;
					break;
				}
				$num++;
				$test = $prefix.'-'.$num;
			} while( $testnum < 100 );
			if( $testnum >= 100 && $test != $alias ) throw new CmsContentException(lang('aliasalreadyused'));
		}

		$this->mAlias = $alias;
		global_cache::clear('content_quicklist');
		global_cache::clear('content_tree');
		global_cache::clear('content_flatlist');
	}

	/**
	 * Returns the menu text for this content page.
	 * The MenuText is by default used as the text portion of a navigation link.
	 *
	 * @return string
	 */
	public function MenuText()
	{
		return $this->mMenuText;
	}

	/**
	 * Sets the menu text for this content page
	 *
	 * @param string $menutext
	 */
	public function SetMenuText($menutext)
	{
		$this->mMenuText = $menutext;
	}

	/**
	 * Returns number of immediate child content items of this content item.
	 *
	 * @return int
	 */
	public function ChildCount()
	{
		$hm = CmsApp::get_instance()->GetHierarchyManager();
		$node = $hm->getNodeById($this->mId);
		if( $node ) return $node->count_children();
	}

	/**
	 * Content page objects only directly store enough information to
	 * build a basic navigation from content objects.
	 * This method will return all of the other properties of this content object.
	 *
	 * Note: this method does not itself load any properties. To do that,
	 * use e.g. $this->HasProperty('anythingnonfalsy') before calling this
	 * method.
	 *
	 * @return array
	 */
	public function Properties()
	{
		return $this->_props;
	}

	/**
	 * Test whether this content item has the named extra property.
	 * Properties will be loaded from the database if necessary.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function HasProperty($name)
	{
		if( !$name ) return false;
		if( !is_array($this->_props) ) $this->_load_properties();
		if( !$this->_props ) return false;
		return in_array($name,array_keys($this->_props));
	}

	/**
	 * Get the value for the named extra property.
	 * Properties will be loaded from the database if necessary.
	 *
	 * @param string $name
	 * @param mixed $default fallback value Default null since 2.2.19#2
	 * @return mixed String value, or $default if the property does not exist.
	 */
	public function GetPropertyValue($name,$default= null)
	{
		if( $this->HasProperty($name) ) return $this->_props[$name];
		return $default;
	}

	/**
	 * Populate this page's 'extra' properties (if any), if this page is
	 * not being added or cloned i.e. is already recorded in the database
	 * @ignore
	 * @return bool true always
	 */
	private function _load_properties()
	{
		if( $this->mId <= 0 ) return false; // unsaved new or cloned page

		$this->_props = [];
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.'content_props WHERE content_id = ?';
		$dbr = $db->GetArray($query,array((int)$this->mId));

		foreach( $dbr as $row ) {
			foreach( [
			'type',
			'prop_name',
			'param1',
			'param2',
			'param3',
			'content'
			] as $fld ) {
				if( $row[$fld] === null ) $row[$fld] = '';
			}
			$this->_props[$row['prop_name']] = $row['content'];
		}
		return true;
	}

	/**
	 * @ignore
	 */
	private function _save_properties()
	{
		if( $this->mId <= 0 ) return false; //unsaved new or cloned page
		if( !is_array($this->_props) || count($this->_props) == 0 ) return false;

		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT prop_name FROM '.CMS_DB_PREFIX.'content_props WHERE content_id = ?';
		$gotprops = $db->GetCol($query,array($this->mId));

		$now = $db->DbTimeStamp(time());
		$iquery = 'INSERT INTO '.CMS_DB_PREFIX."content_props
					(content_id,type,prop_name,content,modified_date)
					VALUES (?,?,?,?,$now)";
		$uquery = 'UPDATE '.CMS_DB_PREFIX."content_props SET content = ?, modified_date = $now WHERE content_id = ? AND prop_name = ?";

		foreach( $this->_props as $key => $value ) {
			if( in_array($key,$gotprops) ) {
				// update
				$dbr = $db->Execute($uquery,array($value,$this->mId,$key));
			}
			else {
				// insert
				$dbr = $db->Execute($iquery,array($this->mId,'string',$key,$value));
			}
		}
		return true;
	}

	/**
	 * Set the value of a the named property.
	 * This method will load properties for this content page if necessary.
	 *
	 * @param string $name The property name
	 * @param string $value The property value.
	 */
	public function SetPropertyValue($name,$value)
	{
		if( !is_array($this->_props) ) $this->_load_properties();
		$this->_props[$name] = $value;
	}

	/**
	 * Set the value of a the named property.
	 * This method will not load properties
	 *
	 * @param string $name The property name
	 * @param string $value The property value.
	 */
	public function SetPropertyValueNoLoad($name,$value)
	{
		if( !is_array($this->_props) ) $this->_props = [];
		$this->_props[$name] = $value;
	}

	/**
	 * An abstract method that extended content types can use to indicate whether or not they want children.
	 * Some content types, such as a separator do not want to have any children.
	 *
	 * @since 0.11
	 * @abstract
	 * @return bool Default true
	 */
	public function WantsChildren()
	{
		return true;
	}

	/**
	 * An abstract method that indicates that this content type is navigable and generates a useful URL.
	 *
	 * @abstract
	 * @return bool Default true
	 */
	public function HasUsableLink()
	{
		return true;
	}

	/**
	 * An abstract method indicating whether the content type is copyable.
	 *
	 * @abstract
	 * @return bool default false
	 */
	public function IsCopyable()
	{
		return false;
	}

	/**
	 * An abstract method to indicate whether this content type generates a system page.
	 * System pages are used to handle things like 404 errors etc.
	 *
	 * @abstract
	 * @return bool default false
	 */
	public function IsSystemPage()
	{
		return false;
	}

	/**
	 * Indicates whether this page type uses a template.
	 * Some content types like sectionheader and separator do not.
	 *
	 * @since 2.0
	 * @abstract
	 * @return bool default false
	 */
	public function HasTemplate()
	{
		return false;
	}

	/************************************************************************/
	/* The rest																*/
	/************************************************************************/

	/**
	 * Load the content of the object from an array.
	 * This method modifies the current object.
	 *
	 * There is no check on the data provided, because this is the job of
	 * ValidateData
	 *
	 * Upon failure the object comes back to initial values and returns false
	 *
	 * @param array $data Data as loaded from the database
	 * @param bool  $loadProperties Optionally load content properties at the same time.
	 * @return bool true always ATM
	 */
	function LoadFromData(&$data,$loadProperties = false)
	{
		foreach( [
			'content_name',
			'type', //cannot be usefully loaded
			'hierarchy',
			'menu_text',
			'content_alias',
			'id_hierarchy',
			'hierarchy_path',
			'prop_names',
			'metadata',
			'titleattribute',
			'tabindex',
			'accesskey',
			'page_url'
		] as $fld ) {
			if( !isset($data[$fld]) ) $data[$fld] = '';
		}

		$this->mId				= (int)$data["content_id"];
		$this->mName			= $data["content_name"];
		$this->mAlias			= $data["content_alias"];
		$this->mOldAlias		= $data["content_alias"];
		$this->mOwner			= (int)$data["owner_id"];
		$this->mParentId		= (int)$data["parent_id"];
//		$this->mOldParentId		= (int)$data["parent_id"];
		$this->mTemplateId		= (int)$data["template_id"];
		$this->mItemOrder		= (int)$data["item_order"];
		$this->mOldItemOrder	= (int)$data["item_order"]; //same as previous
		$this->mMetadata		= $data['metadata'];
		$this->mHierarchy		= $data["hierarchy"];
		$this->mIdHierarchy		= $data["id_hierarchy"];
		$this->mHierarchyPath	= $data["hierarchy_path"];
		$this->mMenuText		= $data['menu_text'];
		$this->mTitleAttribute	= $data['titleattribute'];
		$this->mAccessKey		= $data['accesskey'];
		$this->mTabIndex		= $data['tabindex'];
		$this->mDefaultContent	= ($data["default_content"] == 1);
		$this->mActive			= ($data["active"] == 1);
		$this->mShowInMenu		= ($data["show_in_menu"] == 1);
		$this->mCachable		= ($data["cachable"] == 1);
		$this->mSecure			= !empty($data['secure']);
		$this->mURL				= $data["page_url"];
		$this->mLastModifiedBy	= (int)$data["last_modified_by"];
		$this->mCreationDate	= $data["create_date"];
		$this->mModifiedDate	= $data["modified_date"];

		$result = true;
		if( $loadProperties ) {
			$this->_load_properties();
			if( !is_array($this->_props) ) { //TODO always array, maybe empty
				$result = false;
				$this->SetInitialValues(); //TODO bad logic
			}
		}

		$this->Load();
		return $result;
	}

	/**
	 * Convert the current object to an array.
	 *
	 * This can be considered a simple DTO (Data Transfer Object)
	 *
	 * @since 2.0
	 * @author Robert Campbell
	 * @return array
	 */
	public function ToData()
	{
		$out = [];
		$out['content_id'] = $this->mId;
		$out['content_name'] = $this->mName;
		$out['type'] = $this->Type();
		$out['content_alias'] = $this->mAlias;
		$out['owner_id'] = $this->mOwner;
		$out['parent_id'] = $this->mParentId;
		$out['template_id'] = $this->mTemplateId;
		$out['item_order'] = $this->mItemOrder;
		$out['metadata'] = $this->mMetadata;
		$out['hierarchy'] = $this->mHierarchy;
		$out['id_hierarchy'] = $this->mIdHierarchy;
		$out['hierarchy_path'] = $this->mHierarchyPath;
		$out['menu_text'] = $this->mMenuText;
		$out['titleattribute'] = $this->mTitleAttribute;
		$out['accesskey'] = $this->mAccessKey;
		$out['tabindex'] = $this->mTabIndex;
		$out['default_content'] = ($this->mDefaultContent && $this->mActive)?1:0;
		$out['active'] = ($this->mActive)?1:0;
		$out['show_in_menu'] = ($this->mShowInMenu)?1:0;
		$out['cachable'] = ($this->mCachable)?1:0;
		$out['secure'] = ($this->mSecure)?1:0;
		$out['page_url'] = $this->mURL;
		$out['last_modified_by'] = $this->mLastModifiedBy;
		$out['create_date'] = $this->mCreationDate;
		$out['modified_date'] = $this->mModifiedDate;
		$out['wants_children'] = ($this->WantsChildren())?1:0;
		$out['has_usable_link'] = ($this->HasUsableLink())?1:0;
		return $out;
	}

	/**
	 * Callback function for content types to use to preload content or other things if necessary.
	 * This is called right after the content is loaded from the database.
	 *
	 * @abstract
	 */
	protected function Load()
	{
	}

	/**
	 * Insert or update the content.
	 * This is generally used to change the value of one property (or one plus
	 * ancillaries) of an existing page. Or to add a new page.
	 *
	 * @todo return bool indicating success
	 */
	public function Save()
	{
		HookManager::do_hook('Core::ContentEditPre', [ 'content' => &$this ]);

		if( !is_array($this->_props) ) {
			debug_buffer('save is loading properties');
			$this->_load_properties();
		}

		if( $this->mId > 0 ) { // was >-1 : new-page mId = 0, cloned-page mId = -1
//			$result =
			$this->Update();
		}
		else {
//			$result =
			$this->Insert();
		}
		$result = true; //TODO per above

		if( $result ) {
			$tophier = $this->ParentHierarchy();
			$contentops = ContentOperations::get_instance();
			$contentops->SetContentModified();
			$contentops->SetAllHierarchyPositions($tophier);
			HookManager::do_hook('Core::ContentEditPost', [ 'content' => &$this ]);
		}
		return $result;
	}

	/**
	 * Update the database with the contents of the content object.
	 *
	 * This method will calculate a new item order for the object if necessary
	 * and then save the content record, the additional editors, and the properties.
	 * Additionally, if a page url is specified a static route will be created.
	 *
	 * Because multiple content objects may be modified in one batch, the
	 * calling function is responsible for ensuring that page hierarchies
	 * are updated.
	 *
	 * @todo return bool indicating success
	 */
	protected function Update()
	{
		$gCms = CmsApp::get_instance();
		$db = $gCms->GetDb();
//		$result = false;

		// Figure out the item_order (if necessary)
		if ($this->mItemOrder < 1) {
			$query = "SELECT ".$db->IfNull('MAX(item_order)','0')." as new_order FROM ".CMS_DB_PREFIX."content WHERE parent_id = ?";
			$row = $db->GetRow($query,array($this->mParentId));
			if ($row && $row['new_order'] > 0) {
				$this->mItemOrder = $row['new_order'] + 1;
			}
			else {
				$this->mItemOrder = 1;
			}
		}

		$query = "SELECT hierarchy,id_hierarchy,hierarchy_path FROM ".CMS_DB_PREFIX."content WHERE content_id = ?";
		$row = $db->GetRow($query,array($this->mParentId));
		if ($row) {
			$this->mHierarchy = $row['hierarchy'].'.'.str_pad($this->mItemOrder,5,'0',STR_PAD_LEFT); // OR in future, 3-wide would suffice
			$this->mIdHierarchy = $row['id_hierarchy'].'.'.$this->mId;
			$this->mHierarchyPath = $row['hierarchy_path'].'/'.$this->mAlias;
		}
		else {
			$this->mHierarchy = str_pad($this->mItemOrder,5,'0',STR_PAD_LEFT); // OR 3
			$this->mIdHierarchy = $this->mId;
			$this->mHierarchyPath = $this->mAlias;
		}

		if ($this->mDefaultContent) $this->mActive = true; // ensure this
		$this->mModifiedDate = trim($db->DBTimeStamp(time()), "'");

		$query = "UPDATE ".CMS_DB_PREFIX."content SET
content_name = ?,
content_alias = ?,
type = ?,
owner_id = ?,
template_id = ?,
parent_id = ?,
item_order = ?,
hierarchy = ?,
id_hierarchy = ?,
hierarchy_path = ?,
active = ?,
default_content = ?,
show_in_menu = ?,
cachable = ?,
secure = ?,
page_url = ?,
menu_text = ?,
metadata = ?,
titleattribute = ?,
accesskey = ?,
tabindex = ?,
modified_date = ?,
last_modified_by = ?
WHERE content_id = ?";
		$db->Execute($query, array(
			$this->mName,
			$this->mAlias,
			$this->Type(),
			$this->mOwner,
			$this->mTemplateId,
			$this->mParentId,
			$this->mItemOrder,
			$this->mHierarchy,
			$this->mIdHierarchy,
			$this->mHierarchyPath,
			($this->mActive ? 1 : 0),
			($this->mDefaultContent ? 1 : 0),
			($this->mShowInMenu ? 1 : 0),
			($this->mCachable ? 1 : 0),
			($this->mSecure ? 1 : 0),
			$this->mURL,
			$this->mMenuText,
			$this->mMetadata,
			$this->mTitleAttribute,
			$this->mAccessKey,
			$this->mTabIndex,
			$this->mModifiedDate,
			$this->mLastModifiedBy,
			(int) $this->mId
		));

//TODO	$result = $db->Affected_Rows() == 1 && $db->ErrorNo() == 0; reliable after UPDATE
/*		if (!$result) {
			die($db->sql.'<br>'.$db->ErrorMsg());
		}

		if ($this->mOldParentId != $this->mParentId) {
			// Fix the item_order if necessary
			$query = "UPDATE ".CMS_DB_PREFIX."content SET item_order = item_order - 1 WHERE parent_id = ? AND item_order > ?";
			$result = $db->Execute($query, array($this->mOldParentId,$this->mOldItemOrder));
			TODO AND corresponding effect on a segment of each hierarchy value?
			$this->mOldParentId = $this->mParentId;
			$this->mOldItemOrder = $this->mItemOrder;
		}
*/
		if (!empty($this->mAdditionalEditors)) {
			$query = "DELETE FROM ".CMS_DB_PREFIX."additional_users WHERE content_id = ?";
			$db->Execute($query, array($this->mId));

			foreach ($this->mAdditionalEditors as $oneeditor) {
				$new_addt_id = $db->GenID(CMS_DB_PREFIX."additional_users_seq");
				$query = "INSERT INTO ".CMS_DB_PREFIX."additional_users (additional_users_id, user_id, content_id) VALUES (?,?,?)";
				$db->Execute($query, array($new_addt_id, $oneeditor, $this->mId));
//TODO			$result = $result && $dbr != false;
			}
		}

		if( !empty($this->_props) ) {
//TODO		$result = $result &&
			$this->_save_properties();
		}

		cms_route_manager::del_static('','__CONTENT__',$this->mId);
		if( $this->mURL != '' ) {
			$route = CmsRoute::new_builder($this->mURL,'__CONTENT__',$this->mId,null,true);
//TODO		$result = $result &&
			cms_route_manager::add_static($route);
		}

		$pid = global_cache::get('default_content');
		if( $this->mDefaultContent ) {
			if( $pid != $this->mId ) {
				$query = 'UPDATE '.CMS_DB_PREFIX.'content SET default_content=0 WHERE content_id=?';
				$db->Execute($query, array($pid));
				global_cache::clear('default_content');
				audit($pid,'Default page','Changed to '.$this->mId.': '.$this->mName);
			}
		}
		else if( $pid == $this->mId ) {
			$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE content_id!=? AND default_content=0 ORDER BY hierarchy';
			$dbr = $db->GetOne($query, array($this->mId));
			if( $dbr > 0 ) {
				$query = 'UPDATE '.CMS_DB_PREFIX.'content SET default_content=0 WHERE content_id=?';
				$db->Execute($query, array($dbr));
				$contentops = ContentOperations::get_instance();
				$pn = $contentops->GetPageDescriptor($dbr);
				audit($pid,'Default page',"Interim change to $dbr: $pn");
			}
			else {
				audit($pid,'Default page','Removed, no replacement set');
			}
			global_cache::clear('default_content');
		}
//TODO	return $result;
	}

	/**
	 * Initially save a content object in the database.
	 *
	 * Like the Update method this method will determine an item order,
	 * save the record, save properties and additional editors.
	 * Since 2.2.19, the saved page will NOT become the default page
	 * if no other page is already the default.
	 */
	protected function Insert()
	{
		// TODO: This function should return something
		$gCms = CmsApp::get_instance();
		$db = $gCms->GetDb();

//		$result = false;
/* BAD!
		$query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE default_content = 1';
		$dflt_pageid = (int) $db->GetOne($query);
		if( $dflt_pageid < 1 ) $this->SetDefaultContent(true);
*/
		// Figure out the item_order if necessary
		if ($this->mItemOrder < 1) {
			$query = "SELECT ".$db->IfNull('MAX(item_order)','0')." as new_order FROM ".CMS_DB_PREFIX."content WHERE parent_id = ?";
			$row = $db->GetRow($query, array($this->mParentId));
			if ($row && $row['new_order'] > 0) {
				$this->mItemOrder = $row['new_order'] + 1;
			}
			else {
				$this->mItemOrder = 1;
			}
		}

		$newid = $db->GenID(CMS_DB_PREFIX."content_seq");
		$this->mId = $newid;

		$query = "SELECT hierarchy,id_hierarchy,hierarchy_path FROM ".CMS_DB_PREFIX."content WHERE content_id = ?";
		$row = $db->GetRow($query,array($this->mParentId));
		if ($row) {
			$this->mHierarchy = $row['hierarchy'].'.'.str_pad($this->mItemOrder,5,'0',STR_PAD_LEFT); // OR 3 would suffice
			$this->mIdHierarchy = $row['id_hierarchy'].'.'.$newid;
			$this->mHierarchyPath = $row['hierarchy_path'].'/'.$this->mAlias;
		}
		else {
			$this->mHierarchy = str_pad($this->mItemOrder,5,'0',STR_PAD_LEFT); // OR 3
			$this->mIdHierarchy = $newid;
			$this->mHierarchyPath = $this->mAlias;
		}

		if ($this->mDefaultContent) $this->mActive = true; // ensure this
		$this->mModifiedDate = $this->mCreationDate = trim($db->DBTimeStamp(time()), "'");

		$query = "INSERT INTO ".CMS_DB_PREFIX."content (
content_id,
content_name,
content_alias,
type,
owner_id,
parent_id,
template_id,
item_order,
hierarchy,
id_hierarchy,
hierarchy_path,
active,
default_content,
show_in_menu,
cachable,
secure,
page_url,
menu_text,
metadata,
titleattribute,
accesskey,
tabindex,
last_modified_by,
create_date,
modified_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		$result = $db->Execute($query, array(
				$newid,
				$this->mName,
				$this->mAlias,
				$this->Type(),
				$this->mOwner,
				$this->mParentId,
				$this->mTemplateId,
				$this->mItemOrder,
				$this->mHierarchy,
				$this->mIdHierarchy,
				$this->mHierarchyPath,
				($this->mActive ? 1 : 0),
				($this->mDefaultContent ? 1 : 0),
				($this->mShowInMenu ? 1 : 0),
				($this->mCachable ? 1 : 0),
				($this->mSecure ? 1 : 0),
				$this->mURL,
				$this->mMenuText,
				$this->mMetadata,
				$this->mTitleAttribute,
				$this->mAccessKey,
				$this->mTabIndex,
				$this->mLastModifiedBy,
				$this->mCreationDate,
				$this->mModifiedDate
		));

		if (!$result) {
			die($db->sql.'<br>'.$db->ErrorMsg()); //TODO throw
		}

		if (is_array($this->_props) && count($this->_props)) {
			// TODO perhaps some error checking there
			debug_buffer('save from ' . __LINE__);
			$this->_save_properties();
//TODO		$result = $result && ;
		}
		if (!empty($this->mAdditionalEditors)) {
			foreach ($this->mAdditionalEditors as $oneeditor) {
				$new_addt_id = $db->GenID(CMS_DB_PREFIX."additional_users_seq");
				$query = "INSERT INTO ".CMS_DB_PREFIX."additional_users (additional_users_id, user_id, content_id) VALUES (?,?,?)";
				$db->Execute($query, array($new_addt_id, $oneeditor, $newid));
//TODO			$result = $result && $dbr != false;
			}
		}

		if( $this->mURL ) {
			$route = CmsRoute::new_builder($this->mURL,'__CONTENT__',$newid,'',true);
			cms_route_manager::add_static($route);
//TODO		$result = $result && ;
		}

		if( $this->mDefaultContent ) {
			$pid = global_cache::get('default_content');
			if( $pid > 0 ) {
				$query = 'UPDATE '.CMS_DB_PREFIX.'content SET default_content=0 WHERE content_id=?';
				$db->Execute($query, array($pid));
			}
			global_cache::clear('default_content');
			audit($pid, 'Default page', "Changed to $newid: ".$this->mName);
		}
//TODO	return $result;
	}

	/**
	 * Test if the content object is valid.
	 * This function is used to check that no compulsory argument
	 * has been forgotten by the user
	 *
	 * We do not check the Id because there can be no Id (new content)
	 * That's up to Save to check this.
	 *
	 * @abstract
	 * @returns	array of error string(s), or empty
	 */
	public function ValidateData()
	{
		$errors = [];

		if ($this->mParentId < -1) {
			$errors[] = lang('invalidparent');
		}

		if ($this->mName == '') {
			if ($this->mMenuText != '') {
				$this->mName = $this->mMenuText;
			}
			else {
				$errors[]= lang('nofieldgiven',array(lang('title')));
			}
		}

		if ($this->mMenuText == '') {
			if ($this->mName != '') {
				$this->mMenuText = $this->mName;
			}
			else {
				$errors[]=lang('nofieldgiven',array(lang('menutext')));
			}
		}

		if (!$this->HandlesAlias()) {
			if ($this->mAlias != $this->mOldAlias || ($this->mAlias == '' && $this->RequiresAlias()) ) {
				$contentops = ContentOperations::get_instance();
				$error = $contentops->CheckAliasError($this->mAlias, $this->mId);
				if ($error) {
					$errors[]= $error;
				}
			}
		}

		$auto_type = content_assistant::auto_create_url();
		if( $this->mURL == '' && cms_siteprefs::get('content_autocreate_urls') ) {
			// create a valid url.
			if( !$this->DefaultContent() ) {
				if( cms_siteprefs::get('content_autocreate_flaturls',0) ) {
					// the default url is the alias... but not synced to the alias.
					$this->mURL = $this->mAlias;
				}
				else {
					// if it don't explicitly say 'flat' we're creating a hierarchical url.
					$gCms = CmsApp::get_instance();
					$tree = $gCms->GetHierarchyManager();
					$node = $tree->find_by_tag('id',$this->ParentId());
					$stack = array($this->mAlias);
					$parent_url = '';
					$count = 0;
					while( $node ) {
						$tmp_content = $node->GetContent();
						if( $tmp_content ) {
							$tmp = $tmp_content->URL();
							if( $tmp != '' && $count == 0 ) {
								// try to build the url out of the parent url.
								$parent_url = $tmp;
								break;
							}
							array_unshift($stack,$tmp_content->Alias());
						}
						$node = $node->GetParent();
						$count++;
					}

					$this->mURL = implode('/',$stack);
					if( $parent_url ) {
						// woot, we got a prent url.
						$this->mURL = $parent_url.'/'.$this->mAlias;
					}
				}
			}
		}
		if( $this->mURL == '' && cms_siteprefs::get('content_mandatory_urls') && !$this->mDefaultContent &&
			$this->HasUsableLink() ) {
			// page url is empty and mandatory
			$errors[] = lang('content_mandatory_urls');
		}
		else if( $this->mURL != '' ) {
			// page url is not empty, check for validity.
			$this->mURL = strtolower(trim($this->mURL," /\t\r\n\0\x08")); // silently delete bad chars. and convert to lowercase.
			if( $this->mURL != '' && !content_assistant::is_valid_url($this->mURL,$this->mId) ) {
				// and validate the URL.
				$errors[] = lang('invalid_url2');
			}
		}

		return $errors;
	}

	/**
	 * Delete the current content object from the database.
	 *
	 * @todo this function should return something, or throw an exception
	 */
	function Delete()
	{
		HookManager::do_hook('Core::ContentDeletePre', [ 'content' => &$this ]);
		$gCms = CmsApp::get_instance();
		$db = $gCms->GetDb();

		if( $this->mId > 0 ) {
			$query = "DELETE FROM ".CMS_DB_PREFIX."content WHERE content_id = ?";
			$result = $db->Execute($query, array($this->mId));

			// Adjust item_orders as necessary
			$query = "UPDATE ".CMS_DB_PREFIX."content SET item_order = item_order - 1 WHERE parent_id = ? AND item_order > ?";
			$result = $db->Execute($query,array($this->mParentId,$this->mItemOrder));

			// Delete properties
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'content_props WHERE content_id = ?';
			$result = $db->Execute($query,array($this->mId));
			unset($this->_props);

			// Delete additional editors
			$query = 'DELETE FROM '.CMS_DB_PREFIX.'additional_users WHERE content_id = ?';
			$result = $db->Execute($query,array($this->mId));
			unset($this->mAdditionalEditors);

			// Delete route
			if( $this->mURL ) cms_route_manager::del_static($this->mURL);
		}

		HookManager::do_hook('Core::ContentDeletePost', [ 'content' => &$this ]);
		$this->mId = -1;
		$this->mItemOrder = -1;
		$this->mOldItemOrder = -1;
	}

	/**
	 * Function for a subclass to parse out data for its parameters.
	 * This method is typically called from an editor form to allow modifying
	 * this content object from form input fields (usually $_POST)
	 *
	 * @param array $params The input array (usually from $_POST)
	 * @param bool  $editing Indicates wether this is an edit or add operation.
	 * @abstract
	 */
	public function FillParams($params,$editing = false)
	{
		// content property parameters
		$parameters = array('extra1','extra2','extra3','image','thumbnail');
		foreach ($parameters as $oneparam) {
			if (isset($params[$oneparam])) $this->SetPropertyValue($oneparam, $params[$oneparam]);
		}

		// go through the list of base parameters
		// setting them from params

		// title
		if (isset($params['title'])) $this->mName = strip_tags($params['title']);

		// menu text
		if (isset($params['menutext'])) $this->mMenuText = strip_tags(trim($params['menutext']));

		// parent id
		if( isset($params['parent_id']) ) {
			if( $params['parent_id'] == -2 && !$editing ) $params['parent_id'] = -1;
			if ($this->mParentId != $params['parent_id']) {
				$this->mHierarchy = '';
				$this->mItemOrder = -1;
			}
			$this->mParentId = (int) $params['parent_id'];
		}

		// active
		if (isset($params['active'])) {
			$this->mActive = (int) $params['active'];
			if( $this->DefaultContent() ) $this->mActive = 1;
		}

		// show in menu
		if (isset($params['showinmenu'])) $this->mShowInMenu = (int) $params['showinmenu'];

		// alias
		// alias field can exist if the user has manage all content... OR alias is a basic property
		// and this user has other edit rights to the content page.
		// empty value on the alias field means we need to generate a new alias
		$new_alias = '';
		$alias_field_exists = isset( $params['alias'] );
		if( $alias_field_exists ) {
			$new_alias = trim(strip_tags($params['alias'])); //TODO also scrub entities
		}
		// if we are adding or we have a new alias, set alias to the field value, or calculate one, adjust as needed
		if( !$editing || $new_alias || $alias_field_exists ) {
			$this->SetAlias($new_alias);
		}

		// target
		if (isset($params['target'])) {
			$val = strip_tags($params['target']);
			if( $val == '---' ) $val = '';
			$this->SetPropertyValue('target', $val);
		}

		// title attribute
		if (isset($params['titleattribute'])) $this->mTitleAttribute = trim(strip_tags($params['titleattribute']));

		// accesskey
		if (isset($params['accesskey'])) $this->mAccessKey = strip_tags($params['accesskey']);

		// tab index
		if (isset($params['tabindex'])) $this->mTabIndex = (empty($params['tabindex'])) ? '' : (int) $params['tabindex'];

		// cachable
		if (isset($params['cachable'])) {
			$this->mCachable = (int) $params['cachable'];
		}
		else {
			$this->_handleRemovedBaseProperty('cachable','mCachable');
		}

		// secure
		if (isset($params['secure'])) {
			$this->mSecure = (int) $params['secure'];
		}
		else {
			$this->_handleRemovedBaseProperty('secure','mSecure');
		}

		// url
		if (isset($params['page_url'])) {
			$this->mURL = trim(strip_tags($params['page_url']));
		}
		else {
			$this->_handleRemovedBaseProperty('page_url','mURL');
		}

		// owner
		if (isset($params["ownerid"])) $this->SetOwner((int) $params["ownerid"]);

		// additional editors
		if (isset($params["additional_editors"])) {
			$addtarray = [];
			if( is_array($params['additional_editors']) ) {
				foreach ($params["additional_editors"] as $addt_user_id) {
					$addtarray[] = (int) $addt_user_id;
				}
			}
			$this->SetAdditionalEditors($addtarray);
		}
	}

	/**
	 * A function to get the internally generated URL for this content type.
	 * This method may be overridden by content types.
	 *
	 * @param bool $rewrite if true, and mod_rewrite is enabled, build a URL suitable for mod_rewrite.
	 * @return string
	 */
	public function GetURL($rewrite = true)
	{
		$config = cms_config::get_instance();
		$url = "";
		$alias = $this->mAlias ?: $this->mId;

		$base_url = CMS_ROOT_URL;
		if( $this->Secure() ) $base_url = $config['ssl_url'];

		/* use root_url for default content */
		if($this->DefaultContent()) {
			$url =  $base_url . '/';
			return $url;
		}

		if( $rewrite == true ) {
			$url_rewriting = $config['url_rewriting'];
			$page_extension = $config['page_extension'];
			if ($url_rewriting == 'mod_rewrite') {
				$str = $this->mURL ?: // we have a url path
					$this->mHierarchyPath;
				$url = $base_url . '/' . $str . $page_extension;
				return $url;
			}
			elseif (isset($_SERVER['PHP_SELF']) && $url_rewriting == 'internal') {
				$str = $this->mURL ?: // we have a url path
					$this->mHierarchyPath;
				$url = $base_url . '/index.php/' . $str . $page_extension;
				return $url;
			}
		}

		$url = $base_url . '/index.php?' . $config['query_var'] . '=' . $alias;
		return $url;
	}

	/**
	 * Move this content up or down relative to its peers.
	 * Note: this method modifies two content objects.
	 *
	 * @since 2.0
	 * @param int $direction negative value indicates up, positive value indicates down.
	 */
	public function ChangeItemOrder($direction)
	{
		$order = $this->mItemOrder;
		if( ($order < 2 && $direction < 0) || $direction == 0 ) { return; }

		$gCms = CmsApp::get_instance();
		$db = $gCms->GetDb();
		$time = $db->DBTimeStamp(time());
		$parentid = $this->mParentId;

		if( $direction < 0 ) {
			// up
			$query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = (item_order + 1), modified_date = '.$time.' WHERE item_order = ? AND parent_id = ?';
			$db->Execute($query,array($order-1,$parentid));
			$query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = (item_order - 1), modified_date = '.$time.' WHERE content_id = ?';
			$db->Execute($query,array($this->mId));
		}
		else {
			// down
			$query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = (item_order - 1), modified_date = '.$time.' WHERE item_order = ? AND parent_id = ?';
			$db->Execute($query,array($order+1,$parentid));
			$query = 'UPDATE '.CMS_DB_PREFIX.'content SET item_order = (item_order + 1), modified_date = '.$time.' WHERE content_id = ?';
			$db->Execute($query,array($this->mId));
		}
		// TODO these also cleared during upstream SetAllHierarchyPositions()
//		global_cache::clear('content_quicklist');
//		global_cache::clear('content_tree');
//		global_cache::clear('content_flatlist');
	}

	/**
	 * Return the raw value for a content property.
	 * If no property name is specified 'content_en' is assumed
	 *
	 * @abstract
	 * @param string $propname An optional property name to display.  If none specified, the system should assume content_en.
	 * @return string
	 */
	public function Show($propname = 'content_en')
	{
	}

	/**
	 * Return a list of properties of this object that may be edited by
	 * the current user i.e. all the properties or just the 'basic' ones.
	 * Content types may override this method, but must call this
	 * base method at the start of their GetEditableProperties().
	 *
	 * @return array stdClass objects each containing members
	 *  'name' (string), 'tab' (string), 'priority' (integer), 'required' (bool)
	 */
	public function GetEditableProperties()
	{
		if( !check_permission(get_userid(),'Manage All Content') ) {
			$basic_attributes = array('title','parent');
			$tmp_basic_attributes = cms_siteprefs::get('basic_attributes');
			if( $tmp_basic_attributes ) {
				$tmp_basic_attributes = explode(',',$tmp_basic_attributes);
				$basic_attributes = array_merge($tmp_basic_attributes,$basic_attributes);
			}

			$out = [];
			foreach( $this->_attributes as $one ) {
				// TODO also omit basic elements that the user is not allowed to process, if there is such a setting.
				if( $one->basic || in_array($one->name,$basic_attributes) ) $out[] = $one;
			}
			return $out;
		}
		return $this->_attributes;
	}

	/**
	 * Sort the supplied property definitions by tabname, priority, property-name
	 * @ignore
	 *
	 * @param mixed $props populated array or falsy value
	 * @return Array, maybe empty
	 */
	private function _SortProperties($props)
	{
		if( $props ) {
			usort($props,function($a,$b) {
				$atab = ( !empty($a->tab) ) ? $a->tab : ContentBase::TAB_MAIN;
				$btab = ( !empty($b->tab) ) ? $b->tab : ContentBase::TAB_MAIN;

				if( ($r = strcmp($atab,$btab)) != 0 ) { return $r; }
				if( ($r = $a->priority - $b->priority) != 0 ) { return $r; }
				return strcmp($a->name,$b->name);
			});
			return $props;
		}
		return [];
	}

	/**
	 * @ignore
	 * @return Array, maybe empty
	 */
	private function _GetEditableProperties()
	{
		if( isset($this->_editable_properties) ) return $this->_editable_properties;

		$arr = $this->GetEditableProperties();
		if( $arr ) {
			$props = $this->_SortProperties($arr);
		}
		else {
			$props = [];
		}
		$this->_editable_properties = $props;
		return $props;
	}

	/**
	 * Used from a page that allows content editing.
	 * This method provides a list of distinct sections that divides up the
	 * various logical sections that this content type supports for editing.
	 *
	 * @abstract
	 * @return Array associative array list of tab keys and labels.
	 */
	public function GetTabNames()
	{
		$props = $this->_GetEditableProperties();
		$arr = [];
		foreach( $props as $one ) {
			if( !isset($one->tab) || $one->tab == '' ) $one->tab = self::TAB_MAIN;
			$key = $lbl = $one->tab;
			if( endswith($key,'_tab__') ) $lbl = lang($key);
			$arr[$key] = $lbl;
		}
		return $arr;
	}

	/**
	 * Get an optional message for each tab.
	 *
	 * @abstract
	 * @since 2.0
	 * @param string $key the tab key (as returned with GetTabNames)
	 * @return string html text to display at the top of the tab, or empty.
	 */
	public function GetTabMessage($key)
	{
		switch( $key ) {
		case self::TAB_PERMS:
			return '<div class="information">'.lang('msg_permstab').'</div>';
		default:
			return '';
		}
	}

	/**
	 * Get the elements for a specific tab.
	 *
	 * @param string $key tab key
	 * @param bool   $adding  Whether this is an add or edit operation.
	 * @return array An array of arrays.  Index 0 of each element should be a prompt field, and index 1 should be the input field for the prompt.
	 */
	public function GetTabElements($key,$adding = false)
	{
		$props = $this->_GetEditableProperties();
		$out = [];
		foreach( $props as $one ) {
			if( !isset($one->tab) || $one->tab == '' ) $one->tab = self::TAB_MAIN;
			if( $key != $one->tab ) continue;
			$out[] = $this->display_single_element($one->name,$adding);
		}
		return $out;
	}

	/**
	 * Method to indicate whether the current page has children.
	 *
	 * @param bool $activeonly Should we test only for active children.
	 * @return bool
	 */
	public function HasChildren($activeonly = false)
	{
		$node = ContentOperations::get_instance()->quickfind_node_by_id($this->mId);
		if( !$node->has_children() ) return false;
		if( $activeonly == false) return true;

		$children = $node->get_children();
		if( $children ) {
			for( $i = 0, $n = count($children); $i < $n; $i++ ) {
				$content = $children[$i]->getContent();
				if( $content->Active() ) return true;
			}
		}

		return false;
	}

	/**
	 * Return a list of additional editors.
	 * Note: in the returned array, group id's are specified as negative integers.
	 *
	 * @return array user ids and/or group ids, or possibly empty
	 */
	public function GetAdditionalEditors()
	{
		if (!isset($this->mAdditionalEditors)) {
			$db = CmsApp::get_instance()->GetDb();
			$this->mAdditionalEditors = [];

			$query = "SELECT user_id FROM ".CMS_DB_PREFIX."additional_users WHERE content_id = ?";
			$dbresult = $db->Execute($query,array($this->mId));

			while ($dbresult && !$dbresult->EOF) {
				$this->mAdditionalEditors[] = $dbresult->fields['user_id'];
				$dbresult->MoveNext();
			}

			if ($dbresult) $dbresult->Close();
		}
		return $this->mAdditionalEditors;
	}

	/**
	 * Set the list of additional editors.
	 * Note: in the provided array, group id's are specified as negative integers.
	 *
	 * @param mixed $editorarray array of user ids and/or group ids, or empty, or null
	 */
	public function SetAdditionalEditors($editorarray)
	{
		if( is_array($editorarray) ) {
			$this->mAdditionalEditors = $editorarray;
		}
		else {
			unset($this->mAdditionalEditors);
		}
	}

	/**
	 * A utility method to return all of the user ids and group ids in a format
	 * suitable for use in a select element.
	 * Note: group ids are expressed as negative integers in the keys.
	 * @return array
	 */
	public static function GetAdditionalEditorOptions()
	{
		$opts = [];
		$userops = UserOperations::get_instance();
		$groupops = GroupOperations::get_instance();
		$allusers = $userops->LoadUsers();
		$allgroups = $groupops->LoadGroups();
		foreach ($allusers as $oneuser) {
			$opts[$oneuser->id] = $oneuser->username;
		}
		foreach ($allgroups as $onegroup) {
			if( $onegroup->id == 1 ) continue; // exclude admin group (they have all privileges anyways)
			$val = $onegroup->id*-1;
			$opts[$val] = lang('group').': '.$onegroup->name;
		}

		return $opts;
	}

	/**
	 * A utility method to generate a <select> field for selecting additional editors.
	 * If a positive owner id is specified that user will be excluded from output select element.
	 *
	 * @see ContentBase::GetAdditionalEditorOptions
	 * @param array $addteditors Array of additional editors
	 * @param int  $owner_id  The current owner of the page.
	 * @return array 2 members, a HTML label+help popup and a select element
	 */
	public static function GetAdditionalEditorInput($addteditors,$owner_id = -1)
	{
		$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_addteditor',lang('help_title_content_addteditor'));
		$ret[] = '<label for="addteditors">'.lang('additionaleditors').':</label>'.$help;
		$text = '<input name="additional_editors" type="hidden" value="">';
		$text .= '<select id="addteditors" name="additional_editors[]" multiple size="5">';

		$topts = self::GetAdditionalEditorOptions();
		foreach( $topts as $k => $v ) {
			if( $k == $owner_id ) continue;
			$text .= CmsFormUtils::create_option(array('label'=>$v,'value'=>$k),$addteditors);
		}

		$text .= '</select>';
		$ret[] = $text;
		return $ret;
	}

	/**
	 * Provides an input element to display the list of additional editors,
	 * plus a corresponding label.
	 * This method is usually called from within this object.
	 *
	 * @param array $addteditors An optional array of additional editor id's (group ids specified with negative values)
	 * @return array 2 members, a HTML label and select element
	 * @see ContentBase::GetAdditionalEditorInput
	 */
	public function ShowAdditionalEditors($addteditors = [])
	{
		if( !$addteditors ) $addteditors = $this->GetAdditionalEditors();
		return self::GetAdditionalEditorInput($addteditors,$this->Owner());
	}

	/**
	 * Tries to set the value of a base (not 'extra') property of this content
	 * object when that property has been removed from the form.
	 * @ignore
	 *
	 * @param string $name The property-definition name
	 * @param string $member The corresponding class-property name
	 * @return bool
	 */
	private function _handleRemovedBaseProperty($name,$member)
	{
		if( empty($this->_attributes) ) return false;
		$fnd = false;
		foreach( $this->_attributes as $attr ) {
			if( $attr->name == $name ) {
				$fnd = true; // i.e. return false;
				break;
			}
		}
		if( !$fnd ) {
			if( isset($this->_prop_defaults[$name]) ) {
				$this->$member = $this->_prop_defaults[$name];
				return true;
			}
		}
		return false;
	}

	/**
	 * Remove a named property-definition from this object's property-definitions.
	 * Specify a default value to use if that property is called for.
	 *
	 * @param string $name The property name
	 * @param string $dflt The default value.
	 */
	protected function RemoveProperty($name,$dflt)
	{
		if( empty($this->_attributes) ) return;
		$tmp = [];
		for( $i = 0, $n = count($this->_attributes); $i < $n; $i++ ) {
			if( is_object($this->_attributes[$i]) && $this->_attributes[$i]->name == $name ) continue;
			$tmp[] = $this->_attributes[$i];
		}
		$this->_attributes = $tmp;
		$this->_prop_defaults[$name] = $dflt;
	}

	/**
	 * Add a property definition.
	 *
	 * @since 1.11
	 * @param string $name The property name
	 * @param int $priority The property priority, for sorting.
	 * @param string $tab The tab for the property (see tab constants)
	 * @param bool $required (whether the property is required)
	 * @param bool $basic Whether or not the property is a basic property (editable by even restricted editors)
	 */
	protected function AddProperty($name,$priority,$tab = self::TAB_MAIN,$required = false,$basic = false)
	{
		$ob = new stdClass();
		$ob->name = (string) $name;
		$ob->priority = (int) $priority;
		$ob->tab = (string) $tab;
		$ob->required = (bool) $required;
		$ob->basic = $basic;

		if( !isset($this->_attributes) ) $this->_attributes = [];
		$this->_attributes[] = $ob;
	}

	/**
	 * Get all properties of this content object, regardless whether the user is entitled to view them.
	 *
	 * @since 2.0
	 * @return array of stdClass objects, or empty
	 */
	public function GetProperties()
	{
		if( !empty($this->_attributes) ) {
			return $this->_SortProperties($this->_attributes);
		}
		return [];
	}

	/**
	 * Add a property that is directly associated with a field in the content table.
	 * Always populates the options tab.
	 *
	 * @param string $name The property name
	 * @param int    $priority The order of the property's UI elements in the editor form tab
	 * @param bool   $is_required Whether a value must be recorded for the property in the editor form. Default false
	 * @deprecated since 2.0
	 */
	protected function AddBaseProperty($name,$priority,$is_required = false)
	{
		$this->AddProperty($name,$priority,self::TAB_OPTIONS,$is_required);
	}

	/**
	 * Alias for AddBaseProperty.
	 * Always populates the options tab.
	 *
	 * @param string $name
	 * @param int    $priority
	 * @param bool   $is_required
	 * @deprecated since 2.0
	 */
	protected function AddContentProperty($name,$priority,$is_required = false)
	{
		$this->AddProperty($name,$priority,self::TAB_OPTIONS,$is_required);
	}

	/**
	 * Generate input elements for displaying/editing a property of this object.
	 *
	 * @abstract
	 * @param string $name The property name
	 * @param bool $adding Whether or not we are in add-content mode (otherwise, edit).
	 * @return array consisting of two elements (normally label and input) or empty.
	 */
	protected function display_single_element($name,$adding)
	{
		$config = cms_config::get_instance();

		switch( $name ) {
		case 'cachable':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_cachable',lang('help_title_content_cachable'));
			return array('<label for="in_cachable">'.lang('cachable').':</label>'.$help,
						 '<input type="hidden" name="cachable" value="0"><input id="in_cachable" class="pagecheckbox" type="checkbox" value="1" name="cachable"'.($this->mCachable?' checked':'').'>');

		case 'title':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_title',lang('help_title_content_title'));
			return array('<label for="in_title">*'.lang('title').':</label>'.$help,
						 '<input type="text" id="in_title" name="title" required value="'.cms_htmlentities($this->mName).'">');

		case 'menutext':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_menutext',lang('help_title_content_menutext'));
			return array('<label for="in_menutext">*'.lang('menutext').':</label>'.$help,
						 '<input type="text" name="menutext" id="in_menutext" value="'.cms_htmlentities($this->mMenuText).'">');

		case 'parent':
			$contentops = ContentOperations::get_instance();
			$tmp = $contentops->CreateHierarchyDropdown($this->mId, $this->mParentId, 'parent_id', ($this->mId > 0) ? 0 : 1, 1, 0, 1, 1);
			if( $tmp ) {
				$help = cms_admin_utils::get_help_tag('core','help_content_parent',lang('help_title_content_parent'));
				return array('<label for="parent_id">*'.lang('parent').':</label>&nbsp;'.$help,$tmp);
			}
			if( !check_permission(get_userid(),'Manage All Content') ) {
				return array('','<input type="hidden" name="parent_id" value="'.$this->mParentId.'">');
			}
			break;

		case 'default': //since 2.2.19#2
			$help = cms_admin_utils::get_help_tag('core','help_content_default',lang('help_title_content_default'));
			if( !$this->mDefaultContent ) {
				$contentops = ContentOperations::get_instance();
				$pid = $contentops->GetDefaultContent();
				if( $pid > 0 ) {
					$pn = $contentops->GetPageDescriptor($pid);
					$xmsg = '<br>'.lang('info_default_page',$pn,$pid);
				}
				else {
					$xmsg = '<br>'.lang('info_default_none');
				}
			}
			else {
				$xmsg = '';
			}
			return array('<label for="id_default">'.lang('default').':</label>&nbsp;'.$help,'<input type="hidden" name="default" value="0"><input class="pagecheckbox" type="checkbox" name="default" id="id_default" value="1"'.($this->mDefaultContent?' checked':'').'>'.$xmsg);
			break;

		case 'active':
			if( !$this->DefaultContent() ) {
				$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_active',lang('help_title_content_active'));
				return array('<label for="id_active">'.lang('active').':</label>'.$help,'<input type="hidden" name="active" value="0"><input class="pagecheckbox" type="checkbox" name="active" id="id_active" value="1"'.($this->mActive?' checked':'').'>');
			}
			break;

		case 'showinmenu':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_showinmenu',lang('help_title_content_showinmenu'));
			return array('<label for="showinmenu">'.lang('showinmenu').':</label>'.$help,
						 '<input type="hidden" name="showinmenu" value="0"><input class="pagecheckbox" type="checkbox" value="1" name="showinmenu" id="showinmenu"'.($this->mShowInMenu?' checked':'').'>');

		case 'target':
			$text = '<option value="---">'.lang('none').'</option>';
			$text .= '<option value="_blank"'.($this->GetPropertyValue('target')=='_blank'?' selected="selected"':'').'>_blank</option>';
			$text .= '<option value="_parent"'.($this->GetPropertyValue('target')=='_parent'?' selected="selected"':'').'>_parent</option>';
			$text .= '<option value="_self"'.($this->GetPropertyValue('target')=='_self'?' selected="selected"':'').'>_self</option>';
			$text .= '<option value="_top"'.($this->GetPropertyValue('target')=='_top'?' selected="selected"':'').'>_top</option>';
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_target',lang('help_title_content_target'));
			return array('<label for="target">'.lang('target').':</label>'.$help,
						 '<select name="target" id="target">'.$text.'</select>');

		case 'alias':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_page_alias',lang('help_title_page_alias'));
			return array('<label for="alias">'.lang('pagealias').':</label>'.$help,
						 '<input type="text" name="alias" id="alias" value="'.$this->mAlias.'">');

		case 'secure':
			$opt = '';
			if( $this->mSecure ) $opt = ' checked';
			$str  = '<input type="hidden" name="secure" value="0">';
			$str .= '<input type="checkbox" name="secure" id="secure" value="1"'.$opt.'>';
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_secure',lang('help_title_content_secure'));
			return array('<label for="secure">'.lang('secure_page').':</label>'.$help,$str);

		case 'page_url':
			if( !$this->DefaultContent() ) {
				$must = ( cms_siteprefs::get('content_mandatory_urls',0) ) ? '*' : '';
				$prompt = "<label for=\"page_url\">$must".lang('page_url');
				if( $config['url_rewriting'] == 'none' ) {
					$prompt .= ' ('.lang('nouse').')';
				}
				$prompt .= ':</label>&nbsp;';
				$help = cms_admin_utils::get_help_tag('core','help_page_url',lang('help_title_page_url'));
				$str = '<input type="text" name="page_url" id="page_url" value="'.$this->mURL.'" size="50" maxlength="255">';
				return array($prompt.$help,$str);
			}
			else {
				return array('<label for="page_url" disabled>'.lang('page_url').':</label>',
							'<input type="text" id="page_url" value="" size="50" disabled>');
			}
			break;

		case 'image':
			$dir = $config['image_uploads_path'];
			if( ($tmp = cms_siteprefs::get('content_imagefield_path')) ) { $dir .= DIRECTORY_SEPARATOR . trim($tmp, ' \\/'); }
			$data = $this->GetPropertyValue('image');
			$filepicker = cms_utils::get_filepicker_module();
			if( $filepicker ) {
				$profile = $filepicker->get_default_profile( $dir, get_userid() );
				$profile = $profile->overrideWith( ['top'=>$dir, 'type'=>'image'] );
				$input = $filepicker->get_html( 'image', $data, $profile);
			}
			else {
				$input = create_file_dropdown('image',$dir,$data,'jpg,jpeg,png,gif','',true,'','thumb_',0,1); //c,f, FileTypeHelper 'jpg','jpeg','bmp','wbmp','gif','png','webp', 'svg'
			}
			if( !$input ) return [];
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_image',lang('help_title_content_image'));
			return array('<label for="image">'.lang('image').':</label>'.$help,$input);
/*
			$dir = $config['image_uploads_path']; if appropriate, .= DIRECTORY_SEPARATOR. trim cms_siteprefs::get('content_imagefield_path'));
			$data = $this->GetPropertyValue('image');
			$dropdown = create_file_dropdown('image',$dir,$data,'jpg,jpeg,png,gif','',true,'','thumb_',1,1);
			if( !$dropdown ) return [];
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_image',lang('help_title_content_image'));
			return array('<label for="image">'.lang('image').':</label>'.$help,$dropdown);
*/

		case 'thumbnail':
			$dir = $config['image_uploads_path'];
			if( ($tmp = cms_siteprefs::get('content_imagefield_path')) ) { $dir .= DIRECTORY_SEPARATOR . trim($tmp, ' \\/'); }
			$data = $this->GetPropertyValue('thumbnail');
			$filepicker = cms_utils::get_filepicker_module();
			if( $filepicker ) {
				$profile = $filepicker->get_default_profile( $dir, get_userid() );
				$profile = $profile->overrideWith( ['top'=>$dir, 'type'=>'image', 'match_prefix'=>'thumb_' ] );
				$input = $filepicker->get_html( 'thumbnail', $data, $profile);
			}
			else {
				$input = create_file_dropdown('thumbnail',$dir,$data,'jpg,jpeg,png,gif','',true,'','thumb_',0,1);
			}
			if( !$input ) return [];
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_thumbnail',lang('help_title_content_thumbnail'));
			return array('<label for="thumbnail">'.lang('thumbnail').':</label>'.$help,$input);

		case 'titleattribute':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_titleattribute',lang('help_title_content_ta'));
			return array('<label for="titleattribute">'.lang('titleattribute').':</label>'.$help,
						 '<input type="text" name="titleattribute" id="titleattribute" maxlength="255" size="80" value="'.cms_htmlentities($this->mTitleAttribute).'">');

		case 'accesskey':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_accesskey',lang('help_title_content_accesskey'));
			return array('<label for="accesskey">'.lang('accesskey').':</label>'.$help,
						 '<input type="text" name="accesskey" id="accesskey" maxlength="5" value="'.cms_htmlentities($this->mAccessKey).'">');

		case 'tabindex':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_tabindex',lang('help_title_content_tabindex'));
			return array('<label for="tabindex">'.lang('tabindex').':</label>'.$help,
						 '<input type="text" name="tabindex" id="tabindex" maxlength="5" value="'.cms_htmlentities($this->mTabIndex).'">');

		case 'extra1':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_extra1',lang('help_title_content_extra1'));
			return array('<label for="extra1">'.lang('extra1').':</label>'.$help,
						 '<input type="text" name="extra1" id="extra1" maxlength="255" size="80" value="'.cms_htmlentities($this->GetPropertyValue('extra1')).'">');

		case 'extra2':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_extra2',lang('help_title_content_extra2'));
			return array('<label for="extra2">'.lang('extra2').':</label>'.$help,
						 '<input type="text" name="extra2" id="extra2" maxlength="255" size="80" value="'.cms_htmlentities($this->GetPropertyValue('extra2')).'">');

		case 'extra3':
			$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_extra3',lang('help_title_content_extra3'));
			return array('<label for="extra3">'.lang('extra3').':</label>'.$help,
						 '<input type="text" name="extra3" id="extra3" maxlength="255" size="80" value="'.cms_htmlentities($this->GetPropertyValue('extra3')).'">');

		case 'owner':
			$showadmin = ContentOperations::get_instance()->CheckPageOwnership(get_userid(),$this->mId);
			if (!$adding && (check_permission(get_userid(),'Manage All Content') || $showadmin) ) {
				$userops = UserOperations::get_instance();
				$help = '&nbsp;'.cms_admin_utils::get_help_tag('core','help_content_owner',lang('help_title_content_owner'));
				return array('<label for="owner">'.lang('owner').':</label>'.$help, $userops->GenerateDropdown($this->Owner()));
			}
			break;

		case 'additionaleditors':
			// do owner/additional-editor stuff
			if( $adding || check_permission(get_userid(),'Manage All Content') ||
				ContentOperations::get_instance()->CheckPageOwnership(get_userid(),$this->mId) ) {
				return $this->ShowAdditionalEditors();
			}
			break;

		default:
			throw new CmsInvalidDataException('Attempt to display invalid property '.$name);
		}
		return [];
	}
} // end of class

?>
