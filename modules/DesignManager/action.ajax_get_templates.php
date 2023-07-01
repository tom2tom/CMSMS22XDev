<?php

$handlers = ob_list_handlers();
for ($cnt = 0; $cnt < count($handlers); $cnt++) { ob_end_clean(); }

$userid = get_userid();
try {
    $tmp = get_parameter_value($_REQUEST,'filter');
    $filter = json_decode($tmp,TRUE);
    $smarty->assign('tpl_filter',$filter);
    if( !$this->CheckPermission('Modify Templates') ) $filter[] = 'e:'.$userid;

    $tpl_query = new CmsLayoutTemplateQuery($filter);
    $templates = $tpl_query->GetMatches();
    if( $templates ) {
        $smarty->assign('templates',$templates);
        $tpl_nav = array();
        $tpl_nav['pagelimit'] = $tpl_query->limit;
        $tpl_nav['numpages'] = $tpl_query->numpages;
        $tpl_nav['numrows'] = $tpl_query->totalrows;
        $tpl_nav['curpage'] = (int)($tpl_query->offset / $tpl_query->limit) + 1;
        $smarty->assign('tpl_nav',$tpl_nav);
    }

    $designs = CmsLayoutCollection::get_all();
    if( $designs ) {
        $smarty->assign('list_designs',$designs);
        $tmp = array();
        for( $i = 0; $i < count($designs); $i++ ) {
            $tmp['d:'.$designs[$i]->get_id()] = $designs[$i]->get_name();
            $tmp2[$designs[$i]->get_id()] = $designs[$i]->get_name();
        }
        $smarty->assign('design_names',$tmp2);
    }

    $types = CmsLayoutTemplateType::get_all();
    if( $types ) {
        $tmp2 = array();
        $tmp3 = array();
        for( $i = 0; $i < count($types); $i++ ) {
            $n = $types[$i]->get_id();
            $tmp2[$n] = $types[$i]->get_langified_display_value();
            $tmp3[$n] = $types[$i];
        }
        asort($tmp2);
        $smarty->assign('list_types',$tmp2);
        $smarty->assign('list_all_types',$tmp3); // no sorting needed
    }
    else {
        $smarty->assign('list_types',[]);
        $smarty->assign('list_all_types',[]);
    }

    $locks = \CmsLockOperations::get_locks('template');
    $smarty->assign('have_locks',$locks ? count($locks) : 0);
    $smarty->assign('lock_timeout', $this->GetPreference('lock_timeout'));
    $smarty->assign('coretypename',CmsLayoutTemplateType::CORE);
    $smarty->assign('manage_templates',$this->CheckPermission('Modify Templates'));
    $smarty->assign('manage_designs',$this->CheckPermission('Manage Designs'));
    $smarty->assign('has_add_right',
        $this->CheckPermission('Modify Templates') ||
        $this->CheckPermission('Add Templates'));
    $smarty->assign('userid',$userid);

    echo $this->ProcessTemplate('ajax_get_templates.tpl');
}
catch( Exception $e ) {
    echo '<div class="red">'.$e->GetMessage().'</div>';
}
exit;

?>
