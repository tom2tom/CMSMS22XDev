<?php
// stub for not-yet-updated Job classes and tasks
namespace CMSMS\Async;
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Job.php';
class_alias('\CMSMS\Async\Job','\CMSMS\Async\CronJob',false);
