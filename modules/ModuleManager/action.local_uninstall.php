<?php
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;
$this->SetCurrentTab('installed');

try {
    $modname = get_parameter_value($params,'mod');
    if( !$modname ) {
        $this->SetError($this->Lang('error_missingparams'));
        $this->RedirectToAdminTab();
    }

    $ops = ModuleOperations::get_instance();
    $modinstance = $ops->get_module_instance($modname,'',TRUE);
    if( !is_object($modinstance) ) {
        // uh-oh
        $this->SetError($this->Lang('error_getmodule',htmlspecialchars($modname)));
        $this->RedirectToAdminTab();
    }

    if( isset($params['cancel']) ) {
        $this->RedirectToAdminTab();
    }

    if( isset($params['submit']) ) {
        try {
            if( !isset($params['confirm']) || $params['confirm'] != 1 ) throw new \RuntimeException($this->Lang('error_notconfirmed'));
            $postmsg = $modinstance->UninstallPostMessage();
            if( $postmsg == '' ) $postmsg = $this->Lang('msg_module_uninstalled',$modname);
            $result = $ops->UninstallModule($modname);
            if( $result[0] == FALSE ) throw new \RuntimeException($result[1]);
            $this->SetMessage($postmsg);
            $this->RedirectToAdminTab();
        }
        catch( \Exception $e ) {
            $this->ShowErrors($e->GetMessage());
        }
    }

    $myname = $this->GetName();
    $tpl = $smarty->createTemplate("module_file_tpl:$myname;local_uninstall.tpl",null,$myname,$smarty);
    $tpl->assign('module_name',$modinstance->GetName()); // aka $modname
    $tpl->assign('module_version',$modinstance->GetVersion());
    $msg = $modinstance->UninstallPreMessage();
    if( !$msg ) $msg = $this->Lang('msg_module_uninstall');
    $tpl->assign('msg',$msg);
    $tpl->display();
}
catch( \Exception $e ) {
    $this->ShowErrors($e->GetMessage());
}
