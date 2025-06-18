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
if( !$this->CheckPermission('Modify Templates') ) {
    // no manage templates permission
    if( !$this->CheckPermission('Add Templates') ) {
        // no add templates permission
        if( !isset($params['tpl']) || !CmsLayoutTemplate::user_can_edit($params['tpl']) ) {
            // no parameter, or no ownership/addt_editors.
            return;
        }
    }
}

$this->SetCurrentTab('templates');
if( !isset($params['tpl']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
if( isset($params['cancel']) ) {
    $this->SetMessage($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

try {
    $orig_tpl = CmsLayoutTemplate::load($params['tpl']);

    if( isset($params['submit']) || isset($params['apply']) ) {

        try {
            $new_tpl = clone($orig_tpl);
            $new_tpl->set_owner(get_userid());
            $new_tpl->set_name(trim($params['new_name']));
            $new_tpl->set_additional_editors([]);

            // only if have manage themes right.
            if( $this->CheckPermission('Modify Designs') ) {
                $new_tpl->set_designs($orig_tpl->get_designs());
            }
            else {
                $new_tpl->set_designs([]);
            }
            $new_tpl->save();

            if( isset($params['apply']) ) {
                $this->SetMessage($this->Lang('msg_template_copied_edit'));
                $this->Redirect($id,'admin_edit_template',$returnid,['tpl'=>$new_tpl->get_id()]);
            }
            else {
                $this->SetMessage($this->Lang('msg_template_copied'));
                $this->RedirectToAdminTab();
            }
        }
        catch( CmsException $e ) {
            echo $this->ShowErrors($e->GetMessage());
        }
    }

    // build a display.
    $modname = $this->GetName();
    $tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_copy_template.tpl",null,$modname,$smarty);

    $tmp = [$this->Lang('prompt_none')];
    $cats = CmsLayoutTemplateCategory::get_all();
    if( is_array($cats) && count($cats) ) {
        foreach( $cats as $one ) {
            $tmp[$one->get_id()] = $one->get_name();
        }
    }
    $tpl->assign('category_list',$tmp);

    $types = CmsLayoutTemplateType::get_all();
    if( is_array($types) && count($types) ) {
        $tmp = [];
        foreach( $types as $one ) {
            $tmp[$one->get_id()] = $one->get_langified_display_value();
        }
        $tpl->assign('type_list',$tmp);
    }

    $designs = CmsLayoutCollection::get_all();
    if( $designs ) {
        $tmp = [];
        foreach( $designs as $one ) {
            $tmp[$one->get_id()] = $one->get_name();
        }
        $tpl->assign('design_list',$tmp);
    }

    $userops = cmsms()->GetUserOperations();
    $allusers = $userops->LoadUsers();
    $tmp = [];
    foreach( $allusers as $one ) {
        $tmp[$one->id] = $one->username;
    }
    if( $tmp ) {
        $tpl->assign('user_list',$tmp);
    }

    $new_name = $orig_tpl->get_name();
    $p = strrpos($new_name,' -- ');
    $n = 2;
    if( $p !== FALSE ) {
        $n = (int)substr($new_name,$p+4)+1;
        $new_name = substr($new_name,0,$p);
    }
    $new_name .= ' -- '.$n;
    $tpl->assign('new_name',$new_name);
    $tpl->assign('tpl',$orig_tpl);

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
