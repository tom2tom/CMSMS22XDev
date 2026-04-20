<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Class: CMSMS\Async\Job
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

namespace CMSMS\Async;

use CmsApp;
use CMSMS\JobOperations;
use LogicException;
use const CMS_DB_PREFIX;

/**
 * An abstract base class for a background Job
 *
 * @package CMS
 * @author Robert Campbell
 * @since 2.2
 */
abstract class Job
{
    /**
     * Constants indicating that a job does not recur
     * (a falsy recurs-property value indicates the same)
     */
    const RECUR_NONE = '_none';
    const RECUR_ONCE = '_none'; // alias

    /**
     * Constants indicating that a job has a custom recurs-property
     * value (in its 'displayrecr' property) to use for public display.
     */
    const RECUR_CUSTOM = '_custom';
    const RECUR_NONE_CUSTOM = '_custom_none';

    /**
     * Constant indicating that a job manages its own recurrence
     * (which may change from time to time)
     */
    const RECUR_SELF = '_self';

    /**
     * Constants indicating the minimum inter-execution interval for a recurring job
     */
    const RECUR_15M = '_15m';
    const RECUR_30M = '_30m';
    const RECUR_HOURLY = '_hourly';
    const RECUR_60M = '_hourly'; // alias
    const RECUR_2H = '_120m';
    const RECUR_120M = '_120m'; // alias
    const RECUR_3H = '_180m';
    const RECUR_180M = '_180m'; // alias
    const RECUR_12H = '_720m';
    const RECUR_DAILY = '_daily';
    const RECUR_WEEKLY = '_weekly';
    const RECUR_MONTHLY = '_monthly';
    const RECUR_YEARLY = '_yearly';

    /**
     * Constants indicating job priority
     */
    const PRIORITY_HIGH = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_LOW = 3;

    /**
     * @var array
     * The 'name' property (specified by the sub-class), in combination
     * with the 'module' property if any, must be unique across the installation
     * The 'created' property represents the original poll-time, and
     * should not be changed by any re-poll
     * No 'start' property is recorded unless a futuer-start is wanted
     * or until job execution or other use of ->force_start
     * Any 'lastexec' contructed-property is recorded in the
     * JobOperations::CACHETABLE, for easier access during jobs-processing
     * and after this job has been deleted from the JobOperations::RECORDTABLE
     */
    protected $props = [
     'id' => 0,
     'created' => 0,
     'displayrecr' => null,
     'errors' => 0,
     'module' => null,
     'name' => null,
     'priority' => 2,
     'recurs' => self::RECUR_NONE,
     'start' => null,
     'until' => null
    ];

