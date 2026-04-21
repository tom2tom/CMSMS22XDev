<?php
/*
CMSMS ModuleManager module class: ClearCacheJob
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file ModuleManager.module.php applies to this file.
*/

namespace ModuleManager;

use CMSMS\Async\Job;
use cms_siteprefs;
use const PUBLIC_CACHE_LOCATION;
use function lang_by_realm;

class ClearCacheJob extends Job
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'ClearCache';
        $this->module = 'ModuleManager';
        $this->priority = 3;
        $this->recurs = Job::RECUR_WEEKLY;
    }

    public function fill_description()
    {
        $this->description = lang_by_realm('ModuleManager','jobclear_description');
    }

    public function execute($now = 0)
    {
        if (!$now) { $now = time(); }
        $this->record_exec_time($now);
        if ($this->start <= $now) {
            $this->force_start = $now;
            $caches = glob(PUBLIC_CACHE_LOCATION.DIRECTORY_SEPARATOR.'modmgr_*.dat');
            if ($caches) {
                $mins = cms_siteprefs::get('browser_cache_expiry',60);
                $mins = max(1,(int)$mins);
                $limit = time() - ($mins * 60);
                foreach ($caches as $fp) {
                   if (is_file($fp) && filemtime($fp) < $limit) {
                        @unlink($fp);
                    }
                }
            }
        }
        return true;
    }
}
