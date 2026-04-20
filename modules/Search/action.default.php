<?php
if (!isset($gCms)) exit;

if (isset($params['formtemplate'])) {
    $template = trim($params['formtemplate']);
}
else {
    $tpl = CmsLayoutTemplate::load_dflt_by_type('Search::searchform');
    if( !is_object($tpl) ) {
        audit('',$this->GetName().':default','No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}

$cache_id = '|sr'.md5(serialize($params));
$compile_id = $this->GetName();

$tpl = $smarty->createTemplate($this->GetTemplateResource($template),$cache_id,$compile_id,$smarty);
if( !$tpl->IsCached() ) {
    $inline = ( isset($params['inline']) ) ? cms_to_bool(trim($params['inline'])) : false;
    $origreturnid = $returnid;
    if( isset( $params['resultpage'] ) ) {
        $manager = $gCms->GetHierarchyManager();
        $node = $manager->sureGetNodeByAlias($params['resultpage']);
        if (isset($node)) {
            $returnid = $node->getID();
        }
        else {
            $node = $manager->sureGetNodeById($params['resultpage']);
            if (isset($node)) $returnid = $params['resultpage'];
        }
    }
    //Pretty URLs compatibility
    $is_method = (isset($params['search_method']))?'post':'get';

    $searchtext = (!empty($params['searchtext'])) ? $params['searchtext'] : '';
    if( !$searchtext ) {
        $searchtext = $this->GetPreference('searchtext');
    }
    if( !$searchtext ) {
        $searchtext = lang_by_realm('AdminSearch','placeholder_search_text');
        if( $searchtext && strpos($searchtext,'missing in the language') !== false ) {
            $searchtext = '';
        }
    }

    $submittext = (!empty($params['submit'])) ? $params['submit'] : $this->Lang('searchsubmit');

    $parms = [];
    if( $origreturnid != $returnid ) { $parms['origreturnid'] = $origreturnid; }
    if( isset($params['modules']) ) { $parms['modules'] = trim($params['modules']); }
    if( isset($params['detailpage']) ) { $parms['detailpage'] = trim($params['detailpage']); }
    foreach( $params as $key => $value ) {
        if( preg_match('/^passthru_/', $key) ) { $parms[$key] = $value; }
    }

    $tpl->assign('search_actionid',$id);
    $tpl->assign('destpage',$returnid);
    $tpl->assign('form_method',$is_method);
    $tpl->assign('inline',$inline);
    $tpl->assign('startform',$this->CreateFormStart($id,'dosearch',$returnid,$is_method,'',$inline,'',$parms));
    $tpl->assign('label','<label for="'.$id.'searchinput">'.$this->Lang('search').'</label>'); //better in the template, if always used !
    $tpl->assign('searchprompt',$this->Lang('search'));
    $tpl->assign('searchtext',$searchtext);
    $tpl->assign('submittext',$submittext);
    $tpl->assign('endform',$this->CreateFormEnd());
}
$tpl->display();
