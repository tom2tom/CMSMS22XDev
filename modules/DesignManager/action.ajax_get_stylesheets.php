<?php

$num = count(ob_list_handlers());
for ($cnt = 0; $cnt < $num; $cnt++) { ob_end_clean(); }

try {
    if( !$this->CheckPermission('Manage Stylesheets') ) throw new Exception($this->Lang('error_permission'));
    $tmp = get_parameter_value($_REQUEST,'filter');
    if( !$tmp ) throw new Exception($this->Lang('error_missingparam'));

    $userid = get_userid();
    $modname = $this->GetName();
    $tpl = $smarty->CreateTemplate("module_file_tpl:$modname;ajax_get_stylesheets.tpl",null,$modname,$smarty);

    $filter = json_decode($tmp,TRUE);

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

    $css_query = new CmsLayoutStylesheetQuery($filter);
    $csslist = $css_query->GetMatches();
    $tpl->assign('stylesheets',$csslist);
    $css_nav = [];
    $css_nav['pagelimit'] = $css_query->limit;
    $css_nav['numpages'] = $css_query->numpages;
    $css_nav['numrows'] = $css_query->totalrows;
    $css_nav['curpage'] = (int)($css_query->offset / $css_query->limit) + 1;
    $tpl->assign('css_nav',$css_nav);
    $tpl->assign('manage_designs',$this->CheckPermission('Manage Designs'));
    $tpl->assign('have_css_locks',$have_locks);
    $tpl->assign('have_css_selflocks',$self_locks);
    if( $self_locks ) { $tpl->assign('which_selflocks',$itemids); }
    $tpl->assign('lock_timeout',$this->GetPreference('lock_timeout'));
//  $tpl->assign('has_add_right',$this->CheckPermission('X') || $this->CheckPermission('Y'));
    $tpl->assign('userid',$userid);

    $tpl->display();
}
catch( Exception $e ) {
    echo '<div class="red">'.$e->GetMessage().'</div>';
}
exit;
