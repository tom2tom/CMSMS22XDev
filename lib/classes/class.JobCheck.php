<?php
/*
Class: CMSMS\JobCheck
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, read the license online at:
https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
namespace CMSMS;

use cms_config;
use cms_siteprefs;
use CmsApp;
use CmsCoreCapabilities;
use Exception;
use ModuleOperations;
use RuntimeException;
use function audit;
use function cms_join_path;
use function debug_to_log;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;

/**
 * Lightweight alternative to JobOperations for use during every request
 * @package CMS
 * @since 2.2.23F2
 */
class JobCheck
{
	const CACHETABLE = 'jobs_record'; // aka JobOperations::CACHETABLE
	const RECORDTABLE = 'jobs'; // also the prefix of the sequence-table name

	private $db;

	public function __construct()
	{
		$this->db = CmsApp::get_instance()->GetDb();
	}

	/**
	 * Initiate background-jobs processing
	 * Formerly a method in the former CmsJobManager module
	 */
	public function initiate_background_processing()
	{
		static $_started = null;
		// ensure this method only proceeds once per request
		if ($_started) {
			return;
		}
		// abort if initiated during the processing of an async request
		if (isset($_REQUEST['cms_cron'])) {
			return;
		}
		$now = time();
		// abort if initiated too soon
		$last_trigger = (int)$this->retrieve_timestamp(0, 'last_async_trigger');
		if ($last_trigger >= $now - $this->get_async_freq()) {
			return; //ASYNCDEBUG
		}
		// abort if job-processing is not appropriate now (slightly expands upon the test above)
		if (!$this->check_for_jobs()) {
			return; //ASYNCDEBUG
		}

		$_started = true;

		$config = cms_config::get_instance();
		$url_parts = parse_url($config['admin_url']);
		if (empty($url_parts['host'])) {
			$_started = false;
			audit('', __FUNCTION__, 'No host is available for async processing');
			return;
		}

		$secure = (!empty($url_parts['scheme'])) ?
			(strcasecmp($url_parts['scheme'], 'https') == 0) :
			CmsApp::get_instance()->is_https_request();

		$transport = 'tcp'; //default, will be treated as insecure
		if ($secure) {
			$opts = stream_get_transports();
			if (in_array('tls', $opts)) {
				$transport = 'tls';
			} elseif (in_array('ssl', $opts)) { // try sslv2 and sslv3
				$transport = 'ssl';
			} else {
				return; //TODO abort message
			}
		}

		if (empty($url_parts['port'])) {
			$url_parts['port'] = ($secure) ? 443 : 80; // TODO 443 might be wrong for this site
		}

		$host = $url_parts['host'];
		$endpoint = $url_parts['path'].'/asyncprocess.php?cms_cron=1';
		$req = "GET $endpoint HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n\r\n";

		// c.f. phpmailer::getSMTPConnection() which prefers stream_socket_client with fsockopen fallback
		// stream_socket_client() allows more flexibility but allegedly,
		// some sitehosts disable that function
		if (function_exists('stream_socket_client')) {
			$remote = $transport.'://'.$host.':'.$url_parts['port'];

			if ($transport == 'tcp') {
				$context = stream_context_create();
			} else {
				//internal-use only, skip verification
				$opts = [
					$transport => [
						'allow_self_signed' => true,
						'verify_host' => false,
						'verify_peer' => false
					]
				];
				$context = stream_context_create($opts);
			}

			$this->record_timestamp(0, 'last_async_trigger', $now + 1);
			try {
				$fp = stream_socket_client($remote, $errno, $errstr, 1, STREAM_CLIENT_ASYNC_CONNECT, $context);
				if (!$fp) {
					$msg = 'Could not connect to the async processing action';
					if ($errstr) {
						$msg .= ': '.$errstr;
					}
					throw new RuntimeException($msg);
				}
				fwrite($fp, $req);
				stream_socket_shutdown($fp, STREAM_SHUT_RDWR);
			} catch(Exception $e) {
				debug_to_log('async connection exception '.$e->GetMessage());
			}
		} else {
			// try to connect using fsockopen()
			// bombs on a secure site without an effective certificate
			$remote = $transport.'://'.$host;

			$this->record_timestamp(0, 'last_async_trigger', $now + 1);
			try {
				$fp = @fsockopen($remote, $url_parts['port'], $errno, $errstr, 1.0); // fsockopen involves PHP header(s)
				if (!$fp) {
					$msg = 'Could not connect to the async processing action';
					if ($errstr) {
						$msg .= ': '.$errstr;
					}
					throw new RuntimeException($msg);
				}
				stream_set_blocking($fp, false);
				stream_set_timeout($fp, 2);
				fwrite($fp, $req);
//				$info = stream_get_meta_data($fp); //ASYNC DEBUG
				fclose($fp);
			} catch(Exception $e) {
				debug_to_log('async connection exception '.$e->GetMessage());
			}
		}
	}

