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

try {
    if( !isset($params['design']) ) {
        throw new CmsException($this->Lang('error_missingparam'));
    }
    $design = CmsLayoutCollection::load($params['design']);

    $can_delete_stylesheets = $this->CheckPermission('Manage Stylesheets');
    $can_delete_templates = $this->CheckPermission('Modify Templates');

    if( isset($params['submit']) ) {
        if( !isset($params['confirm_delete1']) || $params['confirm_delete1'] != 'yes' ||
            !isset($params['confirm_delete2']) || $params['confirm_delete2'] != 'yes') {
            $this->SetError($this->Lang('error_notconfirmed'));
            $this->RedirectToAdminTab();
        }

        if( isset($params['delete_stylesheets']) && $can_delete_stylesheets ) {
            $css_id_list = $design->get_stylesheets();
            if( is_array($css_id_list) && count($css_id_list) ) {
                // get the designs to which these stylesheets are attached
                $css_list = CmsLayoutStylesheet::load_bulk($css_id_list);
                if( $css_list && is_array($css_list) ) {
                    foreach( $css_list as &$css ) {
                        $x = $css->get_designs();
                        if( is_array($x) && count($x) == 1 && $x[0] == $design->get_id() ) {
                            // it's orphaned
                            $css->delete();
                        }
                    }
                    unset($css);
                }
            }
        }

        if( isset($params['delete_templates']) && $can_delete_templates ) {
            $tpl_id_list = $design->get_templates();
            if( is_array($tpl_id_list) && count($tpl_id_list) ) {
                $templates = CmsLayoutTemplate::load_bulk($tpl_id_list);
                if( $templates && is_array($templates) ) {
                    foreach( $templates as &$tpl ) {
                        $x = $tpl->get_designs();
                        if( is_array($x) && count($x) == 1 && $x[0] == $design->get_id() ) {
                            // it's orphaned
                            $tpl->delete();
                        }
                    }
                    unset($tpl);
                }
            }
        }

        $nm = $design->get_name();
        if( $nm !== '' ) {
            $fp = cms_join_path($config['themes_path'],$nm); //OR $config['assets_path'],'designs',$nm
            if( $this->is_dir_unused($fp) ) {
                recursive_delete($fp);
            }
        }

        // done... we 'force' the delete because we loaded the design object
        // before deleting the templates and stylesheets.
        $design->delete(TRUE);
        $this->SetMessage($this->Lang('msg_design_deleted'));
        $this->RedirectToAdminTab();
    }

    $modname = $this->GetName();
    $tpl = $smarty->CreateTemplate("module_file_tpl:$modname;admin_delete_design.tpl", null, $modname, $smarty);

    $tpl->assign('tpl_permission',$can_delete_templates);
    $tpl->assign('css_permission',$can_delete_stylesheets);
    $tpl->assign('design',$design);
    $tpl->display();
}
catch( CmsException $e ) {
    $this->SetError($e->GetMessage());
    $this->RedirectToAdminTab();
}
