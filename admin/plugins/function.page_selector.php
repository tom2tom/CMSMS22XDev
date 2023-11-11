<?php
#Plugin handler: page_selector
#(c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

function smarty_function_page_selector($params,$template)
{
    $value = (isset($params['value']) ) ? (int)$params['value'] : 0; // selected-page id
    $name = (isset($params['name']) ) ? trim($params['name']) : 'parent_id'; //input-element name
    $allowcurrent = (isset($params['allowcurrent']) ) ? cms_to_bool($params['allowcurrent']) : false;
    $allow_all = (isset($params['allow_all']) ) ? cms_to_bool($params['allow_all']) : false;
    $for_child = (isset($params['for_child']) ) ? cms_to_bool($params['for_child']) : false;
    // no current-page
    $out = ContentOperations::get_instance()->CreateHierarchyDropdown(0,$value,$name,$allowcurrent,false,false,$allow_all,$for_child);
    if( isset($params['assign']) )  {
        $smarty->assign(trim($params['assign']),$out);
        return '';
    }
    return $out;
}
