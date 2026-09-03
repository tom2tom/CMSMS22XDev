<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: CMSMS\JobOperations
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
#END_LICENSE

namespace CMSMS;

use cms_config;
use cms_siteprefs;
use CmsApp;
use CmsCoreCapabilities;
use CMSMS\Async\ConditionalJob;
use CMSMS\Async\Job;
use CMSMS\Async\RegularJob;
use CMSMS\HookManager;
use CmsRegularTask;
use Exception;
use ModuleOperations;
use RuntimeException;
use const CMS_DB_PREFIX;
use const CMS_DEBUG;
use const CMS_ROOT_PATH;
use const TMP_CACHE_LOCATION;
use function audit;
use function cms_join_path;
use function debug_to_log;

/**
 * A class of static methods for interacting with table-recorded background jobs
 *
 * @package CMS
 * @since 2.2.23F2
 * @since 2.2 as CMSMS\Async\JobManager
 * @author Robert Campbell
 */
final class JobOperations
{
    const CACHETABLE = 'jobs_record';
    const RECORDTABLE = 'jobs'; // also the prefix of the sequence-table name
    const ERRFILE = 'AsyncJob.errs';
    const LOCKPREF = 'jobs_lock';

    private static $db = null;

    /**
     * Get the jobs recorded in the pending-jobs table.
     * This is used to generate data for displaying jobs-table items.
     * No item is added to the table during this method.
     *
     * @return array of Jobs or maybe empty
     * Array members are ordered by objects' name-property.
     * Maybe should be by start datetime (if any)
     */
    public static function get_jobs_for_display()
    {
        if (!self::$db) {
            self::$db = CmsApp::get_instance()->GetDb();
        }
        $now = time();
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::RECORDTABLE.' ORDER BY name,module'; // no need for placeholder
        $data = self::$db->GetArray($sql);
        if (!$data || !is_array($data)) {
            return [];
        }
        $out = [];
        $ops = ModuleOperations::get_instance();
        foreach ($data as $row) {
            if (!empty($row['module'])) {
                $mod = $ops->get_module_instance($row['module']);
                if (!is_object($mod)) {
                    throw new RuntimeException('Job '.$row['name'].' requires module '.$row['module'].' That could not be loaded');
                }
            }
            $obj = self::unflatten($row['data']);
            if (!$obj) {
                debug_to_log(__METHOD__);
                debug_to_log('Problem unserializing job object');
                debug_to_log($row);
                //TODO etc e.g. delete or errors++
            }
            // cached serialized job might now be inconsisent with other table values
            foreach (['id', 'created', 'errors', 'until'] as $prop) {
                $obj->$prop = (int)$row[$prop];
            }
            $start = (int)$row['start'];
            if ($start != $obj->start) {
                $obj->force_start = $start;
            }
            $obj->fill_description();
            $out[] = $obj;
        }
        return $out;
    }

    /**
     * Get table-recorded jobs.
     * This is used to generate data for the jobs-table items to be processed.
     * No item is added to the table during this method.
     *
     * @return array Jobs or maybe empty
     * Array members are ordered by decreasing error-count and priority,
     *  and increasing creation datetime.
     */
    public static function get_jobs()
    {
        $now = time();

        if (!self::$db) {
            self::$db = CmsApp::get_instance()->GetDb();
        }
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::RECORDTABLE.
        " WHERE (start IS NULL OR start <= $now) ORDER BY errors DESC,priority,created"; // no need for placeholder
        $data = self::$db->GetArray($sql);
        if (!$data) {
            return [];
        }

        $out = [];
        $ops = ModuleOperations::get_instance();
        foreach ($data as $row) {
            if (!empty($row['module'])) {
                $mod = $ops->get_module_instance($row['module']);
                if (!is_object($mod)) {
                    audit('', __CLASS__, sprintf('Could not load module %s required by job %s', $row['module'], $row['name']));
                    continue;
                }
            }
            $obj = self::unflatten($row['data']);
            if (!$obj) {
                debug_to_log(__METHOD__);
                debug_to_log('Problem unserializing job object');
                debug_to_log($row);
                //TODO etc e.g. delete or errors++
            }
            $obj->force_start = (int)$row['start'];
            $out[] = $obj;
        }
        return $out;
    }

