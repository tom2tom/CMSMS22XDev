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
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Manage Designs') ) return;

$this->SetCurrentTab('designs');

if( isset($params['cancel']) ) {
  $this->SetMessage($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}
if( isset($params['close']) ) {
  $this->RedirectToAdminTab();
}

if( empty($params['design']) ) {
  $this->SetError($this->Lang('error_missingparam')); //TODO silent if missing due to polling and page-refreshing
  $this->RedirectToAdminTab();
}

$the_design = CmsLayoutCollection::load($params['design']);
if( !$the_design ) {
  $this->SetError(lang('internal_error'));
  $this->RedirectToAdminTab();
}

if( !isset($params['next1']) ) {
  //display the setup page
  $t1 = $the_design->get_created();
  $t2 = $the_design->get_modified();
  $modname = $this->GetName();
  $tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_export_design.tpl",null,$modname,$smarty);
  $tpl->assign('did',$the_design->get_id());
  $tpl->assign('created',$t1);
  if( $t2 > $t1 + 59 ) {
    $tpl->assign('modified',$t2);
  }
  $tpl->assign('pagetitle',$this->Lang('export_title',$the_design->get_name()));
  $tpl->assign('description',$the_design->get_description());
  $tpl->assign('version',$the_design->get_version());
  $tpl->assign('requires',$the_design->get_requires(3));
  $tpl->display();
  return;
}

$reqs = [];
if( !empty($params['requires']) ) {
  // downstream expects array-format(4)
  $arr = preg_split('/ *(\r\n|\n|,) */',$params['requires'],-1,PREG_SPLIT_NO_EMPTY);
  foreach($arr as $dep) {
    if( preg_match('~^\s*([^<>=!\s]+)\s*([<>=!]{1,2})?\s*([a-zA-Z0-9.]+)?\s*$~',$dep,$matches) ) {
      if( isset($matches[2]) && isset($matches[3]) ) {
        $reqs[$matches[1]] = [$matches[2],$matches[3]];
      }
      else {
        $reqs[$matches[1]] = [];
      }
    }
  }
}
// cache without update of recorded design-properties
$name = $the_design->get_name();
$space = 'dm'.hash('crc32',$name); // must be replicated in the exporter class
$_SESSION[$space]['description'] = $params['description'];
$_SESSION[$space]['version'] = $params['version'];
$_SESSION[$space]['requires'] = $reqs;
$_SESSION[$space]['notes'] = $params['notes'];

try {
  // and the work...
  //TODO arrange to access supplementary info
  $exporter = new dm_design_exporter($the_design);
  $xml = $exporter->get_xml();

  // clear any output buffers.
  $num = count(ob_list_handlers());
  for ($cnt = 0; $cnt < $num; $cnt++) { ob_end_clean(); }

  $fn = munge_string_to_url($name);
  // headers
  header('Content-Description: File Transfer');
  header('Content-Type: application/force-download');
  header("Content-Disposition: attachment; filename=$fn.xml");

  // output
  echo $xml;
  exit;
}
catch( Exception $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}
?>
