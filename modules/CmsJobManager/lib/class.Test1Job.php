<?php
/*
CMSMS CmsJobManager module class: Test1Job
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CmsJobManager.module.php applies to this file.
*/

namespace CmsJobManager;
//namespace CMSMS\tasks; if directly installed for testing

use CMSMS\Async\Job;

class Test1Job extends Job
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'Test1Job';
        $this->module = 'CmsJobManager'; // if not directly installed
        $this->description = 'Creates an admin log entry';
        $this->start = strtotime('+1 day',time());
    }

    public function execute($now = 0)
    {
        $this->record_exec_time($now);
//      some_unknown_function(); // intentionally generate an error
        audit('',$this->module,'Job Test1 complete');
        debug_to_log('Job Test1 complete');
    }
}
