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

$step = ( isset($params['step']) ) ? (int)$params['step'] : 1;

try {
  switch( $step ) {
  case 1: // select and upload a source-file via input-file element
    try {
      if( isset($params['next1']) ) { //submit-button name
        // check for uploaded file
        $key = $id.'import_xml_file';
        if( !isset($_FILES[$key]) || $_FILES[$key]['name'] == '' ) {
          throw new CmsException($this->Lang('error_nofileuploaded'));
        }
        if( $_FILES[$key]['error'] != 0 || $_FILES[$key]['tmp_name'] == '' || $_FILES[$key]['type'] == '') {
          throw new CmsException($this->Lang('error_uploading','xml'));
        }
        if( $_FILES[$key]['type'] != 'text/xml' ) {
           throw new CmsException($this->Lang('error_upload_filetype',$_FILES[$key]['type']));
        }

        $reader = dm_reader_factory::get_reader($_FILES[$key]['tmp_name']);
        $reader->validate(); // throws upon error

        // copy uploaded file to temporary location
        $tmpfile = tempnam(TMP_CACHE_LOCATION,'dm_'); //TODO PUBLIC_CACHE_LOCATION
        if( $tmpfile === FALSE ) throw new CmsException($this->Lang('error_create_tempfile'));
        @copy($_FILES[$key]['tmp_name'],$tmpfile);

        // redirect back to here for step2.
        $this->Redirect($id,'admin_import_design',$returnid,['step'=>2,'tmpfile'=>$tmpfile]);
      }
    }
    catch( CmsException $e ) {
      $this->ShowErrors($e->GetMessage()); //TODO ensure this works
    }

    $modname = $this->GetName();
    $tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_import_design.tpl",null,$modname,$smarty);
    $tpl->display();
    break;

  case 2: // preview what's going to be imported
    try {
      if( !isset($params['tmpfile']) ) {
        // bad error, redirect to admin tab i.e. designs list
        $this->SetError($this->Lang('error_missingparam'));
        $this->RedirectToAdminTab();
      }
      $tmpfile = trim($params['tmpfile']);
      if( !file_exists($tmpfile) ) {
        // bad error, redirect to admin tab
        $this->SetError($this->Lang('error_filenotfound',$tmpfile));
        $this->RedirectToAdminTab();
      }

      if( isset($params['next2']) ) { // submit-button name
        if( empty($params['check1']) ) {
          $this->ShowErrors($this->Lang('error_notconfirmed'));
          //fall into re-display
        }
        elseif( empty($params['newname']) ) {
          $this->ShowErrors($this->Lang('error_missingparam'));
        }
        else {
          // redirect back to here for step3.
          $this->Redirect($id,'admin_import_design',$returnid,[
          'step'=>3,
          'tmpfile'=>$tmpfile,
          'newname'=>$params['newname'],
          'newdescription'=>$params['newdescription']
          ]);
        }
      }

      $reader = dm_reader_factory::get_reader($tmpfile);

      $design_info = $reader->get_design_info();
      if( empty($params['newname']) ) {
        // suggest a new name for the design/theme
        $newname = CmsLayoutCollection::suggest_name($design_info['name']);
      }
      else {
        $newname = $params['newname']; // doing a re-run
      }
      $modname = $this->GetName();
      $tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_import_design2.tpl",null,$modname,$smarty);
      $tpl->assign('tmpfile',$tmpfile);
      $tpl->assign('cms_version',CMS_VERSION);
      $tpl->assign('design_info',$design_info);
      $tpl->assign('templates',$reader->get_template_list());
      $tpl->assign('stylesheets',$reader->get_stylesheet_list());
      $tpl->assign('new_name',$newname);
      $tpl->display();
    }
    catch( CmsException $e ) {
      $this->ShowErrors($e->GetMessage());
    }
    break;

  case 3: // do the import
    if( !isset($params['tmpfile']) || !isset($params['newname']) ||
      $params['newname'] == '') {
      // bad error, redirect to admin tab.
      throw new CmsException($this->Lang('error_missingparam'));
    }
    $tmpfile = trim($params['tmpfile']);
    if( !file_exists($tmpfile) ) {
      // bad error, redirect to admin tab.
      throw new CmsException($this->Lang('error_filenotfound',$tmpfile));
    }

    $newname = trim($params['newname']);
    $newdescription = trim($params['newdescription']);

    $reader = dm_reader_factory::get_reader($tmpfile);
    $reader->set_suggested_name($newname);
    $reader->set_suggested_description($newdescription);
    $reader->import();
    $this->SetMessage($this->Lang('msg_design_imported'));
    $this->RedirectToAdminTab();

    break;

  default:
  }
}
catch( CmsException $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}

#
# EOF
#
?>
