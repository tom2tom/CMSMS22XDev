<?php

namespace CMSMS\jobs;

use cms_siteprefs;
use CmsApp;
use CMSMS\Async\ConditionalJob;
use CMSMS\Async\Job;
use const CMS_DB_PREFIX;
use function lang;
use function lang_by_realm;

//see also ReduceAdminlogTask which consolidates matching log entries
class PruneAdminlogJob extends ConditionalJob
{
    const LIFETIME_SITEPREF = 'adminlog_lifetime';

    public function __construct()
    {
        parent::__construct();
        $this->name = 'PruneAdminlog';
        $this->recurs = Job::RECUR_DAILY;
        $this->testrecurs = Job::RECUR_12H;
    }

    public function fill_description()
    {
        $this->description = lang_by_realm('jobs','adminlog_description');
    }

    public function testRecur($priorexec, $time = 0)
    {
        $lifetime = (int)cms_siteprefs::get(self::LIFETIME_SITEPREF,0);
        if( $lifetime != -1 ) { // automatic pruning
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
        if( $this->recurs == Job::RECUR_DAILY ) {
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

        $lifetime = (int)cms_siteprefs::get(self::LIFETIME_SITEPREF,(86400 * 31));
        if( $lifetime != -1 ) { // true unless racy preference-change since test()
            $db = CmsApp::get_instance()->GetDB();
            $q = "DELETE FROM ".CMS_DB_PREFIX."adminlog WHERE timestamp<?";
            $p = array($time - $lifetime);
            $db->Execute($q,$p);
        }
        return true;
    }
}
