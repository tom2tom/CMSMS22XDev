<?php

namespace cms_autoinstaller;

use __appbase\utils;
use cms_autoinstaller\wizard_step;
use Exception;
use function __appbase\lang;
use function __appbase\smarty;

class wizard_step5 extends wizard_step
{
    private $_adminacct;

    public function __construct()
    {
        parent::__construct();
        $this->_adminacct = array('username'=>'admin','emailaddr'=>'','password'=>'','repeatpw'=>'','saltpw'=>1,'emailaccountinfo'=>0);
        $tmp = $this->get_wizard()->get_data('adminaccount');
        if( is_array($tmp) && count($tmp) ) $this->_adminacct = $tmp;
    }

    private function validate($acct)
    {
        if( !isset($acct['username']) || $acct['username'] == '' ) throw new Exception(lang('error_adminacct_username'));
        if( !isset($acct['password']) || $acct['password'] == '' || strlen($acct['password']) < 6 ) {
            throw new Exception(lang('error_adminacct_password'));
        }
        if( !isset($acct['repeatpw']) || $acct['repeatpw'] != $acct['password'] ) {
            throw new Exception(lang('error_adminacct_repeatpw'));
        }
        if( !empty($acct['emailaddr']) && !utils::is_email($acct['emailaddr']) ) {
            throw new Exception(lang('error_adminacct_emailaddr'));
        }
        if( empty($acct['emailaddr']) && $acct['emailaccountinfo'] ) {
            throw new Exception(lang('error_adminacct_emailaddrrequired'));
        }
    }

    protected function process()
    {
        $this->_adminacct['username'] = trim(utils::clean_string($_POST['username']));
        $this->_adminacct['emailaddr'] = trim(utils::clean_string($_POST['emailaddr']));
        $this->_adminacct['password'] = trim(utils::clean_string($_POST['password']));
        $this->_adminacct['repeatpw'] = trim(utils::clean_string($_POST['repeatpw']));
        $this->_adminacct['saltpw'] = 1;
        if( isset($_POST['saltpw']) ) $this->_adminacct['saltpw'] = (int)$_POST['saltpw'];
        $this->_adminacct['emailaccountinfo'] = 0;
        if( isset($_POST['emailaccountinfo']) ) $this->_adminacct['emailaccountinfo'] = (int)$_POST['emailaccountinfo'];

        $this->get_wizard()->set_data('adminaccount',$this->_adminacct);
        try {
            $this->validate($this->_adminacct);
            $url = $this->get_wizard()->next_url();
            utils::redirect($url);
        }
        catch( Exception $e ) {
            $smarty = smarty();
            $smarty->assign('error',$e->GetMessage());
        }
    }

    protected function display()
    {
        parent::display();
        $smarty = smarty();
        $smarty->assign('verbose',$this->get_wizard()->get_data('verbose',0))
          ->assign('account',$this->_adminacct)
          ->assign('yesno',array('0'=>lang('no'),'1'=>lang('yes')))
          ->display('wizard_step5.tpl');
        $this->finish();
    }

} // end of class

?>
