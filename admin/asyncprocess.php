<?php
#CMS Made Simple backend processing script
#(c) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, read the license online at
#https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

use CMSMS\Async\Job;
use CMSMS\JobOperations;

while( ob_get_level() ) {
    @ob_end_clean();
}
ignore_user_abort();
header('Connection: close');
header('X-CMSMS: Processing');
echo ' '; // single character
flush();

if( !isset($_REQUEST['cms_cron']) ) {
    exit;
}

global $DONT_LOAD_SMARTY;
$DONT_LOAD_SMARTY = 1;

require_once '../lib/include.php';

$now = time();
$last_run = (int)cms_siteprefs::get_forced('last_processing');
$gap = JobOperations::get_async_freq();
if( $now < $last_run + $gap ) {
    exit; // too soon
}
$current_job = null; // the intra-loop 'current' job, if any - used during error handling

if( !function_exists('_cmsjobmgr_errorhandler') ) {
 function _cms_jobmgr_joberrorhandler($job,$errmsg,$errfile,$errline)
 {
    debug_to_log('Fatal error occurred processing async jobs at: '.$errfile.':'.$errline);
    debug_to_log('Msg: '.$errmsg);
    if( is_object($job) ) {
        // add the id to the cache of error-jobs
        $fn = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.JobOperations::ERRFILE;
        $fh = fopen($fn,'a');
        fwrite($fh,$job->id."\n");
        fclose($fh);
    }
 }
 function _cmsjobmgr_errorhandler()
 {
    global $current_job;
    $err = error_get_last();
    if( is_null($err) ) return;
    if( $err['type'] != E_ERROR ) return;
    if( $current_job ) {
        _cms_jobmgr_joberrorhandler($current_job,$err['message'],$err['file'],$err['line']);
    }
 }
}
register_shutdown_function('_cmsjobmgr_errorhandler');

$db = CmsApp::get_instance()->GetDb();
$save_time = function($job_id,$stamp) use($db)
{
    $sql = 'UPDATE '.CMS_DB_PREFIX.Job::RECORDTABLE.' SET start = ? WHERE id = ?';
    $db->Execute($sql,[$stamp,$job_id]);
};

$me = basename(__FILE__);
try {

    if( JobOperations::is_locked() ) {
        if( JobOperations::lock_expired() ) {
            debug_to_log($me.': Removing an expired lock (probably an error occurred)');
            audit('',$me,'Removing an expired lock. An error probably occurred during previous job-processing.');
            JobOperations::unlock();
        }
        else {
            debug_to_log($me.': Processing still locked (probably due to an error)... try again later');
            audit('',$me,'Processing is already occurring');
            exit;
        }
    }
    JobOperations::lock(); // block parallel processing

    JobOperations::process_errors();
    JobOperations::clear_bad_jobs();

    $config = cms_config::get_instance();
    $devreport = !empty($config['developer_mode']);
    $time_limit = JobOperations::get_batch_timeout();
    $started_at = $now;

    set_time_limit($time_limit);
    JobOperations::record_eligible_jobs();
    $jobs = JobOperations::get_jobs();

    foreach( $jobs as $job ) {
        // skip future-start jobs
        if( (int)$job->start > $now ) {
            continue;
        }
        try {
            $current_job = $job;
            if( $job instanceof RegularJob ) {
                $nextat = 1; // force a downstream test whether to execute now
            }
            else {
                $nextat = JobOperations::calculate_next_start_time($job);
            }
            if( $nextat == 0 ) {
                if ( $job->id > 0 ) {
                    $job->delete();
                }
            }
            elseif( $nextat <= time() + 1 ) {
                $pst = $job->start;
                $res = $job->execute($now); // updates start property to $now, errors property if needed
                if( $job->start != $pst ) {
                    $job->save(); // record updated start, errors
                    if( $devreport ) {
                        audit('',$me,'Processed job '.$job->name);
                    }
                }
            }
            $current_job = null;
        }
        catch (Exception $e) {
            audit($job->id,$me,'An error occurred while processing job '.$job->name);
            _cms_jobmgr_joberrorhandler($current_job,$e->GetMessage(),$e->GetFile(),$e->GetLine());
            $current_job = null;
        }
        $now = time(); // update for timeout-check
        // make sure we have not timed out
        if( $now - $time_limit >= $started_at ) {
            break;
        }
    }
}
catch (Exception $e) {
    debug_to_log('--Major async processing exception--');
    debug_to_log('exception '.$e->GetMessage());
    debug_to_log($e->GetTraceAsString());
}
cms_siteprefs::set('last_processing',$now);
JobOperations::unlock();

exit;
