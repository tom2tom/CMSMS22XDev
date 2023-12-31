<?php
#CMS - CMS Made Simple
#(c)2004 by Ted Kulp (wishy@users.sf.net)
#Visit our homepage at: http://www.cmsmadesimple.org
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

function smarty_function_tab_header($params,$template)
{
	$smarty = $template->smarty;

	if( !isset($params['name']) ) return;
	$name = trim($params['name']);
	$label = $name;
	$active = FALSE;
	if( isset($params['label']) ) $label = trim($params['label']);
	if( isset($params['active']) ) {
		$tmp = trim($params['active']);
		if( $tmp == $name ) {
			$active = TRUE;
		}
		else {
			$active = cms_to_bool($tmp);
		}
	}

	$out = cms_admin_tabs::set_tab_header($name,$label,$active);
	if( isset($params['assign']) ) {
		$smarty->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}
?>