    /**
     * Job-class safe unserializer
     *
     * @param string $data serialized object
     * @return mixed object | false
     */
    public static function unflatten($data)
    {
        $prior = ini_get('unserialize_callback_func');
        ini_set('unserialize_callback_func', 'CMSMS\JobOperations::JobLoader');
        // manage E_WARNING if unserialize fails
        $prior2 = set_error_handler(function() { return true; }, E_WARNING);
        $obj = unserialize($data); // all classes allowed (at least for a RecurringJob with some sort of converted Task object in it)
        set_error_handler($prior2);
        ini_set('unserialize_callback_func', $prior);
        return $obj;
    }

    /**
     * Job-class mini autoloader
     * @ignore
     */
    public static function JobLoader($classname)
    {
        $parts = explode('\\', trim($classname, ' \\/'));
        $v = $parts[0];
        if ($v == 'CMSMS') {
            $parts[0] = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib';
        } else {
            $parts[0] = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$v.DIRECTORY_SEPARATOR.'lib';
        }
        $n = count($parts) - 1;
        $v = $parts[$n];
        $parts[$n] = "class.$v.php";
        $fp = implode(DIRECTORY_SEPARATOR, $parts);
        include_once $fp;
    }

    /**
     * Record in the jobs-table the Job and CmsRegularTask objects that
     * are candidates for execution.
     *
     * @return bool indicating object(s) were recorded
     */
    public static function record_eligible_jobs()
    {
        $res = false;
        $pollplace = function($pathbase,$classbase) {
            $res = 0;
            $patn = $pathbase.DIRECTORY_SEPARATOR.'class.*Job.php';
            $paths = glob($patn, GLOB_NOESCAPE);
            foreach ($paths as $one) {
                $tmp = basename($one, '.php');
                require_once $one;
                $classname = $classbase.str_replace('class.', '', $tmp);
                try {
                    if (class_exists($classname, false)) {
                        $job = new $classname();
//                      $job_id =
                        $job->save();
                        $res++;
                    }
                }
                catch (Exception $e) {
                // nothing here
                }
            }
            return $res;
        };

        // 1.  Poll places for non-module-specific jobs
        // native jobs
        $dir = cms_join_path(CMS_ROOT_PATH, 'lib', 'jobs');
        if ($pollplace($dir, 'CMSMS\jobs\\') > 0) { $res = true; }
        $config = cms_config::get_instance();
        $dir = cms_join_path($config['assets_path'], 'jobs');
        if ($pollplace($dir, 'CMSAsset\jobs\\') > 0) { $res = true; }
        $astdir = $config['assets_dir'];
        if ($pollplace($dir, "CMSMS\\$astdir\\jobs\\") > 0) { $res = true; }

        // CmsRegularTask-derived jobs
        $patn = cms_join_path(CMS_ROOT_PATH, 'lib', 'jobs', 'class.*ask.php');
        $paths = glob($patn, GLOB_NOESCAPE);
        foreach ($paths as $one) {
            require_once $one;
            $tmp = str_replace('class.', '', basename($one, '.php'));
            $classname = preg_replace('/\.?[tT]ask$/', 'Task', $tmp); // no namespace ? optional namespace ?
            try {
                $obj = new $classname();
                if (!$obj instanceof CmsRegularTask) {
                    continue;
                }
                $job = new RegularJob($obj);
                $job->save();
                $res = true;
            }
            catch (Exception $e) {
                // nothing here
            }
        }

        // 2.  Get jobs from relevant modules
        $ops = ModuleOperations::get_instance();
        $modules = $ops->get_modules_with_capability(CmsCoreCapabilities::TASKS);
        if (!$modules) {
            return false;
        }
        foreach ($modules as $one) {
            if (!is_object($one)) {
                $one = $ops->get_module_instance($one);
            }
            if (!is_object($one)) {
                continue; // module exists but for some reason cannot be loaded
            }

            $tasks = $one->GetTasks(); // will use get_tasks() if relevant
            if (!$tasks) {
                continue;
            }
            if (!is_array($tasks)) {
                $tasks = [$tasks];
            }

            foreach ($tasks as $onetask) {
                if (!is_object($onetask)) {
                    continue;
                }
                if ($onetask instanceof CmsRegularTask) {
                    $job = new RegularJob($onetask);
                    $job->module = $one->GetName();
                    $job->save();
                    $res = true;
                } elseif ($onetask instanceof Job) {
                    $onetask->save();
                    $res = true;
                }
            }
        }
        return $res;
    }

