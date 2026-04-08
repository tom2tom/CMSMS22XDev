<?php
#CMS Made Simple admin console script
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

$CMS_ADMIN_PAGE = 1;

require_once '../lib/include.php';

check_login();
$userid = get_userid();
$access = check_permission($userid, 'Modify User-defined Tags');
if (!$access) {
    exit(lang('no_permission')); //TODO throw if can be caught
}

require_once 'header.php';

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

function listudt_summarize($str,$numwords,$ets='...')
{
    if( !$str ) { return (string)$str; }
    $str = strip_tags($str);
    $stringarray = explode(" ",$str);
    $numwords = min(max($numwords,1),100);
    if( $numwords >= count($stringarray) ) { return $str; }
    $tmp = array_slice($stringarray,0,$numwords);
    $tmp = implode(' ',$tmp).$ets;
    return $tmp;
}

if (!empty($_GET['message'])) $themeObject->ShowMessage(lang($_GET['message']));

$list = UserTagOperations::get_instance()->ListUserTags();
$tags = [];
if( $list && is_array($list) ) {
    foreach( $list as $id => $label ) {
        $tag = UserTagOperations::get_instance()->GetUserTag($id);
        $rec = array();
        $rec['id'] = $id;
        $rec['name'] = $label;
        $rec['description'] = listudt_summarize($tag['description'],20);
        $tags[$id] = $rec;
    }
}

$tpl = $smarty->createTemplate('admin_tpl:listusertags.tpl',null,null,$smarty,false);
$tpl->assign('tags',$tags)
 ->assign('addurl','editusertag.php'.$urlext);
$tpl->display();

require_once 'footer.php';
