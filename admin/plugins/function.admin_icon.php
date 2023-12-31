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

function smarty_function_admin_icon($params,$template)
{
    $smarty = $template->smarty;

    if( !cmsms()->test_state(CmsApp::STATE_ADMIN_PAGE) ) return;

    $icon = null;
    $tagparms = array('class'=>'systemicon');
    foreach( $params as $key => $value ) {
        switch( $key ) {
        case 'icon':
            $icon = trim($value);
            break;
        case 'width':
        case 'height':
        case 'alt':
        case 'rel':
        case 'class':
        case 'id':
        case 'name':
        case 'title':
        case 'accesskey':
            $tagparms[$key] = trim($value);
            break;
        case 'assign':
            break;
        }
    }

    if( !$icon ) return;
    $fnd = cms_admin_utils::get_icon($icon);
    if( !$fnd ) return;

    if( !isset($tagparms['alt']) ) $tagparms['alt'] = basename($fnd);

    $out = "<img src=\"{$fnd}\"";
    foreach( $tagparms as $key => $value ) {
        $out .= " $key=\"$value\"";
    }
    $out .= '/>';

    if( isset($params['assign']) ) {
        $smarty->assign(trim($params['assign']),$out);
        return;
    }
    return $out;
}

?>
