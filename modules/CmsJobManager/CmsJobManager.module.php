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

use CMSMS\Async\Job;
use CMSMS\JobOperations;

final class CmsJobManager extends CMSModule
{
    const EVT_ONFAILEDJOB = 'OnJobFailed';
    const LOCKPREF = 'jobs_lock';

    private $_lock = 0; // timestamp (replicated in module-preference LOCKPREF)

    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetVersion() { return '0.2.0'; }
    public function MinimumCMSVersion() { return '2.2.23F2'; } // for migrated permission and Jobs processing
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return ''; }
    public function HasAdmin() { return TRUE; }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'siteadmin'; }
    public function IsPluginModule() { return TRUE; } // doesn't actually process tags, but this method fakes the async processing
    public function LazyLoadFrontend() { return FALSE; } // TODO TRUE might be ok now
    public function LazyLoadAdmin() { return FALSE; } // ditto
    public function VisibleToAdminUser() { return $this->CheckPermission('Manage Jobs'); }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function HandlesEvents() { return TRUE; }

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
     * Get help for the OnJobFailed event
     */
    public function GetEventHelp($name)
    {
        return $this->Lang('evthelp_'.$name);
    }

    /**
     * Get decription of the OnJobFailed event
     */
    public function GetEventDescription($name)
    {
        return $this->Lang('evtdesc_'.$name);
    }

    /**
     * Acquire a job-processing lock
     * This is essentially to prevent concurrent processing of jobs on
     * threaded web-servers. The lock does not apply to any particular job
     */
    protected function lock()
    {
        $this->_lock = time();
        $this->SetPreference(self::LOCKPREF,$this->_lock);
    }

    /**
     * @ignore
     */
    protected function unlock()
    {
        $this->_lock = 0;
        $this->RemovePreference(self::LOCKPREF);
    }

    /**
     * @ignore
     */
    protected function is_locked()
    {
        $this->_lock = (int)$this->GetPreference(self::LOCKPREF);
        return ($this->_lock > 0);
    }

    /**
     * @ignore
     */
    protected function lock_expired()
    {
        $this->_lock = (int)$this->GetPreference(self::LOCKPREF);
        if( $this->_lock > 0 ) {
            return ($this->_lock < time() - JobOperations::get_batch_timeout());
        }
        return FALSE;
    }

    /**
     * Initiate background-jobs processing
     */
    public function trigger_async_processing()
    {
        static $_started = null;
        // ensure this method only proceeds once per request
        if( $_started ) {
            return;
        }
        // abort if initiated during the processing of an async request
        if( isset($_REQUEST['cms_cron']) ) {
            return;
        }
        $now = time();
        // abort if initiated too soon
        $last_trigger = (int) $this->GetPreference('last_async_trigger');
        if( $last_trigger >= $now - JobOperations::get_async_freq() ) {
            return;
        }
        // abort if job-processing is not appropriate now (slightly expands upon the test above)
        if( !JobOperations::check_for_jobs() ) {
            return;
        }

        $_started = TRUE;
        // initiate a frontend url cuz there will never be a logged-in user
        // and the async handler (preprocess_mact()) looks for a frontend-specific actionid
        $returnid = ContentOperations::get_instance()->GetDefaultContent();
        $url_parts = parse_url($this->create_url('__','process',$returnid));
        if( empty($url_parts['host']) ) {
            $_started = FALSE;
            audit('',$this->GetName(),'No host is available for async processing');
            return;
        }

        $secure = (!empty($url_parts['scheme'])) ?
            (strcasecmp($url_parts['scheme'],'https') == 0) :
            CmsApp::get_instance()->is_https_request();

        $transport = 'tcp'; //default, will be treated as insecure
        if( $secure ) {
            $opts = stream_get_transports();
            if( in_array('tls',$opts) ) {
                $transport = 'tls';
            }
            elseif( in_array('ssl',$opts) ) { // try sslv2 and sslv3
                $transport = 'ssl';
            }
        }

        if( empty($url_parts['port']) ) {
            $url_parts['port'] = ($secure) ? 443 : 80; // TODO 443 might be wrong for this site
        }

        $host = $url_parts['host'];
        $clean = str_replace('&amp;','&',$url_parts['query']);
        $endpoint = $url_parts['path'].'?'.$clean.'&cms_cron=1'; // former &showtemplate=false seems bad for async-request processing
        $req = "GET $endpoint HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n\r\n";

        // c.f. phpmailer::getSMTPConnection() which prefers stream_socket_client with fsockopen fallback
        // stream_socket_client() allows more flexibility but allegedly,
        // some sitehosts disable that function
        if( function_exists('stream_socket_client') ) {
            $remote = $transport.'://'.$host.':'.$url_parts['port'];

            if( $transport == 'tcp' ) {
                $context = stream_context_create();
            }
            else {
                //internal-use only, skip verification
                $opts = [
                    $transport => [
                        'allow_self_signed' => TRUE,
                        'verify_host' => FALSE,
                        'verify_peer' => FALSE
                    ]
                ];
                $context = stream_context_create($opts);
            }

            $this->SetPreference('last_async_trigger',$now + 1);
            try {
                $fp = stream_socket_client($remote,$errno,$errstr,1,STREAM_CLIENT_ASYNC_CONNECT,$context);
                if( !$fp ) {
                    $msg = 'Could not connect to the async processing action';
                    if( $errstr ) $msg .= ': '.$errstr;
                    throw new RuntimeException($msg);
                }
                fwrite($fp,$req);
                stream_socket_shutdown($fp,STREAM_SHUT_RDWR);
            }
            catch( Exception $e ) {
                debug_to_log('async connection exception '.$e->GetMessage());
            }
        }
        else {
            // try to connect using fsockopen()
            // bombs on a secure site without an effective certificate
            $remote = $transport.'://'.$host;

            $this->SetPreference('last_async_trigger',$now + 1);
            try {
                $fp = @fsockopen($remote,$url_parts['port'],$errno,$errstr,1.0); // fsockopen involves PHP header(s)
                if( !$fp ) {
                    $msg = 'Could not connect to the async processing action';
                    if( $errstr ) $msg .= ': '.$errstr;
                    throw new RuntimeException($msg);
                }
                stream_set_blocking($fp,FALSE);
                stream_set_timeout($fp,2);
                fwrite($fp,$req);
                fclose($fp);
            }
            catch( Exception $e ) {
                debug_to_log('async connection exception '.$e->GetMessage());
            }
        }
    }
} // class
