<?php
/*
CMSMS FileManager module class: ClearUserDirsJob
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file FileManager.module.php applies to this file.
*/

namespace FileManager;

use CMSMS\Async\Job;
use cms_userprefs;
use UserOperations;
use function lang_by_realm;

class ClearUserDirsJob extends Job
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'ClearUserDirs';
        $this->module = 'FileManager';
        $this->recurs = Job::RECUR_WEEKLY;
    }

    public function fill_description()
    {
        $this->description = lang_by_realm('FileManager','jobcleardirs_description');
    }

    public function execute($now = 0)
    {
        if( !$now ) { $now = time(); }
        $this->record_exec_time($now);
        if( $this->start <= $now ) {
            $this->force_start = $now;

            $limit = strtotime('-1 week',$now);
            $list = UserOperations::get_instance()->GetList();
            foreach( $list as $uid => $uname ) {
                $when = (int)cms_userprefs::get_for_user($uid,'filemanager_cwd_recorded',-1);
                if( $when <= $limit && $when > -1 ) {
                    cms_userprefs::remove_for_user($uid,'filemanager_cwd',true);
                }
            }
        }
        return true;
    }
}
