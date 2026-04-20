<?php

namespace CMSMS\jobs;

use CmsApp;
use CMSMS\Async\Job;
use CMSMS\JobOperations;
use const CMS_DB_PREFIX;
use function lang_by_realm;

//see also PruneAdminlogJob which removes all items older than a configured threshold datetime
final class ReduceAdminlogJob extends Job
{
    const LASTEXECUTE_PROPNAME = 'Lastrun:ReduceAdminlog'; // i.e. 'Lastrun:'.jobname

    protected $_queue = [];

    public function __construct()
    {
        parent::__construct();
        $this->name = 'ReduceAdminlog';
        $this->recurs = Job::RECUR_3H;
    }

    public function fill_description()
    {
        $this->description = lang_by_realm('jobs','reduceadminlog_description');
    }

    public function execute($time = 0)
    {
        $last_execute = (int)JobOperations::retrieve_timestamp($this->id,self::LASTEXECUTE_PROPNAME);

        if( !$time ) { $time = time(); }
        $this->record_exec_time($time);
        if( $this->start > $time ) return true;
        $this->force_start = $time;

        $db = CmsApp::get_instance()->GetDB();
        $table = CMS_DB_PREFIX.'adminlog';
        $mintime = max(0,$last_execute - 60,$time - 86400);
        $sql = "SELECT * FROM $table WHERE timestamp >= $mintime ORDER BY timestamp ASC";
        $dbr = $db->Execute($sql);

        $prev = [];
        while( $dbr && !$dbr->EOF() ) {
            $row = $dbr->fields;
            if( $prev && $this->is_same($prev,$row) ) {
                $this->queue_for_deletion($prev);
            }
            elseif( $this->have_queued() ) {
                $this->adjust_last();
                $this->clear_queued();
            }
            $prev = $row;
            $dbr->MoveNext();
        }
        if( $this->have_queued() ) {
            $this->adjust_last();
            $this->clear_queued();
        }
        return true;
    }

    protected function is_same($a,$b)
    {
        if( !is_array($a) || !is_array($b) ) return false;

        // ignore the timestamp
        foreach( $a as $key => $val ) {
            switch( $key ) {
            case 'timestamp':
                if( abs($b['timestamp'] - $a['timestamp']) > 3600 ) return false;
                break;
            default:
                if( $a[$key] != $b[$key] ) return false;
                break;
            }
        }
        return TRUE;
    }

    protected function queue_for_deletion($row)
    {
        $this->_queue[] = $row;
    }

    protected function have_queued()
    {
        return (count($this->_queue) > 1);
    }

    protected function adjust_last()
    {
        if( !$this->have_queued() ) return;

        $n = count($this->_queue);
        $lastrec = $this->_queue[$n - 1];
        $this->_queue = array_slice($this->_queue,0,-1);

        $db = CmsApp::get_instance()->GetDB();
        $lastrec['action'] = $lastrec['action'] . sprintf(" (repeated %d times)",$n);
        $sql = 'UPDATE '.CMS_DB_PREFIX.'adminlog SET action = ?
WHERE timestamp = ? AND user_id = ? AND username = ? AND item_id = ? AND item_name = ? AND ip_addr = ?';
        $db->Execute($sql,array($lastrec['action'],$lastrec['timestamp'],$lastrec['user_id'],$lastrec['username'],
                                $lastrec['item_id'],$lastrec['item_name'],$lastrec['ip_addr']));
    }

    protected function clear_queued()
    {
        $n = count($this->_queue);
        if( $n < 1 ) return;

        $db = CmsApp::get_instance()->GetDB();
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.'adminlog
WHERE timestamp = ? AND user_id = ? AND username = ? AND item_id = ? AND item_name = ? AND action = ? AND ip_addr = ?';
        for( $i = 0; $i < $n; $i++ ) {
            $rec = $this->_queue[$i];
            $db->Execute($sql,array($rec['timestamp'],$rec['user_id'],$rec['username'],
                                    $rec['item_id'],$rec['item_name'],$rec['action'],$rec['ip_addr']));
        }
        $this->_queue = [];
    }
}
