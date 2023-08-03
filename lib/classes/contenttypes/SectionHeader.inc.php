<?php
#CMS Made Simple class SectionHeader
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
#$Id: SectionHeader.inc.php 9406 2014-03-28 23:06:42Z calguy1000 $

/**
 * Implements the CMS Made Simple Section Header content type
 *
 * Section headers are logical ways to organize content.  They usually appear in navigations, but are not navigable.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class SectionHeader extends ContentBase
{
	function FriendlyName() { return lang('contenttype_sectionheader'); }

	function SetProperties()
	{
		parent::SetProperties();
		$this->RemoveProperty('secure',0);
		$this->RemoveProperty('accesskey','');
		$this->RemoveProperty('cachable',true);
		$this->RemoveProperty('target','');
		$this->RemoveProperty('page_url','');
		$this->SetURL(''); // url will be lost when going back to a content page.

		// Turn off caching
		$this->mCachable = false;
	}

	public function HasUsableLink() { return false; }
	public function RequiresAlias() { return TRUE; }
	public function HasSearchableContent() { return FALSE; }
	public function GetURL($rewrite = true) { return '#'; }
	public function IsViewable() { return FALSE; }

	public function TabNames()
	{
		$res = array(lang('main'));
		if( check_permission(get_userid(),'Manage All Content') ) {
			$res[] = lang('options');
		}
		return $res;
	}

	public function EditAsArray($adding = false, $tab = 0, $showadmin = false)
	{
		switch($tab) {
		case '0':
			return $this->display_attributes($adding);
			break;
		case '1':
			return $this->display_attributes($adding,1);
			break;
		}
	}

	public function ValidateData()
	{
		$errors = parent::ValidateData();
		if( $errors && $this->mId < 1 ) {
			// some error occurred..
			// reset the menu text
			// and the alias
			$this->mName = '';
			$this->mMenuText = '';
		}
		$this->mTemplateId = -1;
		return $errors;
	}

}

?>
