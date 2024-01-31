<?php
#CMS Made Simple admin console script used by jQueryUI widget cmsms.hierselector
#(c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANthe TY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id$

$CMS_ADMIN_PAGE=1;
require_once("../lib/include.php");

$op = 'pageinfo';
if( isset($_REQUEST['op']) ) $op = trim($_REQUEST['op']);
$gCms = CmsApp::get_instance();
$hm = $gCms->GetHierarchyManager();
$contentops = $gCms->GetContentOperations();
$allow_all = 1; //until 2.2.18, this always applied
if( isset($_REQUEST['allow_all']) && !cms_to_bool($_REQUEST['allow_all']) ) $allow_all = 0;
$for_child = (isset($_REQUEST['for_child']) && cms_to_bool($_REQUEST['for_child'])) ? 1 : 0;
$allowcurrent = (isset($_REQUEST['allowcurrent']) && cms_to_bool($_REQUEST['allowcurrent'])) ? 1 : 0;
$current = (isset($_REQUEST['current']) ) ? (int)$_REQUEST['current'] : 0;

$display = 'title';
$mod = cms_utils::get_module('CMSContentManager');
if( $mod ) $display = CmsContentManagerUtils::get_pagenav_display();

$ruid = get_userid(FALSE);
try {
    if( $ruid < 1 ) throw new Exception('permissiondenied'); // should throw a 403
    $can_edit_any = check_permission($ruid,'Manage All Content') || check_permission($ruid,'Modify Any Page');

    $out = [];
    $error = '';
    switch( $op ) {
    case 'userlist':
    case 'userpages':
        $tmplist = $contentops->GetPageAccessForUser($ruid);
        if( count($tmplist) ) {
            $display = $pagelist = [];
            foreach( $tmplist as $item ) {
                // get all the parents
                $parents = [];
                $startnode = $node = $contentops->quickfind_node_by_id($item);
                while( $node && $node->get_tag('id') > 0 ) {
                    $content = $node->getContent(FALSE);
                    $rec = $content->ToData();
                    $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($ruid,$content->Id());
                    $rec['display'] = strip_tags($rec['menu_text']);
                    if( $display == 'title' ) $rec['display'] = strip_tags($rec['content_name']);
                    $rec['has_children'] = $node->has_children();
                    $parents[] = $rec;
                    $node = $node->get_parent();
                }
                // start at root
                // push items from list on the stack if they are root, or the previous item is in the opened array.
                $parents = array_reverse($parents);
                for( $i = 0; $i < count($parents); $i++ ) {
                    $content_id = $parents[$i]['content_id'];
                    if( !in_array($content_id,$pagelist) ) {
                        $pagelist[] = $content_id;
                        $display[] = $parents[$i];
                    }
                }
                unset($parents);
            }
            usort($display,function($a,$b) {
                    return strcmp($a['hierarchy'],$b['hierarchy']);
                });
            $out = $display;
            unset($display);
        }
        break;

    case 'here_up':
        // given a page id, get all info for all ancestors, and their peers.
        // as well as the info for the page's current children.
        if( !isset($_REQUEST['page']) ) throw new Exception('missingparams');

        $children_to_data = function($node) use ($display,$allow_all,$for_child,$ruid,$contentops,$can_edit_any,$allowcurrent,$current) {
            $children = $node->getChildren(false,$allow_all);
            if( empty($children) ) return [];

            $child_info = [];
            foreach( $children as $child ) {
                $content = $child->getContent(FALSE);
                if( !is_object($content) ) continue;
                if( !$allow_all && !$content->Active() ) continue;
                if( !$allow_all && !$content->HasUsableLink() ) continue;
                if( !$allowcurrent && $current == $content->Id() ) continue;
                $rec = $content->ToData();
                $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($ruid,$content->Id());
                $rec['display'] = strip_tags($rec['menu_text']);
                if( $display == 'title' ) $rec['display'] = strip_tags($rec['content_name']);
                $rec['has_children'] = $child->has_children();
                $child_info[] = $rec;
            }
            return $child_info;
        };

        $out = [];
        $page = (int)$_REQUEST['page'];
        if( $page < 1 ) $page = -1;

        if( $page == -1 ) {
            $node = $hm; // root
        } else {
            $node = $contentops->quickfind_node_by_id($page);
        }
        do {
            $out[] = $children_to_data($node); // get children of current page.
            $node = $node->get_parent();
        } while( $node );
        $out = array_reverse($out);
        break;

    case 'childrenof':
        if( !isset($_REQUEST['page']) ) {
            $error = 'missingparams';
        }
        else {
            $page = (int)$_REQUEST['page'];
            if( $page < 1 ) $page = -1;
            if( $page == -1 ) {
                $node = $hm;
            }
            else {
                $node = $contentops->quickfind_node_by_id($page);
            }
            if( $node ) {
                $children = $node->getChildren(FALSE,$allow_all);
                if( is_array($children) && count($children) ) {
                    $out = array();
                    foreach( $children as $child ) {
                        $content = $child->getContent(FALSE);
                        if( !is_object($content) ) continue;
                        if( !$allow_all && !$content->Active() ) continue;
                        $rec = $content->ToData();
                        $rec['can_edit'] = check_permission($ruid,'Manage All Content') || $contentops->CheckPageAuthorship($ruid,$content->Id());
                        $rec['display'] = strip_tags($rec['menu_text']);
                        if( $display == 'title' ) $rec['display'] = strip_tags($rec['content_name']);
                        $out[] = $rec;
                    }
                }
            }
        }
        break;

    case 'pageinfo':
        if( !isset($_REQUEST['page']) ) {
            $error = 'missingparams';
        }
        else {
            $page = (int)$_REQUEST['page']; // value < 1 treated as default page
            // get the page info
            $contentobj = $contentops->LoadContentFromId($page);
            if( !is_object($contentobj) ) {
                $error = 'errorgettingcontent';
            }
            else {
                $out = $contentobj->ToData();
                if( $display == 'title' ) { $out['display'] = $out['content_name']; }
                else { $out['display'] = $out['menu_text']; }
            }
        }
        break;

    case 'pagepeers':
        if( !isset($_REQUEST['pages']) || !is_array($_REQUEST['pages']) ) {
            $error = 'missingparams';
        }
        else {
            // clean up the data a bit
            $tmp = array();
            foreach( $_REQUEST['pages'] as $one ) {
                $one = (int)$one;
                // discard negative values
                if( $one > 0 ) $tmp[] = $one;
            }
            $peers = array_unique($tmp);

            $out = [];
            foreach( $peers as $one ) {
                $node = $hm->find_by_tag('id',$one);
                if( !$node ) continue;

                // get the parent
                $parent_node = $node->get_parent();

                // and get it's children
                $out[$one] = [];
                $children = $parent_node->getChildren(FALSE,$allow_all);
                for( $i = 0, $n = count($children); $i < $n; $i++ ) {
                    $content_obj = $children[$i]->getContent(FALSE);
                    if( ! $content_obj->IsViewable() ) continue;
                    $rec = [];
                    $rec['content_id'] = $content_obj->Id();
                    $rec['id_hierarchy'] = $content_obj->IdHierarchy();
                    $rec['wants_children'] = $content_obj->WantsChildren();
                    $rec['has_children'] = $children[$i]->has_children();
                    $rec['display'] = ($display == 'title') ? $content_obj->Name() : $content_obj->MenuText();
                    $out[$one][] = $rec;
                }
            }
        }
        break;

    default:
        throw new Exception('missingparam');
    }
}
catch( Exception $e ) {
    $error = $e->GetMessage();
}

if( $error ) {
    $out = array('status'=>'error','message'=>lang($error));
}
else {
    $out = array('status'=>'success','op'=>$op,'data'=>$out);
}

header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private',false);
header('Content-Type: application/json');
echo json_encode($out);
exit;

#
# EOF
#
