<?php
/*
CMSMS CmsJobManager module action: defaultadmin
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CmsJobManager.module.php applies to this file.
*/

use CMSMS\Async\CronJob;
use CMSMS\Async\Job;
use CMSMS\Async\RegularJob;
use CMSMS\JobOperations;

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

$userid = get_userid();
$see_modlink = !cms_userprefs::get_for_user($userid,'hide_help_links',false);
$me = $this->GetName();
$gap = JobOperations::get_async_freq();

$locked = $this->is_locked();
if( $locked ) {
    if( $this->lock_expired() ) {
        debug_to_log($me.': Removing an expired lock (probably an error occurred)');
        audit('',$me,'Removing an expired lock. An error probably occurred with a previous job.');
        $this->unlock();
        $locked = false;
    }
}
if( !$locked ) {
    // replicate parts of action.process.php to get fresh snapshot
    //TODO error-recording needed here too
    $this->lock(); // block parallel processing

    $now = time();
    $prior = (int)$this->GetPreference('last_processing');
    if( $prior > $now - $gap - 10 ) {
        // fake value to ensure jobs can refreshed now
        $this->SetPreference('tasks_lastcheck',$now - $gap - 10);
    }

    $save_time = function($job_id,$stamp) use($db)
    {
        $sql = 'UPDATE '.CMS_DB_PREFIX.Job::RECORDTABLE.' SET start = ? WHERE id = ?';
        $db->Execute($sql,[$stamp,$job_id]);
    };
    $devreport = !empty($config['developer_mode']);
    $time_limit = JobOperations::get_batch_timeout();

    try {
        JobOperations::process_errors();
        JobOperations::clear_bad_jobs();

        $started_at = $now = time();

        set_time_limit($time_limit);
        JobOperations::record_eligible_jobs();
        $jobs = JobOperations::get_jobs();

        foreach( $jobs as $job ) {
            // skip future-start jobs
            if( (int)$job->start > $now ) {
                continue;
            }
            try {
                if( $job instanceof RegularJob ) {
                    $nextat = 1; // force a downstream test whether to execute now
                } else {
                    $nextat = JobOperations::calculate_next_start_time($job);
                }
                if( $nextat == 0 ) {
                    if( $job->id > 0 ) {
                        $job->delete();
                    }
                }
                elseif( $nextat <= time() + 1 ) { // checking $now is bad when debugging
                    $pst = $job->start;
                    $res = $job->execute($now); // updates start property to $now
                    if( $job->start != $pst ) {
                        $job->save(); // record updated start, errors TODO etc? might be whole job re-inserted
                        if( $devreport ) {
                            audit('',$me,'Processed job '.$job->name);
                        }
                    }
                }
            }
            catch( Exception $e ) {
                audit($job->id,$me,'Job \''.$job->name.'\' error: '.$e->getMessage() );
            }
            $now = time(); // update for timeout-check
            // make sure we have not timed out
            if( $now - $time_limit >= $started_at ) {
                break;
            }
        }
        // defer the next jobs-poll
        $this->SetPreference('last_async_trigger',$now);
    }
    catch( Exception $e ) {
        // some other error occurred
        debug_to_log('--Major async processing exception--');
        debug_to_log('exception '.$e->GetMessage());
        debug_to_log($e->GetTraceAsString());
    }
    $this->unlock();
    $this->SetPreference('last_processing',$now);
}

// setup for display
$nozone = true;
$jobs = [];
$job_objs = JobOperations::get_jobs_for_display();
if( $job_objs ) {
    $list = [];
    $list[CronJob::RECUR_15M] = $this->Lang('recur_15m');
    $list[CronJob::RECUR_30M] = $this->Lang('recur_30m');
    $list[CronJob::RECUR_HOURLY] = $this->Lang('recur_hourly');
    $list[CronJob::RECUR_120M] = $this->Lang('recur_120m');
    $list[CronJob::RECUR_180M] = $this->Lang('recur_180m');
    $list[CronJob::RECUR_DAILY] = $this->Lang('recur_daily');
    $list[CronJob::RECUR_WEEKLY] = $this->Lang('recur_weekly');
    $list[CronJob::RECUR_MONTHLY] = $this->Lang('recur_monthly');
    $list[CronJob::RECUR_YEARLY] = $this->Lang('recur_yearly');

    $offs = 0;
    $zone = $config['timezone'];
    if( $zone && $zone != 'UTC' ) {
        try {
            $dt = new DateTime('@0', new DateTimeZone('UTC'));
            $tz = new DateTimeZone($zone);
            $offs = $tz->getOffset($dt);
            $nozone = false;
        }
        catch( Exception $e ) {
            // nothing here
        }
    }

    foreach( $job_objs as $job ) {
        $obj = new stdClass();
        $obj->name = $job->name;
        $obj->desc = $job->description ?: null;
        $obj->module = $job->module ?: null;
        $flag = JobOperations::job_recurs($job);
        if( $flag ) {
            $rec = $job->recurs;
            if( isset($list[$rec]) ) $rec = $list[$rec];
        }
        else {
            $rec = null;
        }
        $obj->recurs = $rec;
        $obj->created = $job->created; //not displayed unless differs from start
        $obj->start = $job->start;
        $obj->until = ($job->until) ? $job->until + $offs : null;
        $obj->errors = $job->errors;
        $jobs[] = $obj;
    }
}

$tpl = $smarty->createTemplate("module_file_tpl:$me;defaultadmin.tpl",null,$me,$smarty);
$tpl->assign('async_freq',$gap);
$tpl->assign('gmtime',$nozone);
$tpl->assign('jobs',$jobs);
$tpl->assign('modhelp',$see_modlink);
$tpl->display();
