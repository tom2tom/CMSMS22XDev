<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module CMSContentManager action
# (c) 2013 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#-------------------------------------------------------------------------
#END_LICENSE

use CMSContentManager\ContentListBuilder;

if( !isset($gCms) ) exit;

if( isset($params['multisubmit']) && isset($params['multiaction']) &&
    isset($params['multicontent']) && is_array($params['multicontent']) && count($params['multicontent']) > 0 ) {
  list($module,$bulkaction) = explode('::',$params['multiaction'],2);
  if( $module == '' || $module == '-1' || $bulkaction == '' || $bulkaction == '-1' ) {
    $this->SetMessage($this->Lang('error_nobulkaction'));
    $this->RedirectToAdminTab();
  }
  // redirect to special action to handle bulk content stuff.
  $this->Redirect($id,'admin_multicontent',$returnid,
          array('multicontent'=>base64_encode(serialize($params['multicontent'])),
            'multiaction'=>$params['multiaction']));
}

$error = '';
$cm_prettyurls_ok = function() use($config) {
  static $_prettyurls_ok = null;
  if( $_prettyurls_ok === null ) {
    $_prettyurls_ok = ($config['url_rewriting'] != 'none');
  }
  return $_prettyurls_ok;
};

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_pages_tab.tpl",null,$modname,$smarty);

$tpl->assign('prettyurls_ok',$cm_prettyurls_ok());
$tpl->assign('can_add_content',$this->CheckPermission('Add Pages') || $this->CheckPermission('Manage All Content'));
$tpl->assign('can_reorder_content',$this->CheckPermission('Manage All Content'));

// load all the content that this user can display...
// organize it into a tree
$builder = new ContentListBuilder($this);
$curpage = 1;
if( isset($params['curpage']) ) $curpage = (int)$params['curpage'];

//
// handle all of the possible ajaxy/sub actions.
//
$ajax = 0;
if( isset($params['ajax']) ) {
  $ajax = 1;
}
if( isset($params['expandall']) || isset($_GET['expandall']) ) {
  $builder->expand_all();
  $curpage = 1;
}
if( isset($params['collapseall']) || isset($_GET['collapseall']) ) {
  $builder->collapse_all();
  $curpage = 1;
}
if( isset($params['expand']) ) {
  $builder->expand_section($params['expand']);
}
if( isset($params['collapse']) ) {
  $builder->collapse_section($params['collapse']);
  $curpage = 1;
}
if( isset($params['setinactive']) ) {
  $builder->set_active($params['setinactive'],FALSE);
  if( !$res ) $error = $this->Lang('error_setinactive');
}
if( isset($params['setactive']) ) {
  $res = $builder->set_active($params['setactive'],TRUE);
  if( !$res ) $error = $this->Lang('error_setactive');
}
if( isset($params['setdefault']) ) {
  $res = $builder->set_default($params['setdefault'],TRUE);
  if( !$res ) $error = $this->Lang('error_setdefault');
}
if( isset($params['moveup']) ) {
  $res = $builder->move_content($params['moveup'],-1);
  if( !$res ) $error = $this->Lang('error_movecontent');
}
if( isset($params['movedown']) ) {
  $res = $builder->move_content($params['movedown'],1);
  if( !$res ) $error = $this->Lang('error_movecontent');
}
if( isset($params['delete']) ) {
  $res = $builder->delete_content($params['delete']);
  if( $res ) $error = $res;
}

//
// build the display
//

$savedlimit = 0;
$tmp = cms_userprefs::get($modname.'_pages_filter');
if( $tmp ) {
  $tmp = unserialize($tmp);
  if( $tmp ) {
    $builder->set_filter($tmp);
    $savedlimit = $tmp['limit'];
  }
}
if( $savedlimit == 0 ) {
  $allpages = (int)$db->GetOne('SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'content');
  if ($allpages > 50) { $pagelimit = 100; }
  elseif ($allpages > 25) { $pagelimit = 50; }
  elseif ($allpages > 10) { $pagelimit = 25; }
  else { $pagelimit = 10; }
}
else {
  $pagelimit = $savedlimit;
}

$builder->set_pagelimit($pagelimit);
$builder->set_page($curpage);
$editinfo = $builder->get_content_list();
$npages = $builder->get_numpages(); // might differ from $allpages
$tpl->assign('curpage',$curpage);
$tpl->assign('npages',$npages);
$columns = $builder->get_display_columns();
$tpl->assign('columns',$columns);
if( $this->GetPreference('list_namecolumn','menutext') == 'title' ) {
  $tpl->assign('colhdr_page',$this->Lang('colhdr_name'));
}
else {
  $tpl->assign('colhdr_page',$this->Lang('colhdr_menutext'));
}
$tpl->assign('content_list',$editinfo);
$tpl->assign('ajax',$ajax);
if( $error ) $tpl->assign('error',$error);

$opts = array();
if( $this->CheckPermission('Remove Pages') ||
    $this->CheckPermission('Manage All Content') ) {
  bulkcontentoperations::register_function($this->Lang('bulk_delete'),'delete');
}
if( $this->CheckPermission('Manage All Content')) {
  bulkcontentoperations::register_function($this->Lang('bulk_active'),'active');
  bulkcontentoperations::register_function($this->Lang('bulk_inactive'),'inactive');
  bulkcontentoperations::register_function($this->Lang('bulk_cachable'),'setcachable');
  bulkcontentoperations::register_function($this->Lang('bulk_noncachable'),'setnoncachable');
  bulkcontentoperations::register_function($this->Lang('bulk_showinmenu'),'showinmenu');
  bulkcontentoperations::register_function($this->Lang('bulk_hidefrommenu'),'hidefrommenu');
  bulkcontentoperations::register_function($this->Lang('bulk_secure'),'secure');
  bulkcontentoperations::register_function($this->Lang('bulk_insecure'),'insecure');
  bulkcontentoperations::register_function($this->Lang('bulk_setdesign'),'setdesign');
  bulkcontentoperations::register_function($this->Lang('bulk_changeowner'),'changeowner');
}
$opts = bulkcontentoperations::get_operation_list();
$tpl->assign('bulk_options',$opts);

$tpl->display();

?>
