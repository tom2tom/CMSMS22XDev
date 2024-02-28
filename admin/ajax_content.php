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

$CMS_ADMIN_PAGE = 1;
require_once("../lib/include.php");

$op = 'pageinfo';
if( isset($_GET['op']) ) $op = trim($_GET['op']);
$gCms = CmsApp::get_instance();
$contentops = $gCms->GetContentOperations();

//in many contexts where a hierselector is initiated, $allow_all is set FALSE
$allow_all = TRUE; //in 2.2 to 2.2.18 this always applied, probably a workaround/bug
if( isset($_GET['allow_all']) && !cms_to_bool($_GET['allow_all']) ) $allow_all = FALSE;

$display = 'title';
$mod = cms_utils::get_module('CMSContentManager');
if( $mod ) $display = CmsContentManagerUtils::get_pagenav_display();

$ruid = get_userid(FALSE);
try {
    if( $ruid < 1 ) throw new CmsError403Exception('permissiondenied');
    $can_edit_any = check_permission($ruid,'Manage All Content') || check_permission($ruid,'Modify Any Page');

    $out = [];
    switch( $op ) {
//  case 'userlist': unused in cmsms.hierselector
/*  case 'userpages': never initiated via cmsms.hierselector
        // used when a selector was initiated with use_simple = true, ATM no such case across the CMSMS core
        $tmplist = $contentops->GetPageAccessForUser($ruid); //ids of pages which the user may edit
        if( $tmplist ) {
            $pagelist = [];
            foreach( $tmplist as $one ) {
                // get all ancestors
                $parents = [];
                $node = $contentops->quickfind_node_by_id($one);
                while( $node && $node->get_tag('id') > 0 ) {
                    $content = $node->getContent(FALSE);
                    $rec = $content->ToData();
                    $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($ruid,$content->Id());
                    $val = ( $display == 'title' ) ? $rec['content_name'] : $rec['menu_text'];
                    $rec['display'] = ( $val ) ? strip_tags($val) : lang('anonymous');
                    $rec['has_children'] = $node->has_children();
                    $parents[] = $rec;
                    $node = $node->get_parent();
                }
                // accumulate unique ancestor items, starting from the root
                $parents = array_reverse($parents);
                for( $i = 0; $i < count($parents); $i++ ) {
                    $content_id = $parents[$i]['content_id'];
                    if( !in_array($content_id,$pagelist) ) {
                        $pagelist[] = $content_id;
                        $out[] = $parents[$i];
                    }
                }
                unset($parents);
            }
            if( count($out) > 1 ) {
                usort($out,function($a,$b) {
                    return strcmp($a['hierarchy'],$b['hierarchy']);
                });
            }
        }
        break;
*/
    case 'here_up':
        // given a page id, get all info for it, its peers, and all
        // ancestors and their peers.
        // used when a selector was initiated with use_simple = false or unspecified
        if( !isset($_GET['page']) ) throw new CmsException('missingparams');
//      $for_child = isset($_GET['for_child']) && cms_to_bool($_GET['for_child']); // unused here TODO what is it intended to achieve in backend?
        $allowcurrent = isset($_GET['allowcurrent']) && cms_to_bool($_GET['allowcurrent']);
        $current = ( isset($_GET['current']) ) ? (int)$_GET['current'] : 0;

        $children_to_data = function($node) use ($contentops,$allow_all,$display,$ruid,$can_edit_any,/*$out,*$for_child,*/$allowcurrent,$current) {
            $children = $node->getChildren(FALSE,$allow_all);
            if( !$children ) return [];

            $child_info = [];
            foreach( $children as $child ) {
                $content = $child->getContent(FALSE);
                if( !is_object($content) ) continue;
                if( !$allowcurrent && $current == $content->Id() ) continue;
                if( !$allow_all && (!$content->Active() || !$content->HasUsableLink()) ) continue;
                $rec = $content->ToData();
                $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($ruid,$content->Id());
                $val = ( $display == 'title' ) ? $rec['content_name'] : $rec['menu_text'];
                $rec['display'] = ( $val ) ? strip_tags($val) : lang('anonymous');
                $rec['has_children'] = $child->has_children();
                $child_info[] = $rec;
            }
            return $child_info;
        };

        $page = (int)$_GET['page'];
        if( $page < 1 ) $page = -1;
        if( $page == -1 ) {
            $node = $gCms->GetHierarchyManager(); // TODO process -1 as content-tree-root, OR as default page c.f. pageinfo op?
        } else {
            $node = $contentops->quickfind_node_by_id($page);
        }
        do {
            $out[] = $children_to_data($node); // populate child-data of this node i.e. the node and its peers
            $node = $node->get_parent();
        } while( $node );
        $out = array_reverse($out); //TODO any further filtering etc
        break;

/*  case 'childrenof': // unused in cmsms.hierselector
        if( !isset($_GET['page']) ) {
            throw new CmsException('missingparams');
        }
        else {
            $page = (int)$_GET['page'];
            if( $page < 1 ) $page = -1;
            if( $page == -1 ) {
                $node = $gCms->GetHierarchyManager(); // TODO process -1 as content-tree-root, OR as default page c.f. pageinfo op?
            }
            else {
                $node = $contentops->quickfind_node_by_id($page);
            }
            if( $node ) {
                $children = $node->getChildren(FALSE,$allow_all);
                if( $children && is_array($children) ) {
                    foreach( $children as $child ) {
                        $content = $child->getContent(FALSE);
                        if( !is_object($content) ) continue;
                        if( !($allow_all || $content->Active()) ) { //TODO when is inactive but navigable ok? ? when is inactive ok, regardless?
                            continue;
                        }
                        $rec = $content->ToData();
                        $rec['can_edit'] = $can_edit_any || $contentops->CheckPageAuthorship($ruid,$content->Id());
                        $val = ( $display == 'title' ) ? $rec['content_name'] : $rec['menu_text'];
                        $rec['display'] = ( $val ) ? strip_tags($val) : lang('anonymous');
                        $out[] = $rec;
                    }
                }
            }
        }
        break;
*/
    case 'pageinfo':
        if( !isset($_GET['page']) ) {
            throw new CmsException('missingparams');
        }
        else {
            $page = (int)$_GET['page']; // value < 1 treated as default page
            // get the page info
            $content = $contentops->LoadContentFromId($page);
            if( !is_object($content) ) {
                throw new CmsException('errorgettingcontent');
            }
            else {
                $out = $content->ToData();
                $val = ( $display == 'title' ) ? $out['content_name'] : $out['menu_text'];
                $out['display'] = ( $val ) ? strip_tags($val) : lang('anonymous');
            }
        }
        break;

/*  case 'pagepeers': // unused in cmsms.hierselector
        if( !isset($_GET['pages']) || !is_array($_GET['pages']) ) { // never set in cmsms.hierselector
            throw new CmsException('missingparams');
        }
        else {
            // clean up the data a bit
            $tmp = array();
            foreach( $_GET['pages'] as $one ) {
                $one = (int)$one;
                // ignore negative values (clone in-the-making?)
                if( $one > 0 ) $tmp[] = $one;
            }
            $peers = array_unique($tmp);

            foreach( $peers as $one ) {
                $node = $contentops->quickfind_node_by_id($one);
                if( !$node ) continue;

                // get the parent
                $parent_node = $node->get_parent();

                // and get its children
                $out[$one] = [];
                $children = $parent_node->getChildren(FALSE,$allow_all);
                for( $i = 0, $n = count($children); $i < $n; $i++ ) {
                    $content = $children[$i]->getContent(FALSE);
                    if( !$content->IsViewable() ) continue;
                    $rec = [];
                    $rec['content_id'] = $content->Id();
                    $rec['id_hierarchy'] = $content->IdHierarchy();
                    $rec['wants_children'] = $content->WantsChildren();
                    $rec['has_children'] = $children[$i]->has_children();
                    $val = ( $display == 'title' ) ? $content->Name() : $content->MenuText();
                    $rec['display'] = ( $val ) ? strip_tags($val) : lang('anonymous');
                    $out[$one][] = $rec;
                }
            }
        }
        break;
*/
    default:
        throw new CmsException('missingparam');
    }
}
catch( Exception $e ) {
    $out = array('status'=>'error','message'=>$e->GetMessage());
    $error = TRUE;
}

if( empty($error) ) {
    $out = array('status'=>'success','op'=>$op,'data'=>$out);
}

echo json_encode($out);
exit;

#
# EOF
#
