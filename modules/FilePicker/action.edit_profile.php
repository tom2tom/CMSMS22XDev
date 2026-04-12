<?php
# BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module FilePicker action
# (c) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#-------------------------------------------------------------------------
# This file is part of FilePicker
# FilePicker is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# FilePicker is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, read the license online at
# https://www.gnu.org/licenses/old-licenses/gpl2.0.html
#-------------------------------------------------------------------------
# END_LICENSE

use CMSMS\FileType;
use FilePicker\Profile;

if( !defined('CMS_VERSION') ) exit;
if( !$this->VisibleToAdminUser() ) exit;

if( isset($params['cancel']) ) $this->RedirectToAdminTab();

try {
    $profile_id = get_parameter_value($params,'pid',0);
    if( $profile_id > 0 ) {
        $profile = $this->_dao->loadById($profile_id);
        if( !$profile ) throw new LogicException('Invalid profile id passed to edit_profile action');
    }
    else {
        $profile = new Profile();
    }

    $clone = !empty($params['clone']);

    if( isset($params['submit']) ) {
        $pparms = array_diff_key($params,['action'=>1,'clone'=>1,'default'=>1,'pid'=>1,'submit'=>1]);
        //TODO some of these might be better as FilePickerProfile::FLAG_* int values
        foreach( ['show_thumbs','show_hidden','can_upload','can_delete','can_mkdir','sort'] as $boolopt ) {
            if( !isset($params[$boolopt]) ) { $pparms[$boolopt] = 0; }
        }
        if( $clone ) {
            $now = time();
            $pparms = ['id' => 0, 'created' => $now, 'modified'=> $now] + $pparms; // clear its id property
        }
        try {
            $profile->overrideWith($pparms);
            $profile->validate();
            $profile = $this->_dao->save($profile);
            if( !empty($params['default']) ) {
                $this->_dao->setDefault($profile);
            }
            elseif( $profile->id == $this->_dao->getDefaultProfileId() ) {
                $this->_dao->clearDefault();
            }
            $this->RedirectToAdminTab();
        }
        catch( FilePicker\ProfileException $e ) {
            $this->ShowErrors($this->Lang($e->GetMessage()));
        }
    }

    $dflt_profile_id = $this->_dao->getDefaultProfileId();
    $default = ($dflt_profile_id > 0 && $dflt_profile_id == $profile->id);

    if( $clone ) {
        $profile->overrideWith(['name'=>$this->Lang('copytemplate',$profile->name)]);
    }

    $choices = [
    //TODO langify these
    FileType::TYPE_IMAGE => 'Images',
    FileType::TYPE_AUDIO => 'Audio files',
    FileType::TYPE_VIDEO => 'Video files',
    FileType::TYPE_MEDIA => 'Media files',
    FileType::TYPE_XML   => 'XML files',
    FileType::TYPE_DOCUMENT => 'Documents',
    FileType::TYPE_ARCHIVE => 'Archives',
//  FileType::TYPE_EXECUTABLE => 'Executable files', TODO allow if some kind of 'advanced' ?
    FileType::TYPE_ANY => 'All types'
    ];

    $modname = $this->GetName();
    $tpl = $smarty->createTemplate("module_file_tpl:$modname;edit_profile.tpl",null,$modname,$smarty);
    $tpl->assign('doclone',$clone);
    $tpl->assign('profile',$profile);
    $tpl->assign('default',$default);
    $tpl->assign('filetype_opts',$choices);
    $tpl->display();
}
catch( CmsInvalidDataException $e ) {
    $this->SetError( $this->Lang( $e->GetMessage() ) );
    $this->RedirectToAdminTab();
}
catch( Exception $e ) {
    $this->SetError( $e->GetMessage() );
    $this->RedirectToAdminTab();
}
