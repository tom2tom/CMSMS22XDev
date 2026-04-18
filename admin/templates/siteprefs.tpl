<div class="pagecontainer">

{tab_header name='general' label=lang('general') active=$tab}
{tab_header name='sitedown' label=lang('sitedown_settings') active=$tab}
{tab_header name='mail' label=lang('mail_settings') active=$tab}
{tab_header name='smarty' label=lang('smarty_settings') active=$tab}
{tab_header name='setup' label=lang('advanced') active=$tab}

{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='general'}
	<form id="siteprefform_general" method="post" action="{$formurl}">
		<div class="hidden">
			<input type="hidden" name="{$securename}" value="{$secureval}">
			<input type="hidden" name="active_tab" value="general">
		</div>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="editsiteprefs" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
				<input type="submit" name="cancel" value="{lang('cancel')}">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="sitename">{lang('sitename')}:</label> {cms_help key2='siteprefs_sitename' title=lang('sitename')}</p>
			<p class="pageinput"><input type="text" id="sitename" name="sitename" size="30" value="{$sitename}"></p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="frontendlang">{lang('frontendlang')}:</label> {cms_help key2='siteprefs_frontendlang' title=lang('frontendlang')}</p>
			<p class="pageinput">
				<select id="frontendlang" name="frontendlang" style="vertical-align:middle">
					{html_options options=$languages selected=$frontendlang}
				</select>
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="frontendwysiwyg">{lang('frontendwysiwygtouse')}:</label> {cms_help key2='siteprefs_frontendwysiwyg' title=lang('frontendwysiwygtouse')}</p>
			<p class="pageinput">
				<select id="frontendwysiwyg" name="frontendwysiwyg">
					{html_options options=$wysiwyg selected=$frontendwysiwyg}
				</select>
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="globalmetadata">{lang('globalmetadata')}:</label> {cms_help key2='siteprefs_globalmetadata' title=lang('globalmetadata')}</p>
			<p class="pageinput"><textarea id="globalmetadata" class="pagesmalltextarea" name="metadata" cols="80" rows="20">{$metadata}</textarea></p>
		</div>
		{if !empty($themes)}
			<div class="pageoverflow">
				<p class="pagetext"><label for="logintheme">{lang('master_admintheme')}:</label> {cms_help key2='siteprefs_logintheme' title=lang('master_admintheme')}</p>
				<p class="pageinput">
					<select id="logintheme" name="logintheme">
						{html_options options=$themes selected=$logintheme}
					</select>
				</p>
			</div>
		{/if}
		<div class="pageoverflow">
			<p class="pagetext"><label for="defaultdateformat">{lang('date_format_string')}:</label> {cms_help key2='siteprefs_dateformat' title=lang('date_format_string')}</p>
			<p class="pageinput">
				<input type="text" class="pagenb" id="defaultdateformat" name="defaultdateformat" size="20" maxlength="255" value="{$defaultdateformat}">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="thumbnail_width">{lang('thumbnail_width')}:</label> {cms_help key2='siteprefs_thumbwidth' title=lang('thumbnail_width')}</p>
			<p class="pageinput">
				<input type="text" class="pagenb" id="thumbnail_width" name="thumbnail_width" size="3" maxlength="3" value="{$thumbnail_width}">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="thumbnail_height">{lang('thumbnail_height')}:</label> {cms_help key2='siteprefs_thumbheight' title=lang('thumbnail_height')}</p>
			<p class="pageinput">
				<input type="text" id="thumbnail_height" class="pagenb" name="thumbnail_height" size="3" maxlength="3" value="{$thumbnail_height}">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="notice_timeout">{lang('notices_timeout')}:</label> {cms_help key2='siteprefs_noticetimeout' title=lang('notices_timeout_short')}</p>
			<p class="pageinput">
				<input type="text" id="notice_timeout" class="pagenb" name="notices_timeout" size="3" maxlength="2" value="{$notices_timeout}">
			</p>
		</div>
		{if !empty($search_modules)}
			<p class="pagetext"><label for="search_module">{lang('search_module')}:</label> {cms_help key2='settings_searchmodule' title=lang('search_module')}</p>
			<p class="pageinput">
				<select id="search_module" name="search_module">
					{html_options options=$search_modules selected=$search_module}
				</select>
			</p>
		{/if}
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="editsiteprefs" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
				<input type="submit" name="cancel" value="{lang('cancel')}">
			</p>
		</div>
	</form>

