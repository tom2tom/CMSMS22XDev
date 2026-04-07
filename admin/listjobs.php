<?php
/*
CMS Made Simple admin console script
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, read the license online at:
https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

use CMSMS\Async\Job;
use CMSMS\Async\RegularJob;
use CMSMS\JobOperations;

global $CMS_ADMIN_PAGE;
$CMS_ADMIN_PAGE = 1;
require_once '../lib/include.php';

$userid = get_userid();
if( !check_permission($userid,'Manage Jobs') ) {
    exit(lang('no_permission')); //TODO throw if can be caught
}

require_once 'header.php';

$me = basename(__FILE__);
$gap = JobOperations::get_async_freq();
$config = cms_config::get_instance();

$locked = JobOperations::is_locked();
if( $locked ) {
    if( JobOperations::lock_expired() ) {
        debug_to_log($me.': Removing an expired lock (probably an error occurred)');
        audit('',$me,'Removing an expired lock. An error probably occurred with a previous job.');
        JobOperations::unlock();
        $locked = false;
    }
}
$now = time(); // in case it's locked
if( !$locked ) {
    // replicate parts of backend-processor script to get fresh snapshot
    //TODO error-recording needed here too
    JobOperations::lock(); // block parallel processing

    $prior = (int)JobOperations::retrieve_timestamp(0,'last_processing');
    if( $prior > $now - $gap - 10 ) {
        // fake value to ensure jobs can refreshed now
        JobOperations::record_timestamp(0,'tasks_lastcheck',$now - $gap - 10);
    }

    $db = CmsApp::get_instance()->GetDb();
    $save_time = function($job_id,$stamp) use($db)
    {
        $sql = 'UPDATE '.CMS_DB_PREFIX.JobOperations::RECORDTABLE.' SET start = ? WHERE id = ?';
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
            if( (int)$job->start > $now ) { // OR allow a little slop ?
                continue; //ASYNCDEBUG
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
                elseif( $nextat <= time() + 1 ) { // checking $now is bad when debugging ASYNCDEBUG
                    $pst = $job->start;
                    $res = $job->execute($now); // updates start property to $now
                    if( $job->start != $pst ) { //TODO $res (API change) unreliable ?
                        $job->save(); // record updated start, errors TODO etc? might be whole job re-inserted
                        if( $devreport ) {
                            audit('',$me,'Processed job '.$job->name);
                        }
                    }
                    else {
                        $here = 2; //ASYNC DEBUG
                    }
                }
                else {
                    $here = 3; //ASYNC DEBUG
                }
            }
            catch( Exception $e ) {
                audit($job->id,$me,'Job \''.$job->name.'\' error: '.$e->getMessage() );
            }
            $now = time(); // update for timeout-check
            // make sure we have not timed out
            if( $now - $time_limit >= $started_at ) {
                break; // ASYNCDEBUG $here = 1;
            }
        }
        // defer the next jobs-poll
        JobOperations::record_timestamp(0,'last_async_trigger',$now);
    }
    catch( Exception $e ) {
        // some other error occurred
        debug_to_log('--Major async processing exception--');
        debug_to_log('exception '.$e->GetMessage());
        debug_to_log($e->GetTraceAsString());
    }
    JobOperations::unlock();
    JobOperations::record_timestamp(0,'last_processing',$now);
}

// setup for display
$nozone = true;
$jobs = [];
$job_objs = JobOperations::get_jobs_for_display();
if( $job_objs ) {
    $list = [];
    $list[Job::RECUR_15M] = lang('recur_15m');
    $list[Job::RECUR_30M] = lang('recur_30m');
    $list[Job::RECUR_HOURLY] = lang('recur_hourly');
    $list[Job::RECUR_120M] = lang('recur_120m');
    $list[Job::RECUR_180M] = lang('recur_180m');
    $list[Job::RECUR_DAILY] = lang('recur_daily');
    $list[Job::RECUR_WEEKLY] = lang('recur_weekly');
    $list[Job::RECUR_MONTHLY] = lang('recur_monthly');
    $list[Job::RECUR_YEARLY] = lang('recur_yearly');

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
        $rec = $job->displayrecr; // anything custom is preferred
        if( !$rec ) {
            if( JobOperations::job_recurs($job) ) {
                $rec = $job->recurs;
                if( array_key_exists($rec,$list) ) { $rec = $list[$rec]; }
            }
            else {
                $rec = null;
            }
        }
        $obj->recurs = $rec;
        $start = $job->start;
        $obj->created = ($start == 0) ? $job->created : (($start > $now) ? $job->created : null); //not displayed unless differs from start
        $obj->start = $start;
        $obj->until = ($job->until) ? $job->until + $offs : null;
        $obj->errors = $job->errors;
        $jobs[] = $obj;
    }
}

$pdev = check_permission($userid,'Modify Site Preferences') || !empty($config['developer_mode']); // whether to also display extra information suitable for site-developers

$smarty->changeCaching(false);
$tpl = $smarty->createTemplate('admin_tpl:listjobs.tpl',null,null,$smarty,false);
$tpl->assign('pdev',$pdev)
 ->assign('async_freq',$gap)
 ->assign('gmtime',$nozone)
 ->assign('jobs',$jobs)
 ->display();

require_once 'footer.php';