    /**
     * Bump error counts in the RECORDTABLE in accord with ids recorded
     * in the cache of error-jobs, then delete that cache
     */
    public static function process_errors()
    {
        $fn = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.self::ERRFILE;
        if (!is_file($fn)) {
            return;
        }

        $data = file_get_contents($fn);
        @unlink($fn);
        if (!$data) {
            return;
        }

        $tmp = explode("\n", $data);
        if (!$tmp) {
            return;
        }

        $job_ids = [];
        foreach ($tmp as $one) {
            $one = (int) $one;
            if ($one < 1) {
                continue;
            }
            if (!in_array($one, $job_ids)) {
                $job_ids[] = $one;
            }
        }

        if ($job_ids) {
            if (!self::$db) {
                self::$db = CmsApp::get_instance()->GetDb();
            }
            $sql = 'UPDATE '.CMS_DB_PREFIX.self::RECORDTABLE.
            ' SET errors = errors + 1 WHERE id IN ('.implode(',', $job_ids).')';
            self::$db->Execute($sql);
            debug_to_log('Increased error count on '.count($job_ids).' jobs ');
        }
    }

    /**
     * At most hourly, clear table-recorded jobs having error count
     * >= the configured maximum-errors-count
     */
    public static function clear_bad_jobs()
    {
        $lastrun = (int)self::retrieve_timestamp(0, 'last_badjob_run');
        $now = time();
        if ($lastrun > $now - 3600) { // hardcoded interval
            return;
        }

        $job_maxerrs = cms_siteprefs::get('job_maxerrs', 5);
        if (!self::$db) {
            self::$db = CmsApp::get_instance()->GetDb();
        }
        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::RECORDTABLE.' WHERE errors >= ?';
        $data = self::$db->GetArray($sql, [$job_maxerrs]);
        if ($data && is_array($data)) {
            $idlist = [];
            foreach ($data as $row) {
                $obj = self::unflatten($row['data']);
                if (!is_object($obj)) {
                    debug_to_log(__METHOD__);
                    debug_to_log('Problem unserializing job object');
                    debug_to_log($row);
                    //TODO etc e.g. delete or errors++
                    continue;
                }
                $idlist[] = (int) $row['id'];
                HookManager::do_hook('Core::OnJobFailed', ['job' => $obj]);
            }
            $sql = 'DELETE FROM '.CMS_DB_PREFIX.self::RECORDTABLE.' WHERE id IN ('.implode(',', $idlist).')';
            self::$db->Execute($sql);
            audit('', __CLASS__, 'Cleared '.count($idlist).' bad jobs');
        }
        self::record_timestamp(0, 'last_badjob_run', $now);
    }

    /**
     * Clear all recorded jobs-data
     */
    public static function clear_all()
    {
        if (!self::$db) {
            self::$db = CmsApp::get_instance()->GetDb();
        }
        self::$db->Execute('DELETE FROM '.CMS_DB_PREFIX.self::CACHETABLE.' WHERE id > 0'); // i.e. only job-specific properties
        self::$db->Execute('DELETE FROM '.CMS_DB_PREFIX.self::RECORDTABLE);
        self::reset_sequence();
    }

    /**
     * Clear all recorded jobs-data for a specified module
     * This is a registered Core::ModuleUninstalled event handler
     * @param array $params
     */
    public static function clear_module(array $params)
    {
        if (!empty($params['name'])) {
            if (!self::$db) {
                self::$db = CmsApp::get_instance()->GetDb();
            }
            $modname = $params['name'];
            self::$db->Execute('DELETE FROM '.CMS_DB_PREFIX.self::CACHETABLE.' WHERE propname LIKE ?', ["%{$modname}%"]);
            self::$db->Execute('DELETE FROM '.CMS_DB_PREFIX.self::RECORDTABLE.' WHERE module=?', [$modname]);
            self::reset_sequence();
        }
    }

