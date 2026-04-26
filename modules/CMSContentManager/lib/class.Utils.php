<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module CMSContentManager class Utils
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program. If not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
#END_LICENSE

namespace CMSContentManager;

use cms_userprefs;
use cms_utils;
use CmsDataNotFoundException;
use CmsLayoutCollection;
use CmsLayoutTemplate;
use CmsLayoutTemplateType;
use function get_userid;

/**
 * Utility methods for the CMSContentManager module.
 *
 * This is an internal class.  Use of this class in third party modules will not be supported.
 *
 * @package CMS
 * @internal
 * @ignore
 * @author Robert Campbell
 */
final class Utils
{
	private function __construct() {}

	public static function get_pagedefaults()
	{
		try {
			$tpl = CmsLayoutTemplate::load_dflt_by_type(CmsLayoutTemplateType::CORE.'::page');
			$tpl_id = $tpl->get_id();
		}
		catch( CmsDataNotFoundException $e ) {
			$type = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::page');
			$list = CmsLayoutTemplate::load_all_by_type($type);
			$tpl = $list[0];
			$tpl_id = $tpl->get_id();
		}

		$page_prefs = array(
			'contenttype'=>'content', // string
			'disallowed_types'=>'', // array of strings
			'design_id'=>CmsLayoutCollection::load_default()->get_id(), // int
			'template_id'=>$tpl_id,
			'parent_id'=>-2, // int
			'secure'=>0, // boolean
			'cachable'=>1, // boolean
			'active'=>1, // boolean
			'showinmenu'=>1, // boolean
			'metadata'=>'', // string
			'content'=>'', // string
			'searchable'=>1, // boolean
			'addteditors'=>array(), // array of ints.
			'extra1'=>'', // string
			'extra2'=>'', // string
			'extra3'=>''); // string
		$mod = cms_utils::get_module('CMSContentManager');
		$tmp = $mod->GetPreference('page_prefs');
		if( $tmp ) $page_prefs = unserialize($tmp);

		return $page_prefs;
	}

	public static function locking_enabled()
	{
		$mod = cms_utils::get_module('CMSContentManager');
		$timeout = (int) $mod->GetPreference('locktimeout');
		return ( $timeout > 0 );
	}

	public static function get_pagenav_display()
	{
		$userid = get_userid(FALSE);
		$pref = cms_userprefs::get_for_user($userid,'ce_navdisplay');
		if( !$pref ) {
			$mod = cms_utils::get_module('CMSContentManager');
			$pref = $mod->GetPreference('list_namecolumn','title');
		}
		return $pref;
	}
}
?>
