<?php

namespace CMSMS\jobs;

use cms_siteprefs;
use CMSMS\AdminAlerts\TranslatableAlert;
use CMSMS\Async\Job;
use const CMS_ROOT_PATH;
use const CONFIG_FILE_LOCATION;
use function cms_join_path;
use function lang_by_realm;

class CmsSecurityCheckJob extends Job
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'CmsSecurityCheck';
        $this->recurs = Job::RECUR_DAILY;
    }

    public function fill_description()
    {
        $this->description = lang_by_realm('jobs','securitycheck_description');
    }

    public function execute($time = 0)
    {
        if( !$time ) { $time = time(); }
        $this->record_exec_time($time);
        if( $this->start > $time ) return true;
        $this->force_start = $time;

        // check if config is writable
        if( is_writable(CONFIG_FILE_LOCATION) ) {
            $alert = new TranslatableAlert('Modify Site Preferences');
            $alert->name = __CLASS__.'config'; // so that there can only ever be one alert of this type at a time.
            $alert->msgkey = 'config_writable';
            $alert->priority = $alert::PRIORITY_HIGH;
            $alert->titlekey = 'security_issue';
            $alert->save();
        }

        // check if install file exists
        $pattern = cms_join_path(CMS_ROOT_PATH,'cmsms-*-install.php');
        $files = glob($pattern);
        if( is_array($files) && count($files) > 0 ) {
            $fn = basename($files[0]);
            $alert = new TranslatableAlert('Modify Site Preferences');
            $alert->name = __CLASS__.'install';
            $alert->msgkey = 'installfileexists';
            $alert->msgargs = $fn;
            $alert->priority = $alert::PRIORITY_HIGH;
            $alert->titlekey = 'security_issue';
            $alert->save();
        }

        // check if mail is configured
        // not a security issue... but meh, it saves another Job
        if( !cms_siteprefs::get('mail_is_set',0) ) {
            $alert = new TranslatableAlert('Modify Site Preferences');
            $alert->name = __CLASS__.'mail';
            $alert->msgkey = 'info_mail_notset';
            $alert->priority = $alert::PRIORITY_HIGH;
            $alert->titlekey = 'config_issue';
            $alert->save();
        }

        return true;
    }
}
