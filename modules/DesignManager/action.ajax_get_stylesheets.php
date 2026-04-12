<?php

$num = count(ob_list_handlers());
for ($cnt = 0; $cnt < $num; $cnt++) { ob_end_clean(); }

try {
    if( !$this->CheckPermission('Manage Stylesheets') ) throw new Exception($this->Lang('error_permission'));
    $tmp = get_parameter_value($_REQUEST,'filter'); //TODO something dynamic from downstream, not just defaults
    if( !$tmp ) throw new Exception($this->Lang('error_missingparam'));
    $filter = json_decode($tmp,true);
    if( !$filter ) throw new Exception($this->Lang('error_missingparam'));

    $modname = $this->GetName();
    $tpl = $smarty->createTemplate("module_file_tpl:$modname;ajax_get_stylesheets.tpl",null,$modname,$smarty);

    $designchoices = [];
    $designs = CmsLayoutCollection::get_all();
    if( $designs ) {
        $tpl->assign('list_designs',$designs);
        for( $i = 0,$n = count($designs); $i < $n; $i++ ) {
            $did = $designs[$i]->get_id();
            $dn = $designs[$i]->get_name();
            $filter['d:'.$did] = $dn;
            $designchoices[$did] = $dn;
        }
        asort($designchoices);
    }
    asort($filter);

    $tpl->assign('css_filter',$filter);
    $tpl->assign('design_names',$designchoices);

    $userid = get_userid();
    $locks = CmsLockOperations::get_locks('stylesheet',0,$userid); //lock(s) held by other users
    $have_locks = $locks && is_array($locks);
    $locks = CmsLockOperations::get_locks('stylesheet',$userid); //lock(s) held by current user
    if( $locks && is_array($locks) ) {
        // grab id's for Smarty tip
        $tmp = [];
        foreach( $locks as $obj ) {
            $tmp[] = $obj['oid'];
        }
        sort($tmp,SORT_NUMERIC);
        $itemids = $this->Lang('prompt_id').'='.implode(',',$tmp); //for tooltip
        $self_locks = true;
    }
    else {
        $self_locks = false;
    }

    $css_nav = [];
    $css_query = new CmsLayoutStylesheetQuery($filter); //TODO dynamic params from downstream, not just defaults
    $sheets = $css_query->GetMatches();
    if( $sheets ) {
        $tpl->assign('stylesheets',$sheets,true);
//        if( $css_query->numpages > 1 ) {
            $css_nav['pagelimit'] = $css_query->limit;
            $css_nav['numpages'] = $css_query->numpages;
            $css_nav['numrows'] = $css_query->totalrows;
            $css_nav['curpage'] = (int)($css_query->offset / $css_query->limit) + 1;
//        }
    }
    $tpl->assign('css_nav',$css_nav,true);
    $tpl->assign('have_css_locks',$have_locks);
    $tpl->assign('have_css_selflocks',$self_locks);
    if( $self_locks ) { $tpl->assign('which_selflocks',$itemids); }
    if( $have_locks ) { $tpl->assign('iconsteal_url',$this->GetModuleURLPath().'/images/steal.png'); }
    $tpl->assign('lock_timeout',$this->GetPreference('lock_timeout'));
//  $tpl->assign('has_add_right',$this->CheckPermission(''Manage Stylesheets') || $this->CheckPermission('TODO'));
    $tpl->assign('userid',$userid);
    $tpl->display();
}
catch( Exception $e ) {
    echo '<div class="red">'.$e->GetMessage().'</div>';
}
exit;
