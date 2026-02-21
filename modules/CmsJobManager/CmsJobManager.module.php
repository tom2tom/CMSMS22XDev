<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: CmsJobManager
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
# A core module for CMS Made Simple to allow management of asynchronous
# jobs and cron jobs.
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
#END_LICENSE
/*
NOTE background processing is 'core-business' of CMSMS
The functionality of this module could readily be migrated to core
classes, methods and scripts, to reduce memory-load at least
*/
use CMSMS\Async\Job;

final class CmsJobManager extends CMSModule
{
    const EVT_ONFAILEDJOB = 'OnJobFailed';

    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'siteadmin'; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return ''; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetVersion() { return '0.2.0'; }
    public function HandlesEvents() { return TRUE; }
    public function HasAdmin() { return TRUE; }
    public function LazyLoadAdmin() { return TRUE; }
    public function LazyLoadFrontend() { return TRUE; }
    public function MinimumCMSVersion() { return '2.2.23F2'; }
    public function VisibleToAdminUser() { return $this->CheckPermission('Manage Jobs'); }

    public function HasCapability($capability, $params = array())
    {
        switch( $capability ) {
        case CmsCoreCapabilities::TASKS:
            return FALSE; // TRUE when cleanup-Job implemented
        }
        return FALSE;
    }

    /**
     * @ignore
     */
    public function get_tasks()
    {
/* Job not yet complete
        $fp = cms_join_path(__DIR__,'lib','class.PastExecJob.php');
        if( func_num_args() > 0 && func_get_arg(0) ) { // want filepath(s)
            return [$fp];
        } else {
            require_once($fp);
            return [new CmsJobManager\PastExecJob()];
        }
*/
        return [];
    }

    /**
     * @ignore
     */
    public function DoEvent($originator,$eventname,&$params)
    {
        if( $originator == 'Core' && $eventname == 'ModuleUninstalled' ) {
            $modname = trim($params['name']);
            if( $modname ) {
                $db = CmsApp::get_instance()->GetDb();
                $db->Execute('DELETE FROM '.CMS_DB_PREFIX.Job::RECORDTABLE.' WHERE module=?',[$modname]);
            }
        }
    }

    /**
     * Get help for the 'OnJobFailed' event
     * @param string $name 'OnJobFailed'
     * @return string
     */
    public function GetEventHelp($name)
    {
        return $this->Lang('evthelp_'.$name);
    }

    /**
     * Get description of the OnJobFailed event
     * @param string $name 'OnJobFailed'
     * @return string
     */
    public function GetEventDescription($name)
    {
        return $this->Lang('evtdesc_'.$name);
    }
} // class
