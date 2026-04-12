<?php

use CMSMS\FilePickerProfile;

if( !isset($gCms) ) exit;
if( CmsApp::get_instance()->is_frontend_request() ) exit;

try {
    $name = get_parameter_value($params,'name');
    $value = get_parameter_value($params,'value');
    $type = get_parameter_value($params,'type','image');
    $dir = get_parameter_value($params,'dir');
    if( !$dir ) $dir = $config['uploads_path'];
    $userid = get_userid(false);
    $profile = $this->get_default_profile($dir,$userid);
    $parms = ['type' => $type];
    if( $userid < 1 || !check_permission($userid,'Modify Files') ) {
        $parms['can_upload'] = FilePickerProfile::FLAG_NONE;
        $parms['can_delete'] = FilePickerProfile::FLAG_NONE;
        $parms['can_mkdir'] = FilePickerProfile::FLAG_NONE;
    }
    $profile->overrideWith($parms); //TODO other $params[] ?
    echo "<div>\n".$this->get_html($name,$value,$profile)."\n</div>";
}
catch( Exception $e ) {
    $this->ShowErrors($e->GetMessage()); // OR Set... then redirect ?
}
