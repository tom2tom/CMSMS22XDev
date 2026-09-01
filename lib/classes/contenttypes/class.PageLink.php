<?php
#CMS Made Simple class PageLink
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

//namespace CMSMS\contenttypes;

/**
 * Implements the PageLink content type.
 *
 * This content type simply provides a way to manage additional links to internal content pages
 * that may be in another place in the page hierarchy.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class PageLink extends ContentBase
{
	public function IsCopyable() { return true; }
	public function IsViewable() { return false; }
//	public function IsDefaultPossible() { return true; } can true be valid for this type?
	public function HasSearchableContent() { return false; }
//	public function HasUsableLink() { return false; } is false valid for this type?
	public function FriendlyName() { return lang('contenttype_pagelink'); }

	public function SetProperties()
	{
		parent::SetProperties();
		$this->RemoveProperty('cachable',true);
		$this->RemoveProperty('secure',false);
//		$this->AddProperty('default',2,parent::TAB_OPTIONS,true); consistent with IsDefaultPossible()
		$this->AddProperty('page',3,parent::TAB_MAIN,true,true);
		$this->AddProperty('params',4,parent::TAB_OPTIONS,true,true);

		//Turn off caching
		$this->mCachable = false;
	}

	public function FillParams($params, $editing = false)
	{
		parent::FillParams($params,$editing);

		if (isset($params)) {
			$parameters = array('page','params' );
			foreach ($parameters as $oneparam) {
				if (isset($params[$oneparam])) $this->SetPropertyValue($oneparam,$params[$oneparam]);
			}
		}
	}

	public function ValidateData()
	{
		$errors = parent::ValidateData();

		$page = $this->GetPropertyValue('page');
		if ($page == '-1') {
			$errors[]= lang('nofieldgiven',lang('page'));
		}

		// get the content type of page.
		else {
			$contentops = ContentOperations::get_instance();
			$destobj = $contentops->LoadContentFromID($page);
			if( !is_object($destobj) ) {
				$errors[] = lang('destinationnotfound');
			}
			else if( $destobj->Type() == 'pagelink' ) {
				$errors[] = lang('pagelink_circular');
			}
			else if( $destobj->Alias() == $this->mAlias ) {
				$errors[] = lang('pagelink_circular');
			}
		}
		return $errors;
	}

	public function TabNames()
	{
		$res = array(lang('main'));
		//TODO relevance of $pown for this?
		if( check_permission(get_userid(),'Manage All Content') ) $res[] = lang('options');
		return $res;
	}

	protected function display_single_element($one,$adding,$pmac,$pown,$paed)
	{
		//TODO no help and disabled input(s) if user not authorised per args $pmac $pown
		switch($one) {
		case 'page':
			$contentops = ContentOperations::get_instance();
			$tmp = $contentops->CreateHierarchyDropdown($this->mId,$this->GetPropertyValue('page'),'page',true,false,false,false,false,'selpage');
			if( $tmp ) return array(lang('destination_page').':',$tmp);
			break;

		case 'params':
			$val = cms_htmlentities($this->GetPropertyValue('params'));
			return array(lang('additional_params').':','<input type="text" name="params" value="'.$val.'">');
			break;

		default:
			return parent::display_single_element($one,$adding,$pmac,$pown,$paed);
		}
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

	public function GetURL($rewrite = true)
	{
		$page = $this->GetPropertyValue('page');
		$params = $this->GetPropertyValue('params');

		$contentops = ContentOperations::get_instance();
		$destcontent = $contentops->LoadContentFromId($page);
		if( is_object( $destcontent ) ) {
			$url = $destcontent->GetURL();
			$url .= $params;
			return $url;
		}
	}
}

?>
