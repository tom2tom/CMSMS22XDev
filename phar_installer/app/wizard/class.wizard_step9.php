<?php

namespace cms_autoinstaller;

use __appbase\session;
use cms_autoinstaller\wizard_step;
use cms_config;
use cms_mailer;
use Exception;
use ModuleOperations;
use RuntimeException;
use function __appbase\endswith;
use function __appbase\get_app;
use function __appbase\lang;
use function __appbase\smarty;
use function cmsms;

class wizard_step9 extends wizard_step
{
    protected function process()
    {
        // nothing here
    }

    private function do_upgrade($version_info)
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',900));

        $this->connect_to_cmsms($destdir);

        // upgrade modules
        $this->message(lang('msg_upgrademodules'));
        $modops = ModuleOperations::get_instance();
        $allmodules = $modops->FindAllModules();
        foreach( $allmodules as $name ) {
            // we force all system modules to be loaded, if it's a system module
            // and needs upgrade, then it should automagically upgrade.
            // additionally, upgrade any specific modules specified by the upgrade routine.
            if( $modops->IsSystemModule($name) || $modops->IsQueuedForInstall($name) ) {
                $this->verbose(lang('msg_upgrade_module',$name));
                $module = $modops->get_module_instance($name,'',TRUE);
                if( !is_object($module) ) {
                    $this->error("FATAL ERROR: could not load module {$name} for upgrade");
                }
            }
        }

        // clear the cache
        cmsms()->clear_cached_files();
        $this->message(lang('msg_clearedcache'));

        // write protect config.php
        @chmod("$destdir/config.php",0444);  //TODO 0440 better c.f. global_umask site-preference

        audit('','CMSMS version','Upgraded to '.CMS_VERSION);

