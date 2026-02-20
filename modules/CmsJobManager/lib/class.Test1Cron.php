<?php
/*
CMSMS CmsJobManager module class: Test1Cron
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CmsJobManager.module.php applies to this file.
*/

namespace CmsJobManager;

use CMSMS\Async\CronJob;

class Test1Cron extends CronJob
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'Test1Cron';
        $this->module = 'CmsJobManager';
        $this->description = 'Creates an admin log entry hourly';
        $this->recurs = parent::RECUR_HOURLY;
        $this->until = strtotime('+1 day');
    }

    public function execute($now = 0)
    {
        $this->record_exec_time($now);
//      some_unknown_function(); // intentionally generate an error
        audit('',$this->module,'Cron job Test1 complete');
        debug_to_log('Cron job Test1 complete');
    }
}
