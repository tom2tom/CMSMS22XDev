<?php
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;
$this->SetCurrentTab('installed');

try {
    $mod = get_parameter_value($params,'mod');
    if( !$mod ) {
        $this->SetError($this->Lang('error_missingparams'));
        $this->RedirectToAdminTab();
    }

    $ops = ModuleOperations::get_instance();
    $modinstance = $ops->get_module_instance($mod,'',TRUE);
    if( !is_object($modinstance) ) {
        // uh-oh
        $this->SetError($this->Lang('error_getmodule',htmlspecialchars($mod)));
        $this->RedirectToAdminTab();
    }

    if( isset($params['cancel']) ) {
        $this->RedirectToAdminTab();
    }

    if( isset($params['submit']) ) {
        try {
            if( !isset($params['confirm']) || $params['confirm'] != 1 ) throw new \RuntimeException($this->Lang('error_notconfirmed'));
            $postmsg = $modinstance->UninstallPostMessage();
            if( $postmsg == '' ) $postmsg = $this->Lang('msg_module_uninstalled',$mod);
            $result = $ops->UninstallModule($mod);
            if( $result[0] == FALSE ) throw new \RuntimeException($result[1]);
            $this->SetMessage($postmsg);
            $this->RedirectToAdminTab();
        }
        catch( \Exception $e ) {
            $this->ShowErrors($e->GetMessage());
        }
    }
/* TODO process all ModuleManager templates like this, or none of them
    $tpl = $smarty->CreateTemplate($this->GetTemplateResource('local_uninstall.tpl'));
//    $tpl->assign('mod',$this);
//    $tpl->assign('actionid',$id);
    $tpl->assign('module_name',$modinstance->GetName());
    $tpl->assign('module_version',$modinstance->GetVersion());
    $msg = $modinstance->UninstallPreMessage();
    if( !$msg ) $msg = $this->Lang('msg_module_uninstall');
    $tpl->assign('msg',$msg);
    $tpl->display();
*/
    $smarty->assign('module_name',$modinstance->GetName());
    $smarty->assign('module_version',$modinstance->GetVersion());
    $msg = $modinstance->UninstallPreMessage();
    if( !$msg ) $msg = $this->Lang('msg_module_uninstall');
    $smarty->assign('msg',$msg);
    echo $this->ProcessTemplate('local_uninstall.tpl');
}
catch( \Exception $e ) {
    echo $this->ShowErrors($e->GetMessage());
}
