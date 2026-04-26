<?php

namespace CMSMS\jobs;

use cms_http_request;
use cms_siteprefs;
use CMSMS\AdminAlerts\TranslatableAlert;
use CMSMS\Async\ConditionalJob;
use CMSMS\Async\Job;
use const CMS_DEFAULT_VERSIONCHECK_URL;
use const CMS_VERSION;
use function cmsversion_compare;
use function lang;
use function lang_by_realm;

class CmsVersionCheckJob extends ConditionalJob
{
    const ENABLED_SITEPREF = 'checkversion';

    public function __construct()
    {
        parent::__construct();
        $this->name = 'CmsVersionCheck';
        $this->recurs = Job::RECUR_DAILY;
        $this->testrecurs = Job::RECUR_DAILY;
    }

    public function fill_description()
    {
        $this->description = lang_by_realm('jobs','versioncheck_description');
    }

    public function testRecur($priorexec, $time = 0)
    {
        $enabled = (bool)cms_siteprefs::get(self::ENABLED_SITEPREF,1);
        if( $enabled ) {
            if( $this->recurs != Job::RECUR_DAILY ) {
                $this->recurs = Job::RECUR_DAILY;
                $this->save();
            }
        }
        elseif( $this->recurs == Job::RECUR_DAILY ) {
            $this->recurs = Job::RECUR_NONE_CUSTOM;
            $this->displayrecr = lang('disabled');
            $this->save();
        }
        return true;
    }

    public function testStart($priorexec, $time = 0)
    {
        if( $this->testrecurs == Job::RECUR_DAILY ) {
            return strtotime('+1 day', $priorexec);
        }
        return $priorexec + 28800; //aka 3600*8 more-frequent checks for property reversion
    }

    public function execute($time = 0)
    {
        if( !$time ) { $time = time(); }
        $this->record_exec_time($time);
        if( $this->start > $time ) return true;
        $this->force_start = $time;

        $enabled = (bool)cms_siteprefs::get(self::ENABLED_SITEPREF,1);
        if( $enabled ) { // true unless racy preference-change since test()
            $remote_ver = $this->fetch_latest_cmsms_ver();
            if( $remote_ver && $remote_ver != 'error' && cmsversion_compare(CMS_VERSION, $remote_ver) < 0 ) {
                $alert = new TranslatableAlert(['Modify Site Preferences']);
                $alert->name = 'CMSMS Version Check';
                $alert->titlekey = 'new_version_avail_title';
                $alert->msgkey = 'new_version_avail2';
                $alert->msgargs = [CMS_VERSION, $remote_ver];
                $alert->save();
                audit('','Core','CMSMS version '.$remote_ver.' is available');
            }
        }
        return true;
    }

    private function fetch_latest_cmsms_ver()
    {
        $remote_ver = 'error';
        $req = new cms_http_request(['method' => 'POST','timeout' => 10]);
        $req->send(CMS_DEFAULT_VERSIONCHECK_URL);
        if( $req->getStatus() == 200 ) {
            $remote_ver = trim($req->getResult());
            if( strpos($remote_ver,':') !== FALSE ) {
                list($tmp,$remote_ver) = explode(':',$remote_ver,2);
                $remote_ver = trim($remote_ver);
            }
        }
        return $remote_ver;
    }
}
