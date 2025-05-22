<?php
#-------------------------------------------------------------------------
# Module DesignManager class dm_utils
# (c) 2012 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# Or read it online: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

final class dm_utils
{
	public function __construct() {}

	public static function locking_enabled()
	{
		$mod = cms_utils::get_module('DesignManager');
		$timeout = (int)$mod->GetPreference('lock_timeout');
		return ($timeout > 0);
	}

	public static function get_template_locks()
	{
		static $_tlocks = [];
		static $_tlocks_loaded = FALSE;
		if( !$_tlocks_loaded ) {
			$_tlocks_loaded = TRUE;
			$tmp = CmsLockOperations::get_locks('template');
			if( $tmp && is_array($tmp) ) {
				foreach( $tmp as $lock_obj ) {
					$_tlocks[$lock_obj['oid']] = $lock_obj;
				}
			}
		}
		return $_tlocks;
	}

	public static function get_css_locks()
	{
		static $_slocks = [];
		static $_slocks_loaded = FALSE;
		if( !$_slocks_loaded ) {
			$_slocks_loaded = TRUE;
			$tmp = CmsLockOperations::get_locks('stylesheet');
			if( $tmp && is_array($tmp) ) {
				foreach( $tmp as $lock_obj ) {
					$_slocks[$lock_obj['oid']] = $lock_obj;
				}
			}
		}
		return $_slocks;
	}

} // end of class