	/**
	 * Report whether recorded-jobs-processing should proceed now
	 *
	 * @return bool indicating enough time has passed, and job(s) were
	 *  recorded already or at least one could be newly-recorded
	 */
	private function check_for_jobs()
	{
		// periodic check for things to process
		$lastcheck = (int)$this->retrieve_timestamp(0, 'tasks_lastcheck');
		$now = time();
		$gap = (int)$this->get_async_freq(); // 180..3600
		if ($lastcheck < $now - $gap) {
			$this->record_timestamp(0, 'tasks_lastcheck', $now);
			if ($this->db->GetOne('SELECT EXISTS(SELECT 1 FROM `'.CMS_DB_PREFIX.self::RECORDTABLE.'`)') == 1) {
				return true; // something already recorded
			}
			/*
			 * Check the existence of at least one CmsRegularTask or Job
			 * that is a candidate for recording in the jobs table.
			 * No investigation of the details of discovered class-files.
			 */
			$patn = cms_join_path(CMS_ROOT_PATH, 'lib', 'jobs', 'class.*Job.php');
			$paths = glob($patn, GLOB_NOESCAPE);
			if ($paths) {
				return true;
			}
			$patn = str_replace('*Job', '*ask', $patn);
			$paths = glob($patn, GLOB_NOESCAPE);
			if ($paths) {
				return true;
			}
			$config = cms_config::get_instance();
			$patn = cms_join_path($config['assets_path'], 'jobs', 'class.*Job.php');
			$paths = glob($patn, GLOB_NOESCAPE);
			if ($paths) {
				return true;
			}
			$ops = ModuleOperations::get_instance();
			$modules = $ops->get_modules_with_capability(CmsCoreCapabilities::TASKS);
			if ($modules) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Cache a timestamp in the CACHETABLE
	 * @see also JobOperations::record_timestamp() which this method replicates
	 *
	 * @param int $id job identifier
	 * @param string $name property identifier
	 * @param int $time the value to store
	 */
	private function record_timestamp($id, $name, $time)
	{
		$tbl = CMS_DB_PREFIX.self::CACHETABLE;
		// upsert
		$sql = "INSERT INTO $tbl(id,propname,value) SELECT ?,?,?
WHERE NOT EXISTS (SELECT 1 FROM $tbl WHERE id=? AND propname=?)";
		$dbr = $this->db->Execute($sql, [$id, $name, $time, $id, $name]);
		if (!$dbr || $this->db->Affected_Rows() == 0) {
			$sql = "UPDATE $tbl SET value=? WHERE id=? AND propname=?";
			$dbr = $this->db->Execute($sql, [$time, $id, $name]);
		}
	}

	/**
	 * Get a table-cached timestamp
	 * @see also JobOperations::retrieve_timestamp() which this method replicates
	 *
	 * @param int $id job identifier > 0 or 0 for non-job data
	 * @param string $name property identifier
	 * @return mixed int | null if not found
	 */
	private function retrieve_timestamp($id, $name)
	{
		$sql = 'SELECT value FROM '.CMS_DB_PREFIX.self::CACHETABLE.' WHERE id=? AND propname=?';
		$res = $this->db->GetOne($sql, [$id, $name]);
		return ($res > 0) ? (int)$res : null;
	}

	/**
	 * Get the (minimum) interval between successive jobs-processing operations.
	 * The interval (as minutes) might be recorded in $config data or site-preferences
	 * Otherwise a default value is used.
	 * @see also JobOperations::get_async_freq() which this method replicates
	 *
	 * @return int seconds 180 .. 3600
	 */
	private function get_async_freq()
	{
		$config = cms_config::get_instance();
		$val = $config['cmsjobmanager_asyncfreq'];
		if ($val == null) {
			$val = $config['cmsjobmgr_asyncfreq']; // deprecated name
		}
		if ($val == null) {
			$val = cms_siteprefs::get('jobs_interval', 15);
		}
		if ($val) {
			$val = max(3, min(60, (int)$val));
			return $val * 60;
		}
		return 900; // aka 15 minutes
	}
}
