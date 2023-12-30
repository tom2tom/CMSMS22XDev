<?php
#Module MicroTiny action
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
if( !cmsms() ) exit;
if( !$this->VisibleToAdminUser() ) return;
$this->SetCurrentTab('settings');

if( isset($params['cancel']) ) {
  // handle cancel
  $this->SetMessage($this->Lang('msg_cancelled'));
  $this->RedirectToAdminTab();
}

try {
  $name = trim(get_parameter_value($params,'profile'));
  if( !$name ) throw new Exception($this->Lang('error_missingparam'));

  // load the profile
  $profile = microtiny_profile::load($name);

  if( isset($params['submit']) ) {
    // handle submit
    foreach( $params as $key => $value ) {
      if( startswith($key,'profile_') ) {
        $key = substr($key,strlen('profile_'));
        $profile[$key] = $value;
      }
    }

    // if name changed and object is a system object, puke
    if( !empty($profile['system']) && $profile['name'] != $name ) {
      throw new CmsInvalidDataException($this->lang('error_cantchangesysprofilename'));
    }

    if( $profile['styler'] == 'sheet' && $profile['dfltstylesheet'] == -1 ) {
      $profile['styler'] = 'One11';
    }

    $profile->save();
    $this->RedirectToAdminTab();
  }

  $smarty->assign('profile',$name);
  $smarty->assign('data',$profile);

  $bp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'js';
  $vals = [];
  $places = glob(cms_join_path($bp,'CMSMSstyles','ui','*'),GLOB_NOESCAPE|GLOB_ONLYDIR);
  foreach( $places as $fp ) {
    $vals[] = basename($fp);
  }
  $places = glob(cms_join_path($bp,'tinymce','skins','ui','*'),GLOB_NOESCAPE|GLOB_ONLYDIR);
  foreach( $places as $fp ) {
    $vals[] = basename($fp);
  }
  foreach( $vals as $name ) {
    $themes[$name] = $name;
  }
  $themes['oxide'] = $this->lang('light');
  $themes['oxide-dark'] = $this->lang('dark');
  $smarty->assign('themes',$themes);

  $vals = [];
  $places = glob(cms_join_path($bp,'CMSMSstyles','content','*'),GLOB_NOESCAPE|GLOB_ONLYDIR);
  foreach( $places as $fp ) {
    $vals[] = basename($fp);
  }
  $places = glob(cms_join_path($bp,'tinymce','skins','content','*'),GLOB_NOESCAPE|GLOB_ONLYDIR);
  foreach( $places as $fp ) {
    $vals[] = basename($fp);
  }
  foreach( $vals as $name ) {
    $stylers[$name] = $name;
  }
  $stylers['default'] = $this->lang('light');
  $stylers['dark'] = $this->lang('dark');
  $stylers['sheet'] = $this->Lang('profile_usesheet');
  $smarty->assign('stylers',$stylers);

  $stylesheets = CmsLayoutStylesheet::get_all(TRUE);
  $stylesheets = array('-1'=>$this->Lang('none')) + $stylesheets;
  $smarty->assign('stylesheets',$stylesheets);

  echo $this->ProcessTemplate('admin_editprofile.tpl');
}
catch( Exception $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}

#
# EOF
#
?>
