<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module CMSContentManager action
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#-------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
#END_LICENSE

if( !isset($gCms) ) exit;
$this->SetCurrentTab('pages');

if( isset($params['cancel']) ) {
    $this->SetMessage($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}
if( !$this->CheckPermission('Manage All Content') ) {
    $this->SetError($this->Lang('error_bulk_permission'));
    $this->RedirectToAdminTab();
}
if( !isset($params['multicontent']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
$pagelist = unserialize(base64_decode($params['multicontent']));

$hm = $gCms->GetHierarchyManager();
$modname = $this->GetName();
$userid = get_userid();

$showmore = 0;
if( isset($params['showmore']) ) {
    $showmore = (int) $params['showmore'];
    cms_userprefs::set_for_user($userid,"{$modname}_bulk_showmore",$showmore); // never cleared
}
if( isset($params['submit']) ) {
    if( !isset($params['confirm1']) || !isset($params['confirm2']) ) {
        $this->SetError($this->Lang('error_notconfirmed'));
        $this->RedirectToAdminTab();
    }
    if( !isset($params['design']) || !isset($params['template']) ) {
        $this->SetError($this->Lang('error_missingparam'));
        $this->RedirectToAdminTab();
    }

    // do the real work
    try {
        @set_time_limit(9999);
        ContentOperations::get_instance()->LoadChildren(-1,FALSE,FALSE,$pagelist);

        $i = 0;
        foreach( $pagelist as $pid ) {
            $node = $hm->find_by_tag('id',$pid);
            if( !$node ) continue;
            $content = $node->getContent(FALSE,FALSE,TRUE);
            if( !is_object($content) ) continue;

            $content->SetTemplateId((int)$params['template']);
            $content->SetPropertyValue('design_id',$params['design']);
            $content->SetLastModifiedBy($userid);
            $content->Save();
            $i++;
        }
        if( $i != count($pagelist) ) {
            throw new CmsException('Bulk operation to set design did not adjust all selected pages');
        }
        audit('','CMSContentManager','Changed template and design on '.count($pagelist).' pages');
        $this->SetMessage($this->Lang('msg_bulk_successful'));
        $this->RedirectToAdminTab();
    }
    catch( Exception $e ) {
        audit('','CMSContentManager','Bulk setting design and template failed: '.$e->GetMessage());
        $this->SetError($e->GetMessage());
        $this->RedirectToAdminTab();
    }
}

$displaydata = array();
foreach( $pagelist as $pid ) {
    $node = $hm->find_by_tag('id',$pid);
    if( !$node ) continue;  // this should not happen, but hey.
    $content = $node->getContent(FALSE,FALSE,FALSE);
    if( !is_object($content) ) continue; // this should never happen either

    $rec = array();
    $rec['id'] = $content->Id();
    $rec['name'] = $content->Name();
    $rec['menutext'] = $content->MenuText();
    $rec['owner'] = $content->Owner();
    $rec['alias'] = $content->Alias();
    $displaydata[] = $rec;
}
$tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_bulk_setdesign.tpl",null,$modname,$smarty);

$tpl->assign('showmore',cms_userprefs::get_for_user($userid,"{$modname}_bulk_showmore",0));
$tpl->assign('multicontent',$params['multicontent']);
$tpl->assign('displaydata',$displaydata);
$tpl->assign('alldesigns',CmsLayoutCollection::get_list());
$dflt_design = CmsLayoutCollection::load_default();
$tpl->assign('dflt_design_id',$dflt_design->get_id());

$dflt_tpl_id = -1;
try {
    $dflt_tpl = CmsLayoutTemplate::load_dflt_by_type(CmsLayoutTemplateType::CORE.'::page');
    $dflt_tpl_id = $dflt_tpl->get_id();
}
catch( Exception $e ) {
    // ignore
}
$tpl->assign('dflt_tpl_id',$dflt_tpl_id);
if( $showmore ) {
    $_tpl = CmsLayoutTemplate::template_query(array('as_list'=>1));
    $tpl->assign('alltemplates',$_tpl);
}
else {
    // gotta get the core page template type
    $_type = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::page');
    $_tpl = CmsLayoutTemplate::template_query(array('t:'.$_type->get_id(),'as_list'=>1));
    $tpl->assign('alltemplates',$_tpl);
}

$tpl->display();
?>
