<?php

$handlers = ob_list_handlers();
for ($cnt = 0; $cnt < count($handlers); $cnt++) { ob_end_clean(); }

try {
    if( !$this->CheckPermission('Manage Stylesheets') ) throw new \Exception($this->Lang('error_permission'));
    $tmp = get_parameter_value($_REQUEST,'filter');
    if( !$tmp ) throw new \Exception($this->Lang('error_missingparam'));

    $modname = $this->GetName();
    $tpl = $smarty->CreateTemplate("module_file_tpl:$modname;ajax_get_stylesheets.tpl",null,$modname,$smarty);

    $filter = json_decode($tmp,TRUE);
    $tpl->assign('css_filter',$filter);

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

    $css_query = new CmsLayoutStylesheetQuery($filter);
    $csslist = $css_query->GetMatches();
    $tpl->assign('stylesheets',$csslist);
    $css_nav = array();
    $css_nav['pagelimit'] = $css_query->limit;
    $css_nav['numpages'] = $css_query->numpages;
    $css_nav['numrows'] = $css_query->totalrows;
    $css_nav['curpage'] = (int)($css_query->offset / $css_query->limit) + 1;
    $tpl->assign('css_nav',$css_nav);
    $tpl->assign('manage_designs',$this->CheckPermission('Manage Designs'));
    $locks = \CmsLockOperations::get_locks('stylesheet');
    $tpl->assign('have_css_locks',($locks) ? count($locks) : 0 );
    $tpl->assign('lock_timeout', $this->GetPreference('lock_timeout'));

    $tpl->display();
}
catch( Exception $e ) {
    echo '<div class="red">'.$e->GetMessage().'</div>';
}
exit;
