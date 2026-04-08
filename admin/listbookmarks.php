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

require_once 'header.php';

$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
$limit = 20; // max items per page
$showinfo = false;

$show = [];
$userid = get_userid();
$bookops = cmsms()->GetBookmarkOperations();
$marklist = $bookops->LoadBookmarks($userid);
if ($marklist) {
	$urlext = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
	$gmax = $page * $limit;
	for ($ctr = $gmax - $limit; $ctr < $gmax; $ctr++) {
		if (isset($marklist[$ctr])) {
			//replicate part of BookmarkOperations::_prep_for_saving()
			$marklist[$ctr]->url = str_replace($urlext,'[SECURITYTAG]',$marklist[$ctr]->url);
			$show[] = $marklist[$ctr];
		}
	}
	$showinfo = !cms_userprefs::get_for_user($userid,'bookmarks',false);
}

$themeObject->set_value('pagetitle', 'bookmarks');

$smarty->changeCaching(false);
$tpl = $smarty->createTemplate('admin_tpl:listbookmarks.tpl',null,null,$smarty,false);
// see also $smarty-assigned var $secureparam
$tpl->assign('showinfo',$showinfo)
 ->assign('iconadd',$themeObject->DisplayImage('icons/system/newobject.gif',lang('addbookmark'),'','','systemicon'))
 ->assign('iconedit',$themeObject->DisplayImage('icons/system/edit.gif',lang('editbookmark'),'','','systemicon'))
 ->assign('icondelete',$themeObject->DisplayImage('icons/system/delete.gif',lang('delete'),'','','systemicon'));
if (($n = count($marklist)) > $limit) {
	$tpl->assign('pagination',pagination($page,$n,$limit));
}
$tpl->assign('marklist',$show);
$tpl->display();

require_once 'footer.php';
