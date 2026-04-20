<?php
#-------------------------------------------------------------------------
# Module DesignManager action
# (c) 2015 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
if( !$this->CheckPermission('Manage Stylesheets') ) return;

$this->SetCurrentTab('stylesheets');
if( !isset($params['css']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
if( isset($params['cancel']) ) {
  $this->SetMessage($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}

try {
  $orig_css = CmsLayoutStylesheet::load($params['css']);
  if( isset($params['submit']) || isset($params['apply']) ) {
    try {
      $new_css = clone($orig_css);
      $new_css->set_name(trim($params['new_name']));
      $new_css->set_designs([]);
      $new_css->save();

      if( isset($params['apply']) ) {
        $this->SetMessage($this->Lang('msg_stylesheet_copied_edit'));
        $this->Redirect($id,'admin_edit_css',$returnid,['css'=>$new_css->get_id()]);
      }
      else {
        $this->SetMessage($this->Lang('msg_stylesheet_copied'));
        $this->RedirectToAdminTab();
      }
    }
    catch( Exception $e ) {
      $this->ShowErrors($e->GetMessage());
    }
  }

  $modname = $this->GetName();
  $tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_copy_css.tpl",null,$modname,$smarty);

  // build a display
  $designchoices = [];
  $designs = CmsLayoutCollection::get_all();
  if( $designs ) {
    for( $i = 0,$n = count($designs); $i < $n; $i++ ) {
      $designchoices[$designs[$i]->get_id()] = $designs[$i]->get_name();
    }
    asort($designchoices);
  }

  $tpl->assign('css',$orig_css); //after sorting?
  $tpl->assign('design_names',$designchoices);
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
