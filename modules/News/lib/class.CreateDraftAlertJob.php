<?php
#CMSMS News module class: CreateDraftAlertJob
#(c) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#The license at the top of file News.module.php applies to this file.

namespace News;

use CmsApp;
use CMSMS\Async\Job;
use const CMS_DB_PREFIX;
use function lang_by_realm;

class CreateDraftAlertJob extends Job
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'CreateDraftAlert'; // class without namespace or 'Job'
        $this->module = 'News';
        $this->recurs = Job::RECUR_15M;
    }

    public function fill_description()
    {
        $this->description = lang_by_realm('News','draftalert_description');
    }

    public function execute($now = 0)
    {
        if (!$now) { $now = time(); }
        $this->record_exec_time($now);
        if ($this->start <= $now) {
            $this->force_start = $now;

            $db = CmsApp::get_instance()->GetDb();
            $query = 'SELECT COUNT(*) FROM '.CMS_DB_PREFIX.
            'module_news WHERE status != \'published\' AND (end_time IS NULL OR end_time >= ?)';
            $longnow = trim($db->DBTimeStamp($now),"'"); // OR date('Y-m-d H:i:s',$now);
            $num = (int)$db->GetOne($query,[$longnow]);
            if ($num > 0) {
                $alert = new DraftMessageAlert($num); // same namespace
                $alert->save();
            }
        }
        return true;
    }
}