    /**
     * @var string
     */
    public $description = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->props['created'] = time();
    }

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __get($key)
    {
        switch( $key ) {
        case 'id':
        case 'created':
        case 'start':
        case 'errors':
        case 'priority':
        case 'until':
            return (isset($this->props[$key])) ? (int)$this->props[$key] : null;

        case 'name':
        case 'module':
            return (isset($this->props[$key])) ? trim($this->props[$key]) : null;

        case 'description':
            return (isset($this->description)) ? trim($this->description) : '';

        case 'lastexec': // pseudo-property
            $cachename = $this->lastexec_propname();
            return JobOperations::retrieve_timestamp($this->props['id'],$cachename);

        case 'recurs':
            $val = trim((string)$this->props['recurs']);
            if( !($val == self::RECUR_CUSTOM || $val == self::RECUR_NONE_CUSTOM) ) {
                return $val;
            }
            // no break here
        case 'displayrecr':
            return (!empty($this->props['displayrecr'])) ? trim((string)$this->props['displayrecr']) : null;

        case 'until':
            return ($this->props['until'] !== null) ? (int)$this->props['until'] : null;

        default:
            if (isset($this->props[$key])) return $this->props[$key];
            throw new LogicException("'$key' is not a gettable member of ".get_class($this));
        }
    }

    /**
     * @param string $key
     * @param mixed $val
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function __set($key,$val)
    {
        switch( $key ) {
        case 'name':
            if( $val ) {
                $this->props[$key] = trim((string)$val);
                break;
            }
            throw new LogicException("Invalid or empty 'name' passed to ".__METHOD__);
        case 'module':
            $this->props[$key] = ($val) ? trim((string)$val) : null;
            break;

        case 'description':
            $this->description = ($val) ? trim($val) : null;

        case 'start':
            $val = (int)$val;
            // this overrides the setter in the base class.
            $limit = JobOperations::get_batch_timeout();
            if( $val + $limit < time() ) {
                throw new LogicException('Cannot set a job start property in the past');
            }
            if( empty($this->props[$key]) ) {
                if( $val >= $this->props['created'] ) {
                    if( empty($this->props['until']) || $val < $this->props['until'] ) {
                        $this->props['start'] = $val;
                        break;
                    }
                }
            }
            elseif( $val == $this->props['start'] ) {
                break;
            }
            throw new LogicException("Invalid 'start' passed to ".__METHOD__);
            // no break here

        // pseudo-property, can be used for reconciling this Job with
        // its table-recorded start value
        // in any case, no validation
        case 'force_start':
            $this->props['start'] = (int)$val;
            break;

        case 'errors':
        case 'created':
            $this->props[$key] = (int)$val;
            break;

        case 'id':
            $val = (int)$val;
            if( empty($this->props[$key]) ) {
                if( $val < 1 ) throw new LogicException("Invalid '$key' passed to ".__METHOD__);
                $this->props[$key] = $val;
                break;
            }
            elseif( $val == $this->props[$key] ) {
                break;
            }
            throw new LogicException("'$key' is not a settable member of ".get_class($this));

        case 'priority':
            $this->props[$key] = max(1,min(3,(int)$val));
            break;

        // pseudo-property
        case 'lastexec':
            $cachename = $this->lastexec_propname();
            JobOperations::record_timestamp($this->props['id'],$cachename,(int)$val);
            break;

        case 'recurs':
            switch( $val ) {
            case self::RECUR_NONE:
            case self::RECUR_15M:
            case self::RECUR_30M:
            case self::RECUR_HOURLY:
            case self::RECUR_2H:
            case self::RECUR_3H:
            case self::RECUR_12H:
            case self::RECUR_DAILY:
            case self::RECUR_WEEKLY:
            case self::RECUR_MONTHLY:
            case self::RECUR_YEARLY:
                $this->props['recurs'] = $val;
                $this->props['displayrecr'] = '';
                break;
            case self::RECUR_CUSTOM:
            case self::RECUR_NONE_CUSTOM:
                $this->props['recurs'] = $val;
                if( method_exists($this, 'get_custom_frequency') ) {
                    $this->props['displayrecr'] = $this->get_custom_frequency();
                }
                else {
                    $this->props['displayrecr'] =  ($val == self::RECUR_CUSTOM) ? 'Custom' : lang('disabled');
                }
                break;
            default:
                throw new LogicException("$val is an invalid value for job recurrence");
            }
            break;

        case 'until':
           if( $val != null ) { // any truthy value
                if( $this->props['start'] <= $val ) {
                    $this->props['until'] = (int)$val;
                }
                else {
                    throw new LogicException('Cannot set a job finish property before the job start');
                }
            } else {
                $this->props['until'] = null;
            }
            break;

        default:
            $this->props[$key] = $val;
        }
    }

    public function due()
    {
        // TODO ensure this handles not-yet-executed onetime jobs
        $nextat = JobOperations::calculate_next_start_time($this);
        return $nextat > 0 && ((int)$this->until == 0 || $nextat <= $this->until) && $nextat <= time();
    }

    /**
     * Record the numeric id of this Job (if possible)
     *
     * @param int $id > 0
     */
    public function set_id($id)
    {
        $this->id = $id;
    }

    /**
     * Delete this Job from the RECORDTABLE (if possible)
     */
    public function delete()
    {
        if( $this->props['id'] == 0 ) {
            throw new LogicException('Cannot delete a job that has not been saved');
        }
        $db = CmsApp::get_instance()->GetDb();
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.JobOperations::RECORDTABLE.' WHERE id = ?';
        $db->Execute($sql,[$this->props['id']]);
        JobOperations::reset_sequence(); // reset the sequencer if possible
        $this->props['id'] = 0;
    }

    /**
     * Insert or update this Job in the RECORDTABLE
     *
     * @return int this Job's (existing or new) numeric id
     */
    public function save()
    {
        if( !$this->name ) {
            throw new LogicException("Invalid or empty 'name' for job in ".__METHOD__);
        }
        $rec = null; //default db field-value
        $until = null;
        if( JobOperations::job_recurs($this) ) {
            $rec = $this->recurs; // TODO might have a related ->public string
            $until = $this->until; // TODO non-recurrent jobs might properly have an end-time ?
        }
        $db = CmsApp::get_instance()->GetDb();
        // Jobs will first be saved when the RECORDTABLE is being populated
        // during job/task discovery i.e. this Job might be recorded in that
        // table despite not having a numeric id. So check for matched name + module
        $mn = $this->module ?: null;
        if( $mn ) {
            $row = $db->GetRow('SELECT * FROM '.CMS_DB_PREFIX.JobOperations::RECORDTABLE.' WHERE (name=? AND module=?)',[$this->name,$mn]);
        }
        else {
            $row = $db->GetRow('SELECT * FROM '.CMS_DB_PREFIX.JobOperations::RECORDTABLE." WHERE (name=? AND COALESCE(module,'')='')",[$this->name]);
        }
        if( $row ) {
            $saveobj = FALSE;
            foreach( ['id','until','priority'] as $prop ) {
                if( $this->$prop == 0 ) {
                    $this->$prop = ($row[$prop] !== null) ? (int)$row[$prop] : null;
                    $saveobj = TRUE;
                }
            }
            if( $this->start > $row['start'] && $row['start'] > time() ) {
                $this->force_start = (int)$row['start']; // preserve logged future-value
            }
            elseif( $this->start == 0 ) {
                $this->force_start = ($row['start'] !== null) ? (int)$row['start'] : null;
            }
            if( !$saveobj ) {
                foreach( ['until','priority'] as $prop ) { // these may be changed at runtime
                    if( $this->$prop != $row[$prop] ) {
                        $saveobj = TRUE; // $this->$prop will be recorded
                    }
                }
            }
            if( $this->created != $row['created'] && $row['created'] > 0 ) {
                $this->created = $row['created']; // no need for table-field change
                $saveobj = TRUE;
            }
            if( !$this->recurs ) { // else if $this->recurs != $row['recurs'] both will be recorded
                $this->recurs = $row['recurs']; // no need for table-field change
                $saveobj = TRUE;
            }
            $job_id = $this->id;
            $start = ((int)$this->props['start']);
            if( $saveobj ) {
                $flat = serialize($this);
                $sql = 'UPDATE '.CMS_DB_PREFIX.JobOperations::RECORDTABLE.' SET start=?, until=?, recurs=?, priority=?, errors=errors + ?, data=? WHERE id=?';
                $db->Execute($sql,[$start,$until,$rec,$this->priority,$this->errors,$flat,$job_id]);
            }
            else {
                $sql = 'UPDATE '.CMS_DB_PREFIX.JobOperations::RECORDTABLE.' SET start=?, until=?, recurs=?, errors=errors + ? WHERE id=?';
                $db->Execute($sql,[$start,$until,$rec,$this->errors,$job_id]);
            }
        }
        else {
            $job_id = $db->GenID(CMS_DB_PREFIX.JobOperations::RECORDTABLE.'_seq');
            $this->props['id'] = $job_id;
            $start = ((int)$this->props['start']); // 0 (i.e. not yet executed) or < $until (if any) or else < $now
            $flat = serialize($this);
            $sql = 'INSERT INTO '.CMS_DB_PREFIX.JobOperations::RECORDTABLE.' (id,name,module,created,start,until,priority,errors,recurs,data) VALUES (?,?,?,?,?,?,?,?,?,?)';
//          $dbr =
            $db->Execute($sql,[$job_id,$this->name,($this->module ?: null),$this->created,$start,$until,($this->priority ?: 2),$this->errors,$rec,$flat]);
        }
        return $job_id;
    }

    /**
     * Populate this Job's description property (prior to dislay in Jobs list) 
     * @abstract
     * @return string
     */
    public function fill_description()
    {
        $this->description = '';
    }

    /**
     * Execute this Job.
     * @abstract
     * Each Job must be able to execute properly within the configured
     * time interval for processing all jobs, after each request has been
     * processed.
     * Any data that are needed for the Job to process must be stored
     * either with the Job object, or in the database.
     * Jobs cannot count on admin-user or data stored in session variables.
     * This method needs to update the Job start and lastexec properties
     * and update the Job errors property to reflect failed execution
     * (if an Exception is thrown which aborts the Job and prevents
     * errors-count update, that count would automatically be updated
     * outside the Job).
     *
     * @param int $now Optional start-of-execution timestamp Default 0 (hence time())
     * @return mixed truthy or falsy value indicating successful completion
     */
    public function execute($now = 0)
    {
        $this->force_start = $now; // without validation
        $this->record_exec_time($now);
        return true;
    }

    /**
     * Set this Job's (latest) start property
     * Job subclasses must call this method.
     * @param int $now Optional timestamp to record Default 0 (hence time())
     */
    public function record_exec_time($now = 0)
    {
        if( $this->props['id'] > 0 ) { // already saved
            $cachename = $this->lastexec_propname();
            if( $now == 0 ) { $now = time(); }
            JobOperations::record_timestamp($this->props['id'],$cachename,$now);
        }
    }

    /**
     * Get the name of the property which identifies the recorded
     * timestamp representing the latest execution of this job
     * @since 2.2.23F2
     *
     * @return string
     */
    protected function lastexec_propname()
    {
        $n = $this->props['name'];
        $m = $this->props['module'];
        return ($m) ? "Lastrun:$m:$n" : "Lastrun:$n"; // consistent prefix to facilitate global removal from table
    }

    /**
     * Get a recorded site-preference value bypassing the usual cache involvement
     * @since 2.2.23F2
     *
     * @param string $name Property identifier
     * @param string $module Optional module name
     * @param mixed $dflt Optional default value
     * @return mixed
     */
    protected function get_recorded_preference($name,$module = '',$dflt = null)
    {
        $pref = $module ? "{$module}_{$name}" : $name;
        $val = JobOperations::retrieve_timestamp(0,$pref);
        if( $val ) {
            return $val;
        }
        return $dflt;
    }
}
