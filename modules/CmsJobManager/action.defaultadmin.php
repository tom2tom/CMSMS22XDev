<?php

use CMSMS\Async\CronJob;
use CmsJobManager\JobQueue;
use CmsJobManager\utils;

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

$jobs = [];
$job_objs = JobQueue::get_all_jobs();
if( $job_objs ) {
    foreach( $job_objs as $job ) {
        $obj = new stdClass();
        $obj->name = $job->name;
        $obj->module = $job->module;
        $obj->frequency = (utils::job_recurs($job)) ? $job->frequency : null;
        $obj->created = $job->created;
        $obj->start = $job->start;
        $obj->until = (utils::job_recurs($job)) ? $job->until : null;
        $obj->errors = $job->errors;
        $jobs[] = $obj;
    }
}

$list = ['' => ''];
$list[CronJob::RECUR_NONE] = '';
$list[CronJob::RECUR_15M] = $this->Lang('recur_15m');
$list[CronJob::RECUR_30M] = $this->Lang('recur_30m');
$list[CronJob::RECUR_HOURLY] = $this->Lang('recur_hourly');
$list[CronJob::RECUR_120M] = $this->Lang('recur_120m');
$list[CronJob::RECUR_180M] = $this->Lang('recur_180m');
$list[CronJob::RECUR_DAILY] = $this->Lang('recur_daily');
$list[CronJob::RECUR_WEEKLY] = $this->Lang('recur_weekly');
$list[CronJob::RECUR_MONTHLY] = $this->Lang('recur_monthly');

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;defaultadmin.tpl",null,$modname,$smarty);
$tpl->assign('jobs',$jobs);
$tpl->assign('async_freq',utils::get_async_freq());
$tpl->assign('last_processing',(int) $this->GetPreference('last_processing'));
$tpl->assign('recur_list',$list);
$tpl->assign('async_freq',utils::get_async_freq());
$tpl->display();