    /**
     * If RECORDTABLE is now empty, reset the sequence tbl id to 0 without a race
     * Pity this can't be done by a db trigger with possibly-ancient MySQL
     */
    public static function reset_sequence()
    {
        if (!self::$db) {
            self::$db = CmsApp::get_instance()->GetDb();
        }
        $mysql = self::$db->get_inner_mysql();
        $mysql->query('LOCK TABLE '.CMS_DB_PREFIX.self::RECORDTABLE.' WRITE');
        $res = $mysql->query('SELECT id FROM '.CMS_DB_PREFIX.self::RECORDTABLE);
        if ($res->num_rows == 0) {
            $seqname = CMS_DB_PREFIX.self::RECORDTABLE . '_seq';
            $mysql->query("LOCK TABLE `$seqname` WRITE");
            $mysql->query("UPDATE `$seqname` SET id = 0");
        }
        $mysql->query('UNLOCK TABLE');
    }

    /**
     * Get the (minimum) interval between successive jobs-processing operations.
     * The interval (as minutes) might be recorded in $config data or site-preferences
     * Otherwise a default value is used.
     *
     * @return int seconds 180 .. 3600
     */
    public static function get_async_freq()
    {
        $config = cms_config::get_instance();
        $val = $config['cmsjobmanager_asyncfreq'];
        if ($val == null) {
            $val = $config['cmsjobmgr_asyncfreq']; // deprecated name
        }
        if ($val == null) {
            $val = cms_siteprefs::get('jobs_interval', 15);
        }
        if ($val) {
            $val = max(3, min(60, (int)$val));
            return $val * 60;
        }
        return 900; // aka 15 minutes
    }

    /**
     * Get the maximum time to complete executing a batch of jobs.
     * The interval might be recorded in $config data or site-preferences.
     * Otherwise PHP's 'max_execution_time' ini-setting is used.
     *
     * @return int seconds 10 .. 600
     */
    public static function get_batch_timeout()
    {
        $config = cms_config::get_instance();
        $val = (int)$config['cmsjobmanager_timelimit'];
        if ($val == 0) {
            $val = (int)cms_siteprefs::get('jobs_timeout');
            if ($val == 0) {
                $val = (int)ini_get('max_execution_time');
            }
        }
        return max(10, min(600, $val));
    }

    /**
     * Get whether the specified Job is recurrent/repeated
     * (i.e. not whether it is actually recurring now)
     *
     * @param Job $job
     * @return bool
     */
    public static function job_recurs(Job $job)
    {
        if (!$job instanceof ConditionalJob) {
            return $job->recurs &&
            !($job->recurs == Job::RECUR_NONE || $job->recurs == Job::RECUR_NONE_CUSTOM);
        }
        $prior = (int)$job->lastexec;
        return $job->testRecur($prior, time());
    }

