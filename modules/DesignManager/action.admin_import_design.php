<?php
#-------------------------------------------------------------------------
# Module DesignManager action: admin_import_design
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

$step = ( isset($params['step']) ) ? (int)$params['step'] : 1;

try {
  switch( $step ) {
  case 1: // select and upload a source-file via input-file element
    try {
      if( isset($params['next1']) ) { //submit-button name
        // check for uploaded file
        $key = $id.'import_xml_file';
        if( !isset($_FILES[$key]) || $_FILES[$key]['name'] == '' ) {
          throw new RuntimeException($this->Lang('error_nofileuploaded'));
        }
        if( $_FILES[$key]['error'] != 0 || $_FILES[$key]['tmp_name'] == '' || $_FILES[$key]['type'] == '') {
          throw new RuntimeException($this->Lang('error_uploading','xml'));
        }
        if( $_FILES[$key]['type'] != 'text/xml' ) {
           throw new RuntimeException($this->Lang('error_upload_filetype',$_FILES[$key]['type']));
        }
        $fn = $_FILES[$key]['tmp_name'];
        // test for old-format design with all/most data-values base64-encoded
        $fh = fopen($fn,'rb');
        if( !$fh ) {
          throw new RuntimeException($mod->Lang('error_fileopen',$xmlfile));
        }
        $str = fread($fh,1500);
        fclose($fh);
        $oldflag = strpos($str, '<!ELEMENT design' !== FALSE) &&
          (!preg_match('~<dtdversion>(.*?)</dtdversion>~',$str,$matches) ||
           version_compare($matches[1],'1.8') < 0);
        if( $oldflag ) {
          $raw = file_get_contents($fn);
          if( $raw ) {
            $plain = preg_replace_callback('~<([a-zA-Z_]+?)><!\[CDATA\[([a-zA-Z0-9+/=]*)\]\]>~', function($matches) {
              // TODO reader-class needs to decode all fdata values in old-format xml (i.e. no 'binary' prop in xml)
              if( $matches[1] != 'fdata' ) { // relevant element in $matches[1] e.g. not endswith 'data' || not an image-file URL
                if( $matches[2] ) {
                  $tmp = base64_decode($matches[2]);
                  if( 1 ) { // TODO is all text - UTF8 alphanum?, ascii 20 to 7e \t \r \n WEAK !!
                    return str_replace($matches[2],$tmp,$matches[0]);
                  }
                }
              }
              return $matches[0];
            }, $raw);
            $bn = preg_replace('~\.xml$~i','',basename($fn));
            $fn = PUBLIC_CACHE_LOCATION . DIRECTORY_SEPARATOR . $bn . '__.xml'; // work with this version
            file_put_contents($fn,$plain);
          }
          else {
            throw new RuntimeException("Design reader failed to read $fn");
          }
        }

        $reader = dm_reader_factory::get_reader($fn);
        $reader->validate(); // throws upon error

        // move uploaded|decoded file to temporary location
        $tmpfile = tempnam(PUBLIC_CACHE_LOCATION,'dm_');
        if( $tmpfile === FALSE ) {
          throw new RuntimeException($this->Lang('error_create_tempfile'));
        }
        if( !$oldflag ) {
          move_uploaded_file($fn,$tmpfile);
        }
        else {
          unlink($_FILES[$key]['tmp_name']);
          @rename($fn,$tmpfile);
        }
        // redirect back to here for step2.
        $this->Redirect($id,'admin_import_design',$returnid,['step'=>2,'tmpfile'=>$tmpfile]);
      }
    }
    catch( Exception $e ) {
      $this->ShowErrors($e->GetMessage()); //TODO ensure this works
    }

    $modname = $this->GetName();
    $tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_import_design.tpl",null,$modname,$smarty);
    $tpl->display();
    break;

  case 2: // preview what's been read and now is offered for import
    //TODO UI and operations to support user-choice : refresh | upgrade | parallel-install (as now)
    //TODO requirements-check and consequent UI display 
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

      $dname = $design_info['name'];
      try {
        $design = CmsLayoutCollection::load($dname);
        $currentname = $dname;
        $currentversion = $design->get_version();
      }
      catch(Exception $e) {
        $currentname = '';
      }
      $importversion = !empty($design_info['version']) ? $design_info['version'] : lang('none');
      if( !empty($design_info['requires']) ) {
        $importrequires = [];
        foreach( $design_info['requires'] as $dep => $version ) {
          //TODO check if requirement met, if not arrange for 'red' display
          $importrequires[] = ($version) ? $dep.' '.htmlspecialchars($version) : $dep;
        }
      }
      else {
        $importrequires = lang('none');
      }
      if( !empty($design_info['notes']) ) {
         //TODO format e.g. strip tags, newlines to <br>, extra blank lines etc
         $importnotes = str_replace(["\r\n","\n"],['<br>','<br>'],ltrim($design_info['notes']," \n\r"));
      }
      else {
        $importnotes = lang('none');
      }
      if( empty($params['newname']) ) {
        // suggest a new name for the design/theme
        $newname = CmsLayoutCollection::suggest_name($dname);
      }
      else {
        $newname = $params['newname']; // doing a re-run
      }
      $modname = $this->GetName();
      $tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_import_design2.tpl",null,$modname,$smarty);
      $tpl->assign('tmpfile',$tmpfile);
      if ($currentname) {
        $tpl->assign('currentname',$currentname);
        $tpl->assign('currentversion',$currentversion);
      }
      $tpl->assign('cms_version',CMS_VERSION);
      $tpl->assign('design_info',$design_info);
      $tpl->assign('importversion',$importversion); //might be empty
      $tpl->assign('importrequires',$importrequires); //might be empty
      $tpl->assign('importnotes',$importnotes); //might be empty
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
  } // steps switch
}
catch( CmsException $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}
?>
