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

$tpl = $smarty->CreateTemplate($this->GetTemplateResource($template),$cache_id,$compile_id,$smarty);
if( !$tpl->IsCached() ) {
    $inline = false;
    if( isset( $params['inline'] ) ) {
        $inline = cms_to_bool(trim($params['inline']));
    }
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

    $searchtext = $this->GetPreference('searchtext','');
    if( !$searchtext ) {
        $searchtext = lang_by_realm('AdminSearch','placeholder_search_text');
        if( $searchtext && strpos($searchtext,'missing in the language') !== false ) {
            $searchtext = '';
        }
    }
    $ph = ( $searchtext ) ? "placeholder=\"$searchtext\" " : '';
    // Variable named hogan in honor of moorezilla's Rhodesian Ridgeback :) https://forum.cmsmadesimple.org/index.php/topic,9580.0.html
    // some of it is only for backwards compatibility.
    $hogan = "{$ph}onfocus=\"if(this.value==this.defaultValue) this.value='';\""." onblur=\"if(this.value=='') this.value=this.defaultValue;\"";
    $submittext = (!empty($params['submit'])) ? $params['submit'] : $this->Lang('searchsubmit');
    $searchtext = (isset($params['searchtext'])) ? $params['searchtext'] : '';

    $tpl->assign('search_actionid',$id);
    $tpl->assign('searchtext',$searchtext);
    $tpl->assign('destpage',$returnid);
    $tpl->assign('form_method',$is_method);
    $tpl->assign('inline',$inline);
    $tpl->assign('startform',$this->CreateFormStart($id,'dosearch',$returnid,$is_method,'',$inline)); // TODO $hidden as form $params
    $tpl->assign('label','<label for="'.$id.'searchinput">'.$this->Lang('search').'</label>');
    $tpl->assign('searchprompt',$this->Lang('search'));
//  $tpl->assign('inputbox',$this->CreateInputText($id, 'searchinput', $searchtext, 20, 50, $hogan));
//  $tpl->assign('submitbutton',$this->CreateInputSubmit($id, 'submit', $submittext));
    $tpl->assign('submittext',$submittext);
    $tpl->assign('hogan',$hogan);
    $tpl->assign('endform',$this->CreateFormEnd());

    $hidden = '';
    if( $origreturnid != $returnid ) $hidden .= $this->CreateInputHidden($id, 'origreturnid', $origreturnid);
    if( isset( $params['modules'] ) ) $hidden .= $this->CreateInputHidden( $id, 'modules', trim($params['modules']) );
    if( isset( $params['detailpage'] ) ) $hidden .= $this->CreateInputHidden( $id, 'detailpage', trim($params['detailpage']) );
    foreach( $params as $key => $value ) {
        if( preg_match( '/^passthru_/', $key ) > 0 ) $hidden .= $this->CreateInputHidden($id,$key,$value);
    }

    if( $hidden ) $tpl->assign('hidden',$hidden);
}
$tpl->display();
