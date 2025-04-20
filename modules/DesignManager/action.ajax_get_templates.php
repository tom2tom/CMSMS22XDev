<?php

$handlers = ob_list_handlers();
for ($cnt = 0; $cnt < count($handlers); $cnt++) { ob_end_clean(); }

$userid = get_userid();
$modname = $this->GetName();
$tpl = $smarty->CreateTemplate("module_file_tpl:$modname;ajax_get_templates.tpl",null,$modname,$smarty);
try {
    $tmp = get_parameter_value($_REQUEST,'filter');
    $filter = json_decode($tmp,TRUE);
    $tpl->assign('tpl_filter',$filter);
    if( !$this->CheckPermission('Modify Templates') ) $filter[] = 'e:'.$userid;

    $tpl_query = new CmsLayoutTemplateQuery($filter);
    $templates = $tpl_query->GetMatches();
    if( $templates ) {
        $tpl->assign('templates',$templates);
        $tpl_nav = array();
        $tpl_nav['pagelimit'] = $tpl_query->limit;
        $tpl_nav['numpages'] = $tpl_query->numpages;
        $tpl_nav['numrows'] = $tpl_query->totalrows;
        $tpl_nav['curpage'] = (int)($tpl_query->offset / $tpl_query->limit) + 1;
        $tpl->assign('tpl_nav',$tpl_nav);
    }

    $designs = CmsLayoutCollection::get_all();
    if( $designs ) {
        $tpl->assign('list_designs',$designs);
        $tmp = array();
        for( $i = 0; $i < count($designs); $i++ ) {
            $tmp['d:'.$designs[$i]->get_id()] = $designs[$i]->get_name();
            $tmp2[$designs[$i]->get_id()] = $designs[$i]->get_name();
        }
        $tpl->assign('design_names',$tmp2);
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
        $tpl->assign('list_types',$tmp2);
        $tpl->assign('list_all_types',$tmp3); // no sorting needed
    }
    else {
        $tpl->assign('list_types',[]);
        $tpl->assign('list_all_types',[]);
    }

    $locks = \CmsLockOperations::get_locks('template');
    $tpl->assign('have_locks',$locks ? count($locks) : 0);
    $tpl->assign('lock_timeout', $this->GetPreference('lock_timeout'));
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
