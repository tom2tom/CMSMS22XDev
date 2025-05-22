<?php
#-------------------------------------------------------------------------
# Module DesignManager action
# (c) 2012 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Manage Designs') ) return;

$this->SetCurrentTab('designs');
if( isset($params['cancel']) ) {
    $this->SetMessage($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

$design = null; // no object
try {
    if( !isset($params['design']) || $params['design'] == '' ) {
        $design= new CmsLayoutCollection();
// no name yet $design->set_name('New Design');
    }
    else {
        $design = CmsLayoutCollection::load($params['design']);
    }

    try {
        if( isset($params['submit']) || isset($params['apply']) || (isset($params['ajax']) && $params['ajax'] == '1') ) {
            $design->set_name($params['name']);
            $design->set_description($params['description']);
            $tpl_assoc = [];
            if( isset($params['assoc_tpl']) ) $tpl_assoc = $params['assoc_tpl'];
            $design->set_templates($tpl_assoc);

            $css_assoc = [];
            if( isset($params['assoc_css']) ) $css_assoc = $params['assoc_css'];
            $design->set_stylesheets($css_assoc);
            $design->save();

            if( isset($params['submit']) ) {
                $this->SetMessage($this->Lang('msg_design_saved'));
                $this->RedirectToAdminTab();
            }
            else {
                echo $this->ShowMessage($this->Lang('msg_design_saved'));
            }
        }
    }
    catch( Exception $e ) {
        echo $this->ShowErrors($e->GetMessage());
    }

    $modname = $this->GetName();
    $tpl = $smarty->CreateTemplate("module_file_tpl:$modname;admin_edit_design.tpl",null,$modname,$smarty);

    $templates = CmsLayoutTemplate::get_editable_templates(get_userid());
    if( $templates ) {
        usort($templates,function($a,$b) {
                return strcasecmp($a->get_name(),$b->get_name());
            });
        $tpl->assign('all_templates',$templates);
    }

    $stylesheets = CmsLayoutStylesheet::get_all();
    if( $stylesheets ) {
        usort($stylesheets,function($a,$b) {
                return strcasecmp($a->get_name(),$b->get_name());
            });
        $out = [];
        $out2 = [];
        for( $i = 0,$n = count($stylesheets); $i < $n; $i++ ) {
            $out[$stylesheets[$i]->get_id()] = $stylesheets[$i]->get_name();
            $out2[$stylesheets[$i]->get_id()] = $stylesheets[$i];
        }
        $tpl->assign('list_stylesheets',$out);
        $tpl->assign('all_stylesheets',$out2);
    }

    if( $design->get_id() > 0 ) {
        CmsAdminThemeBase::GetThemeObject()->SetSubTitle($this->Lang('edit_design').': '.$design->get_name()." ({$design->get_id()})");
    }
    else {
        CmsAdminThemeBase::GetThemeObject()->SetSubTitle($this->Lang('new_design'));
    }

    $tpl->assign('manage_stylesheets',$this->CheckPermission('Manage Stylesheets'));
    $tpl->assign('manage_templates',$this->CheckPermission('Modify Templates'));
    $tpl->assign('design',$design);

    $tpl->display();
}
catch( CmsException $e ) {
    $this->SetError($e->GetMessage());
    $this->RedirectToAdminTab();
}

#
# EOF
#
?>
