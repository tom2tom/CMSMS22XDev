<?php
if (!isset($gCms)) exit;

debug_buffer('', 'Start of Menu Manager Display');

if( isset($params['template']) ) {
    $template = trim($params['template']);
}
else {
    $tpl = CmsLayoutTemplate::load_dflt_by_type('MenuManager::navigation');
    if( !is_object($tpl) ) {
        audit('',$this->GetName(),'No default template found');
        return;
    }
    $template = $tpl->get_name();
}

$cache_id = '|ns'.md5(serialize($params));
$tpl = $smarty->CreateTemplate($this->GetTemplateResource($template),$cache_id,null,$smarty);
$tpl->assign('count',0); // for backwards compat.
if( !$tpl->isCached() ) {
    $hm = $gCms->GetHierarchyManager();
    $deep = 1;
    if( isset($params['loadprops']) && $params['loadprops'] == 0 ) $deep = 0;

    $origdepth = 0;
    $nodelist = array();
    $count = 0;
    $getchildren = true;
    $rootnode = null; //mixed object or objects[]
    $prevdepth = 1;

    if( isset($params['childrenof']) ) {
        $parent = $hm->sureGetNodeByAlias($params['childrenof']);
        if( $parent ) {
            // get the children.
            $children = $parent->GetChildren($deep);
            if( $children && is_array($children) ) {
                if( !is_array($rootnode) ) { $rootnode = array(); }
                foreach( $children as $onechild ) {
                    $obj = $onechild->GetContent();
                    if( is_object($obj) && $obj->Active() &&
                        ($obj->ShowInMenu() || (isset($params['show_all']) && $params['show_all'])) ) {
                        $rootnode[] = $onechild;
                    }
                }
            }
        }
    }
    else if( isset($params['start_page']) || isset($params['start_element']) ) {
        if( isset($params['start_page']) ) {
            $rootnode = $hm->sureGetNodeByAlias($params['start_page']);
        }
        else {
            $rootnode = $hm->getNodeByHierarchy($params['start_element']);
        }
        if( $rootnode ) {
            $content = $rootnode->GetContent();
            if( $content ) {
                if( isset($params['show_root_siblings']) && $params['show_root_siblings'] == '1' ) {
                    // Set original depth first before getting parent node
                    $origdepth = substr_count($rootnode->getHierarchy(), '.') + 1;
// OR               $origdepth = substr_count($content->Hierarchy(), '.') + 1;
                    $rootnode  = $rootnode->getParent();
                    $prevdepth = substr_count($rootnode->getHierarchy(), '.') + 1;
                }
                else {
                    //Show a page if it's active and set to show in menu...
                    //Or if show_all is set and the page isn't a "system" page
                    if( $content->Active() &&
                        ($content->ShowInMenu() || (isset($params['show_all']) && $params['show_all'] == 1 && !$content->IsSystemPage()))) {
                        $origdepth = count(explode('.', $content->Hierarchy()));
                        $this->FillNode($content, $rootnode, $nodelist, $count, $prevdepth, $origdepth, $deep, $params);
                        if( isset($params['number_of_levels']) && $params['number_of_levels'] == '1' ) {
                            $getchildren = false;
                        }
                    }
                }
            }
        }
    }
    else if( isset($params['start_level']) && (int)$params['start_level'] > 1 ) {
        $curcontent = $gCms->get_content_object();
        if( $curcontent ) {
            $properparentpos = $this->nthPos($curcontent->Hierarchy() . '.', '.', (int)$params['start_level'] - 1);
            if( $properparentpos > -1 ) {
                $prevdepth = (int)$params['start_level'];
                $rootnode = $hm->getNodeByHierarchy(substr($curcontent->Hierarchy(), 0, $properparentpos));
            }
        }
    }
    else if( isset($params['items']) ) {
        if( !isset($params['number_of_levels']) ) {
            $getchildren = false;
            $params['number_of_levels'] = 1;
        }

        $items = explode(',', $params['items']);
        if( $items ) {
            foreach ($items as $oneitem) {
                $curnode = $hm->sureGetNodeByAlias(trim($oneitem));
                if( !$curnode ) continue;
                $content = $curnode->GetContent();
                if( !is_object($content) ) continue;
                if( !$content->Active() ) continue;

                if( $getchildren ) {
                    $rootnode[] = $curnode;
                }
                else {
                    $prevdepth = 1;
                    $mnode = $this->FillNode($content, $curnode, $nodelist, $count, $prevdepth, 1, $deep, $params);
                    $mnode->depth = 1;
                }
            }
        }
    }
    else {
        // load all content
        $rootnode = &$hm;
        $prevdepth = 1;
    }


    $showparents = array();

    if( isset($params['collapse']) && $params['collapse'] == '1' ) {
        $newpos = '';
        $content = CmsApp::get_instance()->get_content_object();
        if( $content ) {
            $_list = explode('.',$content->Hierarchy());
            $newpos = '';
            foreach( $_list as $lev ) {
                $newpos .= $lev . '.';
                $showparents[] = $newpos;
            }
        }
    }

    // See if origdepth was ever set...  if not, then get it from the prevdepth set earlier
    if( $origdepth == 0 ) $origdepth = $prevdepth;

    if( isset($rootnode) && $getchildren ) {
        if( $rootnode && is_array($rootnode) ) {
            $first = 1;
            for( $n = 0; $n < count($rootnode); $n++ ) {
                $onenode = $rootnode[$n];
                $content = $onenode->GetContent();
                if( $first ) {
                    $prevdepth = $origdepth = substr_count($onenode->getHierarchy(), '.') + 1;
//OR                $prevdepth = $origdepth = substr_count($content->Hierarchy(), '.') + 1;
                    $first = 0;
                }
                if( $content ) {
                    $mnode = $this->FillNode($content, $onenode, $nodelist, $count, $prevdepth, $origdepth, $deep);
                    if( $n == 0 ) {
                        $mnode->first = 1;
                    }
                    if( $n >= count($rootnode) - 1 ) {
                        $mnode->last = 1;
                    }
                    if( !isset($params['number_of_levels']) ) {
                        $params['number_of_levels'] = 99;
                    }
                    if( $params['number_of_levels'] > 1 ) {
                        // we are getting more than one level.
                        $res = $this->GetChildNodes($onenode,$nodelist,$gCms,$prevdepth,$count,$params,
                                                    $origdepth,$showparents,$deep);
                        if( $res ) $mnode->haschildren = true;
                    }
                }
            }
        }
        else if( $rootnode ) {
            $this->GetChildNodes($rootnode, $nodelist, $gCms, $prevdepth, $count, $params,
                                 $origdepth, $showparents, $deep);
        }
    }

    if( $nodelist ) {
        $tpl->assign('menuparams',$params);
        $tpl->assign('count', count($nodelist));
        $tpl->assign('nodelist', $nodelist);
    }
}

$tpl->display();
debug_buffer('', 'End of Menu Manager Display');
?>
