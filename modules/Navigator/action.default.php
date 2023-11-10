<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module Navigator action
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# A module for CMS Made Simple to allow building hierarchical navigations.
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE
if( !defined('CMS_VERSION') ) exit;

debug_buffer('Start Navigator default action');
$items = '';
$nlevels = -1;
$show_all = FALSE;
$show_root_siblings = FALSE;
$start_element = '';
$start_page = '';
$start_level = 0;
$childrenof = '';
$deep = TRUE;
$collapse = FALSE;

$template = '';
if( isset($params['template']) ) {
    $template = trim($params['template']);
}
else {
    $tpl = CmsLayoutTemplate::load_dflt_by_type('Navigator::navigation');
    if( !is_object($tpl) ) {
        audit('',$this->GetName(),'No default template found');
        return;
    }
    $template = $tpl->get_name();
}

$cache_id = '|nav'.md5(serialize($params).$returnid);
$compile_id = '';

$tpl = $smarty->CreateTemplate($this->GetTemplateResource($template),$cache_id,$compile_id,$smarty);
if( !$tpl->isCached() ) {
    $hm = $gCms->GetHierarchyManager();
    foreach( $params as $key => $value ) {
        switch( $key ) {
        case 'loadprops':
            $deep = cms_to_bool($value);
            break;

        case 'items':
            // hardcoded list of items (and their children)
            Nav_utils::clear_excludes();
            $items = trim($value);
            $nlevels = 1;
            $start_element = '';
            $start_page = '';
            $start_level = 0;
            $childrenof = '';
            break;

        case 'includeprefix':
            Nav_utils::clear_excludes();
            $list = explode(',',$value);
            if( is_array($list) && count($list) ) {
                foreach( $list as &$one ) {
                    $one = trim($one);
                }
                $list = array_unique($list);
                if( count($list) ) {
                    $flatlist = $hm->getFlatList();
                    if( is_array($flatlist) && count($flatlist) ) {
                        $tmp = [];
                        foreach( $flatlist as $id => &$node ) {
                            $alias = $node->get_tag('alias');
                            foreach( $list as $t1 ) {
                                if( startswith( $alias, $t1 ) ) $tmp[] = $alias;
                            }
                        }
                        if( is_array($tmp) && count($tmp) ) $items = implode(',',$tmp);
                    }
                }
            }
            $nlevels = 1;
            $start_element = '';
            $start_page = '';
            $start_level = 0;
            $childrenof = '';
            break;

        case 'excludeprefix':
            Nav_utils::set_excludes($value);
            $items = '';
            break;

        case 'nlevels':
        case 'number_of_levels':
            // a maximum number of levels;
            if( (int)$value > 0 ) $nlevels = (int)$value;
            break;

        case 'show_all':
            // show all items, even if marked as 'not shown in menu' TODO all including excludes ?
            $show_all = cms_to_bool($value);
            break;

        case 'show_root_siblings':
            // given a start element or start page ... show its siblings too
            $show_root_siblings = cms_to_bool($value);
            break;

        case 'start_element':
            $start_element = trim((string)$value);
            $start_page = '';
            $start_level = 0;
            $childrenof = '';
            $items = '';
            break;

        case 'start_page':
            $start_element = '';
            $start_page = trim((string)$value);
            $start_level = 0;
            $childrenof = '';
            $items = '';
            break;

        case 'start_level':
            $value = (int)$value;
            if( $value > 1 ) {
                $start_element = '';
                $start_page = '';
                $items = '';
                $start_level = $value;
            }
            break;

        case 'childrenof':
            $start_page = '';
            $start_element = '';
            $start_level = 0;
            $childrenof = trim((string)$value);
            $items = '';
            break;

        case 'collapse':
            $collapse = (int)$value;
            break;
        }
    } // params

    if( $items ) $collapse = FALSE;

    $rootnodes = [];
    if( $start_element ) {
        // get an alias... from a hierarchy level.
        $tmp = $hm->getNodeByHierarchy($start_element);
        if( is_object($tmp) ) {
            if( !$show_root_siblings ) {
                $rootnodes[] = $tmp;
            }
            else {
                $tmp = $tmp->getParent();
                if( is_object($tmp) && $tmp->has_children() ) {
                    $rootnodes = $tmp->get_children();
                }
            }
        }
    }
    else if( $start_page ) {
        $tmp = $hm->sureGetNodeByAlias($start_page);
        if( is_object($tmp) ) {
            if( !$show_root_siblings ) {
                $rootnodes[] = $tmp;
            }
            else {
                $tmp = $tmp->getParent();
                if( is_object($tmp) && $tmp->has_children() ) {
                    $rootnodes = $tmp->get_children();
                }
            }
        }
    }
    else if( $start_level > 1 ) {
        $tmp = $hm->sureGetNodeById($gCms->get_content_id());
        if( $tmp ) {
            $arr = $arr2 = [];
            while( $tmp ) {
                $id = $tmp->get_tag('id');
                if( !$id ) break;
                $arr[$id] = $tmp;
                $arr2[] = $id;
                $tmp = $tmp->get_parent();
            }
            if( ($start_level - 2) < count($arr2) ) {
                $arr2 = array_reverse($arr2);
                $id = $arr2[$start_level-2];
                $tmp = $arr[$id];
                if( $tmp->has_children() ) {
                    // do childrenof this element
                    $rootnodes = $tmp->get_children();
                }
            }
        }
    }
    else if( $childrenof ) {
        $tmp = $hm->sureGetNodeByAlias(trim($childrenof));
        if( is_object($tmp) ) {
            if( $tmp->has_children() ) $rootnodes = $tmp->get_children();
        }
    }
    else if( $items ) {
        if( $nlevels < 1 ) $nlevels = 1;
        $aliases = explode(',',$items);
        $aliases = array_unique($aliases);
        foreach( $aliases as $item ) {
            $item = trim($item);
            $tmp = $hm->sureGetNodeByAlias($item);
            if( $tmp ) $rootnodes[] = $tmp;
        }
    }
    else {
        // start at the top
        if( $hm->has_children() ) $rootnodes = $hm->get_children();
    }

    if( $rootnodes ) { 
        // fill the nodes
        $outtree = [];
        foreach( $rootnodes as $node ) {
            if( Nav_utils::is_excluded($node->get_tag('alias')) ) continue;
            $tmp = Nav_utils::fill_node($node,$deep,$nlevels,$show_all,$collapse);
            if( $tmp ) $outtree[] = $tmp;
        }
        $tpl->assign('nodes',$outtree);
    }

    Nav_utils::clear_excludes();
}

$tpl->display();
debug_buffer('End Navigator default action');
unset($tpl);
#
# EOF