{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='sitedown'}
	<form id="siteprefform_sitedown" method="post" action="{$formurl}">
		<div class="hidden">
			<input type="hidden" name="{$securename}" value="{$secureval}">
			<input type="hidden" name="active_tab" value="sitedown">
		</div>
		<div class="information" style="display:block">{lang('info_settings_sitedown')}</div>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="editsiteprefs" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
				<input type="submit" name="cancel" value="{lang('cancel')}">
			</p>
		</div>

		<div class="pageoverflow">
			<p class="pagetext"><label for="enablesitedown">{lang('enablesitedown')}:</label> {cms_help key2='settings_enablesitedown' title=lang('enablesitedown')}</p>
			<p class="pageinput">
				<select id="enablesitedown" name="enablesitedownmessage">
					{cms_yesno selected=$enablesitedownmessage}
				</select>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="enablewysiwyg">{lang('enablewysiwyg')}:</label> {cms_help key2='settings_enablewysiwyg' title=lang('enablewysiwyg')}</p>
			<p class="pageinput">
				<select id="enablewysiwyg" name="use_wysiwyg">
					{cms_yesno selected=$use_wysiwyg}
				</select>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="sitedownmessage">{lang('sitedownmessage')}:</label> {cms_help key2='settings_sitedownmessage' title=lang('sitedownmessage')}</p>
			<p class="pageinput">{$textarea_sitedownmessage}</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="sitedownexcludeadmins">{lang('sitedownexcludeadmins')}:</label> {cms_help key2='settings_sitedownexcludeadmins' title=lang('sitedownexcludeadmins')}</p>
			<p class="pageinput">
				<select id="sitedownexcludeadmins" name="sitedownexcludeadmins">
					{cms_yesno selected=$sitedownexcludeadmins}
				</select>
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="sitedownexcludes">{lang('sitedownexcludes')}:</label> {cms_help key2='settings_sitedownexcludes' title=lang('sitedownexcludes')}</p>
			<p class="pageinput">
				<input id="sitedownexcludes" type="text" name="sitedownexcludes" size="50" maxlength="255" value="{$sitedownexcludes|cms_escape}">
				<br><strong>{lang('your_ipaddress')}:</strong>&nbsp;<span style="color:red">{$site_ipaddr}</span><br>{lang('info_sitedownexcludes')}
			</p>
		</div>
		<div class="pageoverflow">
			<div class="pageinput">
				<input type="submit" name="editsiteprefs" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
				<input type="submit" name="cancel" value="{lang('cancel')}">
			</div>
		</div>
	</form>