    /**
     * Get the timestamp representing when the specified Job would like
     * to [re-]start.
     * For any Job (recurrent or non-recurrent).
     * The actual start will probably be later, depending on when jobs
     * are processed etc.
     *
     * @param Job $job
     * @return int possibly 0 representing no-start
     */
    public static function calculate_next_start_time(Job $job)
    {
        $now = time();
        $prior = (int)$job->lastexec;

        if (!$job instanceof ConditionalJob) {
            switch($job->recurs) {
            case '': // identifiers of a onetime job
            case null:
            case Job::RECUR_NONE:
            case Job::RECUR_NONE_CUSTOM:
                if ($prior > 0) {
                    return 0;
                }
                if ($job->start <= $now) {
                    $when = $now;
                } else {
                    $when = $job->start;
                }
                break;
            case Job::RECUR_15M:
                $when = $prior + 15 * 60;
                break;
            case Job::RECUR_30M:
                $when = $prior + 30 * 60;
                break;
            case Job::RECUR_HOURLY:
                $when = $prior + 3600;
                break;
            case Job::RECUR_2H:
                $when = $prior + 2 * 3600;
                break;
            case Job::RECUR_3H:
                $when = $prior + 3 * 3600;
                break;
            case Job::RECUR_DAILY:
                $when = strtotime('+1 day', $prior);
                break;
            case Job::RECUR_WEEKLY:
                $when = strtotime('+1 week', $prior);
                break;
            case Job::RECUR_MONTHLY:
                $when = strtotime('+1 month', $prior);
                break;
            case Job::RECUR_YEARLY:
                $when = strtotime('+1 year', $prior);
                break;
            case Job::RECUR_CUSTOM:
                $when = PHP_INT_MAX; //i.e. never TODO $prior + func($job)
                break;
            default:
                $when = $now; //TODO $prior + func($job)
            }
        }
        else { // ConditionalJob
            $when = $job->testStart($prior, $now);
            if( $when == 0 ) return 0;
        }
        if ($when < $now) {
            $when = $now;
        }
        if (CMS_DEBUG) {
            debug_to_log("adjusted to {$when} -- {$prior} // {$job->until}"); //onetime jobs ? empty ->until ?
        }
        if (!$job->until || $when <= $job->until) {
            return $when;
        }
        return 0;
    }

    /**
     * Acquire the job-processing lock
     * This is essentially to prevent concurrent processing of jobs on
     * threaded web-servers. The lock does not apply to any particular job
     */
    public static function lock()
    {
        self::record_timestamp(0, self::LOCKPREF, time());
    }

    /**
     * Remove the job-processing lock if any
     */
    public static function unlock()
    {
        self::remove_timestamp(0, self::LOCKPREF);
    }

    /**
     * Check whether the job-processing lock exists
     * @return bool
     */
    public static function is_locked()
    {
        $lock = (int)self::retrieve_timestamp(0, self::LOCKPREF);
        return ($lock && $lock > 0);
    }

    /**
     * Check whether the job-processing lock has expired
     * @return bool
     */
    public static function lock_expired()
    {
        $lock = (int)self::retrieve_timestamp(0, self::LOCKPREF);
        return ($lock && $lock > 0) ?
            ($lock < time() - self::get_batch_timeout()) : false;
    }

    /**
     * Cache a timestamp in the CACHETABLE
     *
     * @param int $id job identifier
     * @param string $name property identifier
     * @param int $time the value to store
     */
    public static function record_timestamp($id,$name,$time)
    {
        $db = CmsApp::get_instance()->GetDb();
        $tbl = CMS_DB_PREFIX.self::CACHETABLE;
        // upsert
        $sql = "INSERT INTO $tbl(id,propname,value) SELECT ?,?,?
WHERE NOT EXISTS (SELECT 1 FROM $tbl WHERE id=? AND propname=?)";
        $dbr = $db->Execute($sql,[$id,$name,$time,$id,$name]);
        if( !$dbr || $db->Affected_Rows() == 0 ) {
            $sql = "UPDATE $tbl SET value=? WHERE id=? AND propname=?";
            $dbr = $db->Execute($sql,[$time,$id,$name]);
        }
    }

    /**
     * Get a table-cached timestamp
     *
     * @param int $id job identifier > 0 or 0 for non-job data
     * @param string $name property identifier
     * @return mixed int | null if not found
     */
    public static function retrieve_timestamp($id,$name)
    {
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'SELECT value FROM '.CMS_DB_PREFIX.self::CACHETABLE.' WHERE id=? AND propname=?';
        $res = $db->GetOne($sql,[$id,$name]);
        return ($res > 0) ? (int)$res : null;
    }

    /**
     * Delete a table-cached timestamp
     *
     * @param int $id job identifier
     * @param string $name property identifier
     */
    public static function remove_timestamp($id,$name)
    {
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.self::CACHETABLE.' WHERE id=? AND propname=?';
        //dbr =
        $db->Execute($sql,[$id,$name]);
        //TODO mebbe return a $dbr != false flag indicating success
    }
}
