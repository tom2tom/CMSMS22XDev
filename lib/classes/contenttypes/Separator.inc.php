<?php
#CMS Made Simple class Separator
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
#$Id: Separator.inc.php 9406 2014-03-28 23:06:42Z calguy1000 $

/**
 * Implements the CMS Made Simple Separator content type
 *
 * A separator is used simply for navigations to provide a visual separation
 * between menu items.  Typically as a horizontal or vertical bar.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class Separator extends ContentBase
{

	public function SetProperties()
	{
		parent::SetProperties();
		$this->RemoveProperty('secure',false);
		$this->RemoveProperty('template','-1');
		$this->RemoveProperty('alias','');
		$this->RemoveProperty('title','');
		$this->RemoveProperty('menutext','');
		$this->RemoveProperty('target','');
		$this->RemoveProperty('accesskey','');
		$this->RemoveProperty('titleattribute','');
		$this->RemoveProperty('cachable',true);
		$this->RemoveProperty('page_url','');
		$this->RemoveProperty('tabindex','');
	}

	public function GetURL($rewrite = true) { return '#';  }
	public function IsViewable() { return false; }
	public function FriendlyName() { return lang('contenttype_separator'); }
	public function HasUsableLink() { return false; }
	public function WantsChildren() { return false; }
	public function RequiresAlias() { return false; }
	public function HasSearchableContent() { return false; }

	public function TabNames()
	{
		$res = array(lang('main'));
		if( check_permission(get_userid(),'Manage All Content') ) $res[] = lang('options');
		return $res;
	}

	//this is a redundant CMSMS1 method TODO remove
	public function EditAsArray($adding = false, $tab = 0, $showadmin = false)
	{
		switch($tab) {
		case '0':
			return $this->display_attributes($adding);
		case '1':
			return $this->display_attributes($adding,1);
		default:
			return [];
		}
	}

	public function ValidateData()
	{
		$this->mName = CMS_CONTENT_HIDDEN_NAME;
		return parent::ValidateData();
	}

}

?>
