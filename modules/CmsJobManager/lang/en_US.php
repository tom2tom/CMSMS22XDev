<?php
$lang['created'] = 'Logged'; // was 'Created'
$lang['errors'] = 'Errors';
$lang['evtdesc_CmsJobManager::OnJobFailed'] = 'Sent after a job is removed from the job queue after failing too many times';
$lang['evthelp_CmsJobManager::OnJobFailed'] = '<h4>Parameters:</h4>
<ul>
 <li>"job" - The CMSMS\Async\Job object that has failed</li>
</ul>';
$lang['frequency'] = 'Frequency';
$lang['friendlyname'] = 'Background Job Manager';
$lang['info_background_jobs'] = 'For details and explanation, read the module help.';
$lang['info_background_jobs2'] = 'The background jobs (if any) listed here might have been recorded before this current request. In which case, how long ago they were recorded depends on the configured \'jobs processing interval\' and when previous requests actually occurred.<br>
It is not uncommon for jobs to frequently appear in and disappear from this list.<br>
A high error count or a job which should have started but did not signals that somebody needs to investigate.';
$lang['info_no_jobs'] = 'No job is waiting to be processed';
$lang['jobs'] = 'Jobs';
$lang['moddescription'] = 'A module for managing asynchronous processing jobs.';
$lang['module'] = 'Module';
$lang['name'] = 'Name';
$lang['processing_freq'] = 'Maximum processing frequency (seconds)';
$lang['recur_120m'] = 'Every 2 hours';
$lang['recur_15m'] = 'Every 15 minutes';
$lang['recur_180m'] = 'Every 3 hours';
$lang['recur_30m'] = 'Every 30 minutes';
$lang['recur_daily'] = 'Daily';
$lang['recur_hourly'] = 'Hourly';
$lang['recur_monthly'] = 'Monthly';
$lang['recur_weekly'] = 'Weekly';
$lang['recur_yearly'] = 'Yearly';
$lang['settings'] = 'Settings';
$lang['start'] = 'Start';
$lang['until'] = 'Until';

// maybe the following is more effective in a separate file i.e. translator-friendly but English-only
$lang['help'] = <<<'EOT'
<h3>What does this do?</h3>
<p>The CMSMS core and some modules perform some operations which don't need direct user interaction or which need not be very frequent or that can take an extended time to complete.</p>
<p>This module provides the functionality for performing such operations asynchronously (in the background).</p>
<br>
<p>This module processes jobs at pre-defined intervals - at most every 3 minutes, and at least every 60 minutes (unless there is no request to trigger the procssing in that interval).  The default is 15 minutes.  This infrequent processing is to ensure reasonable performance on most websites.</p>
<h3>How do I use it?</h3>
<p>Authorized users can inspect the list of jobs currently awaiting execution.</p>
<br>
<p>Those listed jobs (if any) might have been recorded before (not during) the request which displayed the list. That can happen if an error prevented completion of a fresh snapshot, or if the latest snapshot is relatively recent. How long ago they were recorded depends on the configured processing interval (aka 'async frequency', see below) and when previous requests actually occurred.<br>
It is not uncommon for jobs to frequently appear in and disappear from the list.</p>
<br>
<p>'Logged' values displayed in the list represent when the job was recorded. Such values are displayed only when they differ from the corresponding start value.</p>
<p>'Frequency' values displayed in the list are nominal.  Actual frequencies will typically be lower, as they also depend on how often requests are processed at the site.</p>
<p>'Until' values displayed in the list are for the site's configured timezone unless otherwise indicated.</p>
<p>A high error count, or a job which should have started but did not, signals that somebody needs to investigate.<p>
<br>
<p>Authorized users can change several job-processing settings, via the Site Amin Global Settings page. Some of those settings can be pre-empted by the configuration properties described below.</p>
<h3>How do developers use it?</h3>
<p>Website developers can add Task and/or Job classes into this site's 'tasks' folder to perform additional async operations. Module developers can deploy Task and/or Job classes which are reported by the module's get_tasks() method.</p>
<br>
<p>A description can be assigned to each Job. That would be used as a tooltip in the Jobs list.<br>
A priority (1-3, default 2) can be assigned to each Job. That might be useful in congested situations.</p>
<br>
<p>Site developers can adjust the processing interval by adding into the config.php file for the site a 'cmsjobmanager_asyncfreq' property having an integer value from 3 to 60 (minutes between jobs-checks) e.g. <pre><code>$config['cmsjobmanager_asyncfreq'] = 5;</code></pre>
<br>
<p><strong>Note:</strong> It is not possible to disable asynchronous processing, because some functioning of the CMSMS core relies on such processing.</p>
<br>
<p>Site developers can override PHP's 'max_execution_time' setting to set a different timeout for processing each batch of jobs by adding into the config.php file for the site a 'cmsjobmanager_timelimit' property having an integer value from 20 to 600 (seconds). Adjust the above example accordingly.</p>
<br>
<p>On low-traffic sites, it might be desirable to ensure that background processing occurs more often than requests. If so, it is recommended that a developer sets up a cron job to regularly request an URL on the site e.g.</p>
<pre>*/3 * * * * wget -O /dev/null https://www.mysite.com/index.php?page=dojobs&showtemplate=false</pre>
<p>The referenced page would be a simple content page with no content of its own and that is not shown in the navigation menu.</p>
<h3>What about problem jobs?</h3>
<p>From time to time some job(s) might fail to complete.  CmsJobManager will remove a job from the waiting-jobs list after the job has failed a number of times.  After that the originating code can re-create the job.  If you encounter a job that continues to fail, this is a bug that should be reported in detail to the appropriate developer, and diagnosed.</p>
<h3>Support</h3>
<p>As per its license, this module is provided as-is. Please read the text of the license (see below) for the full disclaimer.</p>
<p>Discussion of this module may be found in the <a href="https://forum.cmsmadesimple.org" target="_blank">CMSMS Forum</a> or on the <a href="https://cms-made-simple.slack.com" target="_blank">CMSMS Slack channel</a> (after signing up <a href="https://www.cmsmadesimple.org/support/documentation/chat" target="_blank">here</a>).</p>
<p>To submit a Bug Report or Feature Request, please visit the <a href="https://dev.cmsmadesimple.org/projects/cmsmadesimple" target="_blank">CMSMS Forge</a>.</li>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2016 CMS Made Simple Foundation Inc <a href=\'mailto:foundation@smamadesimple.org\'>&lt;CMSMS Foundation&gt;</a>. All rights reserved.</p>
<p>This module has been released under version 2 of the <a href=\'http://www.gnu.org/licenses/licenses.html#GPL\'>GNU General Public License</a>. This module may not be distributed or used otherwise than in accordance with that license, or a later version of that licence granted by the module's distributor.</p>
EOT;

?>
