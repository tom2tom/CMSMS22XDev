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
$orig_memory = (function_exists('memory_get_usage')?memory_get_usage():0);
require_once '../lib/include.php';
check_login();

$userid = get_userid();
if( !check_permission($userid,'Modify Site Preferences') ) {
    exit(lang('no_permission')); //TODO throw if can be caught
}

$gCms = CmsApp::get_instance();
$db = $gCms->GetDb();
$themeObject = cms_utils::get_theme_object();

// get the total number of records NOTE filtered records prob'ly less than this
$totalrows = $db->GetOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'adminlog');

$access = check_permission($userid,'Clear Admin Log');

if( $access && isset($_GET['clear']) ) {
    $query = 'DELETE FROM '.CMS_DB_PREFIX.'adminlog';
    $db->Execute($query);
    unset($_SESSION['adminlog_page']);
    unset($_REQUEST['page']);
    $themeObject->ShowMessage(lang('adminlogcleared'));
    // put mention into the admin log
    audit('','Admin log','Cleared');
}

//TODO paging doesn't properly-handle filtering
$page = ( isset($_SESSION['adminlog_page']) ) ? (int) $_SESSION['adminlog_page'] : 1;
if( isset($_REQUEST['page']) ) {
    $page = (int) $_REQUEST['page'];
    $_SESSION['adminlog_page'] = $page;
}

$limit = 25; //aka page-length & db-query length
$npages = (int)ceil(($totalrows / $limit) - 0.001); //WRONG if filtered
$page = max(1,min($npages,$page));
$from = ($page-1) * $limit;
$orig_filter = new stdClass();
$orig_filter->user = '';
$orig_filter->action = '';
$orig_filter->item_name = '';
if( !empty($_SESSION['adminlog_filter']) ) { $filter = $_SESSION['adminlog_filter']; }
else { $filter = clone $orig_filter; }

// handle filtering dialog.
if( isset($_POST['filterapply']) ) {
    $filter->user = trim(cleanValue($_POST['filteruser']));
    $filter->action = trim(cleanValue($_POST['filteraction']));
    $filter->item_name = trim(cleanValue($_POST['filteritem']));
    $_SESSION['adminlog_filter'] = $filter;
    unset($_SESSION['adminlog_page']);
    $page = 1;
} else if( isset($_POST['filterreset']) ) {
    $filter = $orig_filter;
    unset($_SESSION['adminlog_filter']);
    unset($_SESSION['adminlog_page']);
    $page = 1;
}
$filter_applied = ($filter != $orig_filter);

// now do the query
$sql = 'SELECT * FROM '.CMS_DB_PREFIX.'adminlog ';
$where = $parms = array();
if( $filter->user ) {
    $where[] = 'username = ?';
    $parms[] = $filter->user;
}
if( $filter->action ) {
    $where[] = 'action LIKE ?';
    $parms[] = '%'.$filter->action.'%';
}
if( $filter->item_name ) {
    $where[] = 'item_name LIKE ?';
    $parms[] = '%'.$filter->item_name.'%';
}
if( $where ) {
    $sql .= ' WHERE '.implode(' AND ',$where);
}
$sql .= ' ORDER BY timestamp DESC';

if( isset($_GET['download']) ) {
    // when downloading, honor the filter but skip paging
    $result = $db->Execute($sql,$parms);
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="adminlog.txt"');
    if( $result && $result->RecordCount() > 0 ) {
        $dateformat = trim(cms_userprefs::get_for_user($userid,'date_format_string','%x %X'));
        if( !$dateformat ) $dateformat = '%x %X';
        while ($row = $result->FetchRow()) {
            echo locale_ftime($dateformat,$row['timestamp'])."|";
            echo $row['username'] . "|";
            echo (((int)$row['item_id'] == -1) ? '' : $row['item_id']) . "|";
            echo $row['item_name'] . "|";
            echo $row['action'];
            echo "\n";
        }
    }
    if( $result ) $result->Close();
    return;
}

// this is not a download: process paging
$result = $db->SelectLimit($sql,$limit,$from,$parms);

// begin output
require_once 'header.php';
$themeObject->set_value('pagetitle','adminlog');

$tpl = $smarty->createTemplate('admin_tpl:adminlog.tpl',null,null,$smarty,false);

if ($result && $result->RecordCount() > 0) {
    //TODO paging doesn't properly-handle filtering
    $pagelist = array();
    if( $npages < 20 ) {
        for( $i = 1; $i <= $npages; $i++ ) {
            $pagelist[$i] = $i;
        }
    }
    else {
        // first 5
        for( $i = 1; $i <= 5; $i++ ) {
            $pagelist[$i] = $i;
        }
        // around my current page
        if( $page > 3 ) {
            for( $i = $page - 2; $i <= $page + 2; $i++ ) {
                $pagelist[$i] = $i;
            }
        }
        // middle 5
        $tpage = $page;
        if( $tpage <= 5 || $tpage >= ($npages - 5) ) $tpage = $npages / 2;
        $x1 = max(1,(int)($tpage - 5 / 2));
        $x2 = min($npages,(int)($tpage + 5 / 2));
        for( $i = $x1; $i <= $x2; $i++ ) {
            $pagelist[] = $i;
        }
        // last 5
        for( $i = max(1,$npages - 5); $i <= $npages; $i++ ) {
            $pagelist[] = $i;
        }
        $pagelist = array_unique($pagelist);
        sort($pagelist);
        $pagelist = array_combine($pagelist,$pagelist);
    }
    $tpl->assign('page',$page);
    $tpl->assign('pagelist',$pagelist);
    $tpl->assign('downloadlink',$themeObject->DisplayImage('icons/system/attachment.gif',lang('download'),'','','systemicon'));
    $tpl->assign('langdownload',lang('download'));
    $tpl->assign('languser',lang('user'));
    $tpl->assign('langitemid',lang('itemid'));
    $tpl->assign('langitemname',lang('itemname'));
    $tpl->assign('langaction',lang('action'));
    $tpl->assign('langdate',lang('date'));

    $loglines = array();
    while ($row = $result->FetchRow()) {
        $one = array();
        $one['ip_addr'] = $row['ip_addr'];
        $one['username'] = $row['username'];
        $one['itemid'] = ($row['item_id'] != -1) ? $row['item_id']:'&nbsp;';
        $one['itemname'] = cleanValue($row['item_name']);
        $one['action'] = cleanValue($row['action']);
        $one['date'] = $row['timestamp'];

        $loglines[] = $one;
    }
    $tpl->assign('loglines',$loglines);
    $tpl->assign('logempty',false);
}
else {
    $tpl->assign('langlogempty',lang('adminlogempty'));
    $tpl->assign('logempty',true);
}

if( $access && $result && $result->RecordCount() > 0 ) {
    $tpl->assign('clearicon',$themeObject->DisplayImage('icons/system/delete.gif',lang('delete'),'','','systemicon'));
    $tpl->assign('langclear',lang('clear'));
}
else {
    $tpl->assign('clearicon','');
}
if( $result ) $result->Close();

// see also $smarty-assigned var $secureparam
$tpl->assign('sysmain_confirmclearlog',lang('sysmain_confirmclearlog'))
 ->assign('langfilteruser',lang('filteruser'))
 ->assign('langfilteraction',lang('filteraction'))
 ->assign('langfilterapply',lang('filterapply'))
 ->assign('langfilterreset',lang('filterreset'))
 ->assign('filter',$filter)
 ->assign('filter_applied',$filter_applied);
$tpl->display();

require_once 'footer.php';
