<?php

$num = count(ob_list_handlers());
for( $cnt = 0; $cnt < $num; $cnt++ ) { ob_end_clean(); }

$userid = get_userid();
$modname = $this->GetName();
$tpl = $smarty->CreateTemplate("module_file_tpl:$modname;ajax_get_templates.tpl",null,$modname,$smarty);

try {
    $tmp = get_parameter_value($_REQUEST,'filter');
    $filter = json_decode($tmp,TRUE);
    if( !$this->CheckPermission('Modify Templates') ) $filter[] = 'e:'.$userid;

    $tpl_query = new CmsLayoutTemplateQuery($filter);
    $templates = $tpl_query->GetMatches();
    if( $templates ) {
        $tpl->assign('templates',$templates);
        $tpl_nav = [];
        $tpl_nav['pagelimit'] = $tpl_query->limit;
        $tpl_nav['numpages'] = $tpl_query->numpages;
        $tpl_nav['numrows'] = $tpl_query->totalrows;
        $tpl_nav['curpage'] = (int)($tpl_query->offset / $tpl_query->limit) + 1;
        $tpl->assign('tpl_nav',$tpl_nav);
    }

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
    $tpl->assign('tpl_filter',$filter);
    $tpl->assign('design_names',$designchoices);

    $types = CmsLayoutTemplateType::get_all();
    if( $types ) {
        $publictypes = [];
        $typechoices = [];
        for( $i = 0,$n = count($types); $i < $n; $i++ ) {
            $tid = $types[$i]->get_id();
            $publictypes[$tid] = $types[$i]->get_langified_display_value();
            $typechoices[$tid] = $types[$i];
        }
        asort($publictypes);
        $tpl->assign('list_types',$publictypes);
//      asort($typechoices);
        $tpl->assign('list_all_types',$typechoices); // no sorting needed?
    }
    else {
        $tpl->assign('list_types',[]);
        $tpl->assign('list_all_types',[]);
    }

    $locks = CmsLockOperations::get_locks('template',0,$userid); //lock(s) held by other users
    $have_locks = $locks && is_array($locks);
    $locks = CmsLockOperations::get_locks('template',$userid); //lock(s) held by current user
    if( $locks && is_array($locks) ) {
        // grab id's for Smarty tip
        $tmp = [];
        foreach( $locks as $obj ) {
            $tmp[] = $obj['oid'];
        }
        sort($tmp,SORT_NUMERIC);
        $itemids = $this->Lang('prompt_id').'='.implode(',',$tmp); // for tooltip
        $self_locks = true;
    }
    else {
        $self_locks = false;
    }

    $tpl->assign('have_tpl_locks',$have_locks);
    $tpl->assign('have_tpl_selflocks',$self_locks);
    if( $self_locks ) { $tpl->assign('which_selflocks',$itemids); }
    $tpl->assign('lock_timeout',$this->GetPreference('lock_timeout'));
    $tpl->assign('coretypename',CmsLayoutTemplateType::CORE);
    $tpl->assign('manage_templates',$this->CheckPermission('Modify Templates'));
    $tpl->assign('manage_designs',$this->CheckPermission('Manage Designs'));
    $tpl->assign('has_add_right',
        $this->CheckPermission('Modify Templates') ||
        $this->CheckPermission('Add Templates'));
    $tpl->assign('userid',$userid);

   $tpl->display();
}
catch( Exception $e ) {
    echo '<div class="red">'.$e->GetMessage().'</div>';
}
exit;

?>
