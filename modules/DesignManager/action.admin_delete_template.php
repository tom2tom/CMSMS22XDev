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
  $tpl_ob = CmsLayoutTemplate::load($params['tpl']);
  if( $tpl_ob->get_owner_id() != get_userid() && !$this->CheckPermission('Modify Templates') ) {
    throw new CmsException($this->Lang('error_permission'));
  }

  if( isset($params['submit']) ) {
    if( !isset($params['check1']) || !isset($params['check2']) ) {
      $this->ShowErrors($this->Lang('error_notconfirmed'));
    }
    else {
      $tpl_ob->delete();
      $this->SetMessage($this->Lang('msg_template_deleted'));
      $this->RedirectToAdminTab();
    }
  }

  $modname = $this->GetName();
  $tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_delete_template.tpl",null,$modname,$smarty);

  // find the number of 'pages' that use this template.
  $query = 'SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'content WHERE template_id = ?';
  $n = $db->GetOne($query,[$tpl_ob->get_id()]);
  $tpl->assign('page_usage',$n);

  $out = [];
  $out[0] = $this->Lang('prompt_none');
  $cats = CmsLayoutTemplateCategory::get_all();
  if( is_array($cats) && count($cats) ) {
    foreach( $cats as $one ) {
      $out[$one->get_id()] = $one->get_name();
    }
  }
  $tpl->assign('category_list',$out);

  $types = CmsLayoutTemplateType::get_all();
  if( is_array($types) && count($types) ) {
    $out = [];
    foreach( $types as $one ) {
      $out[$one->get_id()] = $one->get_langified_display_value();
    }
    $tpl->assign('type_list',$out);
  }

  $designs = CmsLayoutCollection::get_all();
  if( $designs ) {
    $out = [];
    foreach( $designs as $one ) {
      $out[$one->get_id()] = $one->get_name();
    }
    $tpl->assign('design_list',$out);
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

  $tpl->assign('tpl',$tpl_ob);
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
