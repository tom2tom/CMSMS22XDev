<?php
if( !isset($gCms) ) exit;
if( CmsApp::get_instance()->is_frontend_request() ) exit;

try {
    $name = get_parameter_value($params,'name');
    $value = get_parameter_value($params,'value');
    $type = get_parameter_value($params,'type','image');
//  $type = 'image'; was intended default ? if so include in previous line

    $profile = $this->get_default_profile();
//  if( $type ) {
        $parms = ['type' => $type];
        $profile = $profile->overrideWith($parms);
//  }
    echo "<div>\n".$this->get_html($name,$value,$profile)."\n</div>";
}
catch( \Exception $e ) {
    echo $this->ShowErrors($e->GetMessage());
}
