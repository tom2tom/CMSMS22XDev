<?php
/*
CMSMS CmsJobManager module class: TestRecurringJob
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CmsJobManager.module.php applies to this file.
*/

namespace CmsJobManager; //if a module task
//namespace CMSMS\tasks; //if directly installed for testing

use CMSMS\Async\CronJob;
use cms_siteprefs;

class TestRecurringJob extends CronJob
{
    private $reps;

    public function __construct()
    {
        parent::__construct();
        $this->name = 'TestRecurringJob';
        $this->module = 'CmsJobManager'; // if a module task
        $this->recurs = CronJob::RECUR_15M;
        $this->until = strtotime('+1 day',time());
        $this->description = 'Bumps a cached counter';
        $this->reps = $this->get_recorded_preference('recurjobtest1','CmsJobManager',0);
    }

    public function execute($now = 0)
    {
        if ($now == 0) $now = time();
        $this->record_exec_time($now);
        $num = $this->reps + 1;
        if ($num <= 5) {
            $this->reps = $num;
            $this->until = $now + 7200; // another 2 hrs from now
            $this->set_recorded_preference('recurjobtest1','CmsJobManager',$num);
        } else {
            $this->reps = 0;
            $this->until = strtotime('+1 day',$now);
            $this->delete_recorded_preference('recurjobtest1','CmsJobManager');
        }
        $this->save();
        return true;
    }
}