{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='mail'}
<script>
function on_mailer() {
  var v = $('#mailer').val();
  if (v == 'mail') {
    $('#set_smtp').find('input,select').prop('disabled',true);
    $('#set_sendmail').find('input,select').prop('disabled',true);
  } else if (v == 'smtp') {
    $('#set_smtp').find('input,select').prop('disabled',false);
    $('#set_sendmail').find('input,select').prop('disabled',true);
  } else if (v == 'sendmail') {
    $('#set_sendmail').find('input,select').prop('disabled',false);
    $('#set_smtp').find('input,select').prop('disabled',true);
  }
}
$(function() {
  on_mailer();
  $('#mailertest').on('click', function() {
    $('#testpopup').dialog({
      width: 'auto',
      modal: true
    });
    return false;
  });
  $('#testcancel').on('click', function() {
    $('#testpopup').dialog('close');
    return false;
  });
  $('#testsend').on('click', function() {
    $('#testpopup').dialog('close');
    $(this).closest('form').trigger('submit');
  });
  $('#mailsubmit, #mailsubmit2').on('click', function() {
    // also submit disabled-element values
    var fm = $('#siteprefform_mail2');
    var sel = fm.find(':disabled');
    if (sel.length > 0) {
      sel.each(function() {
        var hin = '<input type="hidden" name="'+this.name+'" value="'+this.value+'">';
        fm.append(hin);
      });
    }
  });
  $('#mailer').on('change', function() {
    on_mailer();
  });
});
</script>

	<div id="testpopup" title="{lang('title_mailtest')}" style="display:none">
		<form id="siteprefform_mail" method="post" action="{$formurl}">
			<div class="hidden">
				<input type="hidden" name="{$securename}" value="{$secureval}">
				<input type="hidden" name="active_tab" value="mail">
			</div>
			<div class="information">{lang('info_mailtest')}</div>
			<div class="pageoverflow">
				<p class="pagetext"><label for="testaddress">{lang('settings_testaddress')}:</label> {cms_help key2='settings_mailtest_testaddress' title=lang('settings_testaddress')}</p>
				<p class="pageinput">
					<input type="text" id="testaddress" name="mailtest_testaddress" size="50" maxlength="255">
				</p>
			</div>
			<div class="pageoverflow">
				<p class="pageinput">
					<input type="submit" id="testsend" name="testmail" data-ui-icon="ui-icon-mail-closed" value="{lang('sendtest')}">
					<input type="submit" id="testcancel" name="cancel" value="{lang('cancel')}">
				</p>
			</div>
		</form>
	</div>

	<form id="siteprefform_mail2" method="post" action="{$formurl}">
		<div class="hidden">
			<input type="hidden" name="{$securename}" value="{$secureval}">
			<input type="hidden" name="active_tab" value="mail">
		</div>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" id="mailsubmit" name="editsiteprefs" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
				<input type="submit" id="mailertest" name="testemail" data-ui-icon="ui-icon-gear" value="{lang('test')}">
				<input type="submit" name="cancel" value="{lang('cancel')}">
			</p>
		</div>

		<fieldset id="set_general">
			<legend>{lang('general_settings')}</legend>
				<div class="pageoverflow">
					<p class="pagetext"><label for="mailer">{lang('settings_mailer')}:</label> {cms_help key2='settings_mailprefs_mailer' title=lang('settings_mailer')}</p>
					<p class="pageinput">
						<select id="mailer" name="mailprefs_mailer">
							{html_options options=$maileritems selected=$mailprefs.mailer}
						</select>
					</p>
				</div>
				<div class="pageoverflow">
					<p class="pagetext"><label for="from">{lang('settings_mailfrom')}:</label> {cms_help key2='settings_mailprefs_from' title=lang('settings_mailfrom')}</p>
					<p class="pageinput">
						<input type="text" id="from" name="mailprefs_from" value="{$mailprefs.from}" size="50" maxlength="255">
					</p>
				</div>
				<div class="pageoverflow">
					<p class="pagetext"><label for="fromuser">{lang('settings_mailfromuser')}:</label> {cms_help key2='settings_mailprefs_fromuser' title=lang('settings_mailfromuser')}</p>
					<p class="pageinput">
						<input type="text" id="fromuser" name="mailprefs_fromuser" value="{$mailprefs.fromuser}" size="50" maxlength="255">
					</p>
				</div>
		</fieldset>

		<fieldset id="set_smtp">
			<legend>{lang('smtp_settings')}</legend>
				<div class="pageoverflow">
					<p class="pagetext"><label for="host">{lang('settings_smtphost')}:</label> {cms_help key2='settings_mailprefs_smtphost' title=lang('settings_smtphost')}</p>
					<p class="pageinput">
						<input type="text" id="host" name="mailprefs_host" value="{$mailprefs.host}" size="50" maxlength="255">
					</p>
				</div>

				<div class="pageoverflow">
					<p class="pagetext"><label for="port">{lang('settings_smtpport')}:</label> {cms_help key2='settings_mailprefs_smtpport' title=lang('settings_smtpport')}</p>
					<p class="pageinput">
						<input type="text" id="port" name="mailprefs_port" value="{$mailprefs.port}" size="6" maxlength="8">
					</p>
				</div>

				<div class="pageoverflow">
					<p class="pagetext"><label for="timeout">{lang('settings_smtptimeout')}:</label> {cms_help key2='settings_mailprefs_smtptimeout' title=lang('settings_smtptimeout')}</p>
					<p class="pageinput">
						<input type="text" id="timeout" name="mailprefs_timeout" value="{$mailprefs.timeout}" size="6" maxlength="8">
					</p>
				</div>

				<fieldset>
					<legend>{lang('settings_authentication')}</legend>
					<div class="pageoverflow">
						<p class="pagetext"><label for="smtpauth">{lang('settings_smtpauth')}:</label> {cms_help key2='settings_mailprefs_smtpauth' title=lang('settings_smtpauth')}</p>
						<p class="pageinput">
							<select id="smtpauth" name="mailprefs_smtpauth">
								{cms_yesno selected=$mailprefs.smtpauth}
							</select>
						</p>
					</div>

					<div class="pageoverflow">
						<p class="pagetext"><label for="secure">{lang('settings_authsecure')}:</label> {cms_help key2='settings_mailprefs_smtpsecure' title=lang('settings_authsecure')}</p>
						<p class="pageinput">
							<select id="secure" name="mailprefs_secure">
								{html_options options=$secure_opts selected=$mailprefs.secure}
							</select>
						</p>
					</div>

					<div class="pageoverflow">
						<p class="pagetext"><label for="smtpautotls">{lang('settings_smtpautotls')}:</label> {cms_help key2='settings_mailprefs_smtp_smtpautotls' title=lang('settings_smtpautotls')}</p>
						<p class="pageinput">
							<select id="smtpautotls" name="mailprefs_smtpautotls">
								{cms_yesno selected=$mailprefs.smtpautotls}
								{*html_options options=$secure_opts selected=$mailprefs.secure*}
							</select>
						</p>
					</div>

					<div class="pageoverflow">
						<p class="pagetext"><label for="username">{lang('settings_authusername')}:</label> {cms_help key2='settings_mailprefs_smtpusername' title=lang('settings_authusername')}</p>
						<p class="pageinput">
							<input type="text" id="username" name="mailprefs_username" value="{$mailprefs.username}" size="50" maxlength="255">
						</p>
					</div>

					<div class="pageoverflow">
						<p class="pagetext"><label for="password">{lang('settings_authpassword')}:</label> {cms_help key2='settings_mailprefs_smtppassword' title=lang('settings_authpassword')}</p>
						<p class="pageinput">
							<input type="password" id="password" name="mailprefs_password" value="{$mailprefs.password}" size="50" maxlength="100">
						</p>
					</div>
				</fieldset>
		</fieldset>

		<fieldset id="set_sendmail">
			<legend>{lang('sendmail_settings')}</legend>
				<div class="pageoverflow">
					<p class="pagetext"><label for="sendmail">{lang('settings_sendmailpath')}:</label> {cms_help key2='settings_mailprefs_sendmail' title=lang('settings_sendmailpath')}</p>
					<p class="pageinput">
						<input type="text" id="sendmail" name="mailprefs_sendmail" value="{$mailprefs.sendmail}" size="50" maxlength="255">
					</p>
				</div>
		</fieldset>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" id="mailsubmit2" name="editsiteprefs" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
				<input type="submit" name="cancel" value="{lang('cancel')}">
			</p>
		</div>
	</form>
{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='smarty'}
	<form id="siteprefform_smarty" method="post" action="{$formurl}">
		<div class="hidden">
			<input type="hidden" name="{$securename}" value="{$secureval}">
			<input type="hidden" name="active_tab" value="smarty">
		</div>
		<div class="pageoverflow">
			<div class="pageinput">
				<input type="submit" name="editsiteprefs" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
				<input type="submit" name="cancel" value="{lang('cancel')}">
			</div>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="smartycache">{lang('prompt_use_smartycaching')}:</label> {cms_help key2='settings_smartycaching' title=lang('prompt_use_smartycaching')}</p>
			<p class="pageinput">
				<select id="smartycache" name="use_smartycache">
					{html_options options=$yesno selected=$use_smartycache}
				</select>
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="txtfrontcache">{lang('smarty_cache_expiry2')}:</label> {cms_help key2='settings_smartycache_frontlife' title=lang('smarty_cache_expiry2')}</p>
			<p class="pageinput">
				<input type="text" id="txtfrontcache" name="SmartyFrontcacheLife" value="{$SmartyFrontcacheLife}" size="3" maxlength="4">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="txtadmincache">{lang('smarty_cache_expiry1')}:</label> {cms_help key2='settings_smartycache_adminlife' title=lang('smarty_cache_expiry1')}</p>
			<p class="pageinput">
				<input type="text" id="txtadmincache" name="SmartyAdmincacheLife" value="{$SmartyAdmincacheLife}" size="3" maxlength="4">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="compilecheck">{lang('prompt_smarty_compilecheck')}:</label> {cms_help key2='settings_smartycompilecheck' title=lang('prompt_smarty_compilecheck')}</p>
			<p class="pageinput">
				<select id="compilecheck" name="use_smartycompilecheck">
					{html_options options=$yesno selected=$use_smartycompilecheck}
				</select>
			</p>
		</div>
	</form>
{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='setup'}
	<form id="siteprefform_setup" method="post" action="{$formurl}">
		<div class="hidden">
			<input type="hidden" name="{$securename}" value="{$secureval}">
			<input type="hidden" name="active_tab" value="setup">
		</div>
		<div class="pageoverflow">
			<div class="pageinput">
				<input type="submit" name="editsiteprefs" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
				<input type="submit" name="cancel" value="{lang('cancel')}">
			</div>
		</div>
		<fieldset>
			<legend>{lang('browser_cache_settings')}</legend>
			<div class="pageoverflow">
				<p class="pagetext"><label for="allow_browser_cache">{lang('allow_browser_cache')}:</label> {cms_help key2='settings_browsercache' title=lang('allow_browser_cache')}</p>
				<p class="pageinput">
					<select id="allow_browser_cache" name="allow_browser_cache">
						{cms_yesno selected=$allow_browser_cache}
					</select>
				</p>
			</div>
			<div class="pageoverflow">
				<p class="pagetext"><label for="browser_expiry">{lang('browser_cache_expiry')}:</label> {cms_help key2='settings_browsercache_expiry' title=lang('browser_cache_expiry')}</p>
				<p class="pageinput">
					<input type="text" id="browser_expiry" name="browser_cache_expiry" value="{$browser_cache_expiry}" size="6" maxlength="10">
				</p>
			</div>
		</fieldset>

		<fieldset>
			<legend>{lang('server_cache_settings')}</legend>
			<div class="pageoverflow">
				<p class="pagetext"><label for="autoclearcache2">{lang('autoclearcache2')}:</label> {cms_help key2='settings_autoclearcache' title=lang('autoclearcache2')}</p>
				<p class="pageinput">
					<input type="text" id="autoclearcache2" name="auto_clear_cache_age" size="4" value="{$auto_clear_cache_age}" maxlength="4">
				</p>
			</div>
		</fieldset>
		<fieldset>
			<legend>{lang('general_operation_settings')}</legend>
			<div class="pageoverflow">
				<p class="pagetext"><label for="umask">{lang('global_umask')}:</label> {cms_help key2='settings_umask' title=lang('global_umask')}</p>
				<p class="pageinput">
					<input type="text" id="umask" name="global_umask" size="4" value="{$global_umask}">
				</p>
			</div>
		{if isset($testresults)}
			<div class="pageoverflow">
				<p class="pagetext"><label>{lang('results')}</label></p>
				<p class="pageinput"><strong>{$testresults}</strong></p>
			</div>
		{/if}
			<div class="pageoverflow">
				<p class="pageinput"><input type="submit" name="testumask" data-ui-icon="ui-icon-gear" value="{lang('test')}"></p>
			</div>
			<div class="pageoverflow">
				<p class="pagetext"><label for="lock_timeout">{lang('admin_lock_timeout')}:</label> {cms_help key2='settings_lock_timeout' title=lang('admin_lock_timeout')}</p>
				<p class="pageinput">
					<input type="text" id="lock_timeout" name="lock_timeout" size="3" value="{$lock_timeout}">
				</p>
			</div>
			<div class="pageoverflow">
				<p class="pagetext"><label for="adminlog">{lang('adminlog_lifetime')}:</label> {cms_help key2='settings_adminlog_lifetime' title=lang('adminlog_lifetime')}</p>
				<p class="pageinput">
					<select id="adminlog" name="adminlog_lifetime">
						{html_options options=$adminlog_options selected=$adminlog_lifetime}
					</select>
				</p>
			</div>
			<div class="pageoverflow">
				<p class="pagetext"><label for="checkversion">{lang('checkversion')}:</label> {cms_help key2='settings_checkversion' title=lang('checkversion')}</p>
				<p class="pageinput">
					<select id="checkversion" name="checkversion">
						{cms_yesno options=$checkversion selected=$checkversion}
					</select>
				</p>
			</div>
		</fieldset>
{if !empty($pjobs)}
		<fieldset>
			<legend>{lang('async_settings')}</legend>
			<div class="pageoverflow">
				<p class="pagetext"><label for="txtintrvl">{lang('settings_jobsinterval')}:</label> {cms_help key2='siteprefs_jobsinterval' title=lang('settings_jobsinterval')}</p>
				<p class="pageinput">
					<input type="text" id="txtintrvl" name="jobs_interval" size="3" value="{$jobs_interval}" maxlength="3">
				</p>
			</div>
			<div class="pageoverflow">
				<p class="pagetext"><label for="txttimo">{lang('settings_jobstimeout')}:</label> {cms_help key2='siteprefs_jobstimeout' title=lang('settings_jobstimeout')}</p>
				<p class="pageinput">
					<input type="text" id="txttimo" name="jobs_timeout" size="3" value="{$jobs_timeout}" maxlength="3">
				</p>
			</div>
			<div class="pageoverflow">
				<p class="pagetext"><label for="txtmxers">{lang('settings_jobmaxerrs')}:</label> {cms_help key2='siteprefs_jobmaxerrs' title=lang('settings_jobmaxerrs')}</p>
				<p class="pageinput">
					<input type="text" id="txtmxers" name="job_maxerrs" size="3" value="{$job_maxerrs}" maxlength="2">
				</p>
			</div>
		</fieldset>
{/if}
{if !empty($privatePath)}
		<div class="pageoverflow">
			<p class="pagetext"><label for="ppath">{lang('protected_data_path')}:</label> {cms_help key2='siteprefs_protected_path' title=lang('protected_data_path')}</p>
			<p class="pageinput">
				<input type="text" id="ppath" name="privatePath" size="50" value="{$privatePath}">
			</p>
			<p class="warning">{lang('warn_protected_path')}</p>
		</div>
{/if}
		<div class="pageoverflow">
			<div class="pageinput">
				<input type="submit" name="editsiteprefs" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
				<input type="submit" name="cancel" value="{lang('cancel')}">
			</div>
		</div>
	</form>
{tab_end}

</div>
