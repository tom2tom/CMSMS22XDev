<?php
/*
CMSMS FilePicker module class: ResetCounterJob
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file FilePicker.module.php applies to this file.
*/

namespace FilePicker;

use CMSMS\Async\Job;
use CmsApp;
use const CMS_DB_PREFIX;
use function lang_by_realm;

class ResetCounterJob extends Job
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'ResetCounter';
        $this->module = 'FilePicker';
        $this->priority = 3;
        $this->recurs = Job::RECUR_MONTHLY;
    }

    public function fill_description()
    {
        $this->description = lang_by_realm('FilePicker','jobreset_description');
    }

    public function execute($now = 0)
    {
        if (!$now) { $now = time(); }
        //TODO ignore until interval is 6 months
        $this->record_exec_time($now);
        if ($this->start <= $now) {
            $this->force_start = $now;
            $db = CmsApp::get_instance()->GetDB();
            $db->Execute('UPDATE '.CMS_DB_PREFIX.'mod_filepicker_profiles_seq SET id=0 WHERE id>99999');
        }
        return true;
    }
}