        // set the finished message.
        if( $app->has_custom_destdir() ) { // || !$app->in_phar()
            //TODO determine actual link URLs and use normal message
            $this->set_block_html('bottom_nav',lang('finished_custom_upgrade_msg'));
        }
        else {
            $root_url = $app->get_root_url();
            if( endswith($root_url,'/') ) $root_url = rtrim($root_url,' /');
            $admin_url = $root_url.'/admin';
            $this->set_block_html('bottom_nav',lang('finished_upgrade_msg',$root_url,$admin_url));
        }
    }

    private function do_install()
    {
        // create tmp directories
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',901));
        $siteinfo = $this->get_wizard()->get_data('siteinfo');
        if( !$siteinfo ) throw new Exception(lang('error_internal',902));

        $this->message(lang('install_createtmpdirs'));
        @mkdir($destdir.'/tmp/cache',0777,TRUE); //TODO 0770 better c.f. global_umask site-preference ALSO DONE IN STEP 8 do_install()
        @mkdir($destdir.'/tmp/templates_c',0777,TRUE); //ALSO DONE IN STEP 8 do_install()

        // install modules
        $this->message(lang('install_modules'));
        $this->connect_to_cmsms($destdir);
        $modops = cmsms()->GetModuleOperations();
        $allmodules = $modops->FindAllModules();
        foreach( $allmodules as $name ) {
            // we force all system modules to be loaded, if it's a system module
            // and needs upgrade, then it should automagically upgrade.
            if( $modops->IsSystemModule($name) ) {
                $this->verbose(lang('install_module',$name));
                $module = $modops->get_module_instance($name,'',TRUE);
            }
        }

        // write protect config.php
        @chmod("$destdir/config.php",0444); // TODO 0440 better c.f. global_umask site-preference

        $root_url = $app->get_root_url();
        if( endswith($root_url,'/') ) $root_url = rtrim($root_url,' /');
        $admin_url = $root_url.'/admin';
        $adminacct = $this->get_wizard()->get_data('adminaccount');

        if( is_array($adminacct) && !empty($adminacct['emailaccountinfo']) && !empty($adminacct['emailaddr']) ) {
            try {
                $mailer = new cms_mailer();
//              $mailer->SetFrom(some mailto:...); //help to avoid spam tagging
                $mailer->SetFromName(lang('emailsender')); //ditto
                $mailer->AddAddress($adminacct['emailaddr']);
                $mailer->SetSubject(lang('email_accountinfo_subject'));
                if( $app->in_phar() ) {
                    $body = lang('email_accountinfo_message',
                                 $root_url,
                                 $destdir,
                                 $adminacct['username'],
                                 $admin_url);
                }
                else {
                    $body = lang('email_accountinfo_message_exp',
                                 $root_url,
                                 $adminacct['username'],
                                 $admin_url);
                }
                $body = html_entity_decode($body,ENT_QUOTES);
                $mailer->SetBody($body);
                if( $mailer->Send() ) {
                    $this->message(lang('send_admin_email'));
                }
                else {
                    $this->error(lang('error_sendingmail').': '.$mailer->GetErrorInfo());
                }
            }
            catch( Exception $e ) {
                $this->error(lang('error_sendingmail').': '.$e->GetMessage());
            }

        }

        // todo: set initial preferences.

        audit('','CMSMS version','Installed '.CMS_VERSION);

        cmsms()->clear_cached_files();
        $this->message(lang('msg_clearedcache'));

        // set the finished message.
        if( $app->has_custom_destdir() ) { // || !$app->in_phar()
            //TODO determine actual link URLs and use normal message
            // find the common part of the SCRIPT_FILENAME and the destdir
            // /var/www/phar_installer/index.php
            if( $root_url ) {
                $msg = lang('finished_install_msg',$root_url,$admin_url);
            }
            else {
                $msg = lang('finished_custom_install_msg');
            }
            $this->set_block_html('bottom_nav',$msg);
        }
        else {
            $this->set_block_html('bottom_nav',lang('finished_install_msg',$root_url,$admin_url));
        }
    }

    private function do_freshen()
    {
        // create tmp directories
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',903));
        $this->message(lang('install_createtmpdirs'));
        @mkdir($destdir.'/tmp/cache',0777,TRUE); // TODO 0770 better c.f. global_umask site-preference
        @mkdir($destdir.'/tmp/templates_c',0777,TRUE);

        // write protect config.php
        @chmod("$destdir/config.php",0444); // TODO 0440 better c.f. global_umask site-preference

        // clear the cache
        $this->connect_to_cmsms($destdir);
        cmsms()->clear_cached_files();
        $this->message(lang('msg_clearedcache'));

        audit('','CMSMS version','Refreshed '.CMS_VERSION);

        // set the finished message.
        if( $app->has_custom_destdir() ) {
            $this->set_block_html('bottom_nav',lang('finished_custom_freshen_msg'));
        }
        else {
            $root_url = $app->get_root_url();
            if( endswith($root_url,'/') ) $root_url = rtrim($root_url,' /');
            $admin_url = $root_url.'/admin';
            $this->set_block_html('bottom_nav',lang('finished_freshen_msg',$root_url,$admin_url ));
        }
    }

    private function connect_to_cmsms($destdir)
    {
        if( is_file("$destdir/lib/include.php") ) {
            $app = get_app();
            // this loads the standard CMSMS stuff, except smarty cuz it's already done.
            // we do this here because both upgrade and install stuff needs it.
            // NOTE in this connection, we don't disable database loading
            global $CMS_INSTALL_PAGE,$DONT_LOAD_SMARTY,$CMS_VERSION;
            $CMS_INSTALL_PAGE = 1;
            $DONT_LOAD_SMARTY = 1;
            $CMS_VERSION = $app->get_dest_version();
            if( $app->in_phar() ) {
                global $CMS_PHAR_INSTALLER;
                $CMS_PHAR_INSTALLER = 1; //TODO used only to block core Smarty use c.f. $DONT_LOAD_SMARTY
            }
            // setup and initialize the cmsms API's
            // note DONT_LOAD_DB and DONT_LOAD_SMARTY are used.
            require_once "$destdir/lib/include.php";
            // $config does [did?] not define this when installer is running.
            if( !defined('CMS_DB_PREFIX') ) {
                $config = cms_config::get_instance();
                define('CMS_DB_PREFIX',$config['db_prefix']);
            }
        }
        else {
            throw new RuntimeException('Could not find include.php file in destination');
        }
    }

    protected function display()
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',911));

        $wiz = $this->get_wizard();
        // display the template right off the bat.
        parent::display();
        $smarty = smarty();
        $smarty->assign('back_url',$wiz->prev_url())
          ->display('wizard_step9.tpl');

        // here, we do the action-specific stuff.
        try {
            $action = $wiz->get_data('action');
            switch( $action ) {
             case 'upgrade':
                 $tmp = $wiz->get_data('version_info'); // populated only for refreshes & upgrades
                 if( is_array($tmp) && count($tmp) ) {
                     $this->do_upgrade($tmp);
                     break;
                 }
                 else {
                     throw new Exception(lang('error_internal',920));
                 }
                 //no break here
             case 'freshen':
                 $this->do_freshen();
                 break;
             case 'install':
                 $this->do_install();
                 break;
             default:
                 throw new Exception(lang('error_internal',921));
            }

            // clear the session.
            $sess = session::get();
            $sess->clear();

            $this->finish();
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }

        $app->cleanup();
    }

} // end of class

?>
