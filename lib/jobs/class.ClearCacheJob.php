<?php

namespace CMSMS\jobs;

use CmsApp;
use cms_siteprefs;
use CMSMS\Async\ConditionalJob;
use CMSMS\Async\Job;
use function lang;
use function lang_by_realm;

class ClearCacheJob extends ConditionalJob
{
    const CACHEDFILEAGE_SITEPREF = 'auto_clear_cache_age';

    public function __construct()
    {
        parent::__construct();
        $this->name = 'ClearCache';
        $this->recurs = Job::RECUR_DAILY;
        $this->testrecurs = Job::RECUR_DAILY;
    }

    public function fill_description()
    {
        $this->description = lang_by_realm('jobs','clearcache_description');
    }

    public function testRecur($priorexec, $time = 0)
    {
        $age_days = (int)cms_siteprefs::get(self::CACHEDFILEAGE_SITEPREF,0);
        if( $age_days == 0 ) {
            if( $this->recurs == Job::RECUR_DAILY ) {
                $this->recurs = Job::RECUR_NONE_CUSTOM;
                $this->displayrecr = lang('disabled');
                $this->testrecurs = Job::RECUR_12H;
                $this->save();
            }
        }
        elseif( $this->recurs != Job::RECUR_DAILY ) {
            $this->recurs = Job::RECUR_DAILY;
            $this->testrecurs = Job::RECUR_DAILY;
            $this->save();
        }
        return true;
    }

    public function testStart($priorexec, $time = 0)
    {
        if( $this->recurs == Job::RECUR_DAILY ) {
            return strtotime('+1 day', $priorexec);
        }
        return $priorexec + 43200; //aka 3600*12 more-frequent checks for property-reversion
    }

    public function execute($time = 0)
    {
        if( !$time ) { $time = time(); }
        $this->record_exec_time($time);
        if( $this->start > $time ) return true;
        $this->force_start = $time;

        $age_days = (int)cms_siteprefs::get(self::CACHEDFILEAGE_SITEPREF,0);
        if( $age_days > 0 ) { // true unless racy preference-change since test()
            CmsApp::get_instance()->clear_cached_files($age_days);
        }

        return true;
    }
}
