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
if( !$this->CheckPermission('Manage Stylesheets') ) return;

$this->SetCurrentTab('stylesheets');
if( isset($params['cancel']) ) {
  if( $params['cancel'] == $this->Lang('cancel') ) {
    $this->SetMessage($this->Lang('msg_cancelled'));
  }
  $this->RedirectToAdminTab();
}

try {
  if( !isset($params['css']) ) throw new CmsException($this->Lang('error_missingparam'));

  $css_ob = CmsLayoutStylesheet::load($params['css']);

  if( isset($params['submit']) ) {
    if( !isset($params['check1']) || !isset($params['check2']) ) {
      echo $this->ShowErrors($this->Lang('error_notconfirmed'));
    }
    else {
      $css_ob->delete();
      $this->SetMessage($this->Lang('msg_stylesheet_deleted'));
      $this->RedirectToAdminTab();
    }
  }

  $modname = $this->GetName();
  $tpl = $smarty->CreateTemplate("module_file_tpl:$modname;admin_delete_css.tpl", null, $modname, $smarty);
  $tpl->assign('css',$css_ob);
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
