<div class="pagecontainer">

{tab_header name='general' label=lang('general_settings') active=$tab}
{tab_header name='editcontent' label=lang('editcontent_settings') active=$tab}
{tab_header name='sitedown' label=lang('sitedown_settings') active=$tab}
{tab_header name='mail' label=lang('mail_settings') active=$tab}
{tab_header name='setup' label=lang('setup') active=$tab}
{tab_header name='smarty' label=lang('smarty_settings') active=$tab}

{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='general'}
	<form id="siteprefform_general" method="post" action="{$formurl}">
		<div>
			<input type="hidden" name="{$SECURE_PARAM_NAME}" value="{$CMS_USER_KEY}">
			<input type="hidden" name="active_tab" value="general">
			<input type="hidden" name="editsiteprefs" value="true">
		</div>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
			<br>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="sitename">{lang('sitename')}:</label> {cms_help key2='siteprefs_sitename' title=lang('sitename')}</p>
			<p class="pageinput"><input type="text" id="sitename" class="pagesmalltextarea" name="sitename" size="30" value="{$sitename}"></p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="frontendlang">{lang('frontendlang')}:</label> {cms_help key2='siteprefs_frontendlang' title=lang('frontendlang')}</p>
			<p class="pageinput">
				<select id="frontendlang" name="frontendlang" style="vertical-align:middle;">
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
				<input class="pagenb" id="defaultdateformat" type="text" name="defaultdateformat" size="20" maxlength="255" value="{$defaultdateformat}">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="thumbnail_width">{lang('thumbnail_width')}:</label> {cms_help key2='siteprefs_thumbwidth' title=lang('thumbnail_width')}</p>
			<p class="pageinput">
				<input class="pagenb" id="thumbnail_width" type="text" name="thumbnail_width" size="3" maxlength="3" value="{$thumbnail_width}">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="thumbnail_height">{lang('thumbnail_height')}:</label> {cms_help key2='siteprefs_thumbheight' title=lang('thumbnail_height')}</p>
			<p class="pageinput">
				<input id="thumbnail_height" class="pagenb" type="text" name="thumbnail_height" size="3" maxlength="3" value="{$thumbnail_height}">
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
			<br>
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
		</div>
	</form>

{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='editcontent'}
	<form id="siteprefform_editcontent" method="post" action="{$formurl}">
		<div>
			<input type="hidden" name="{$SECURE_PARAM_NAME}" value="{$CMS_USER_KEY}">
			<input type="hidden" name="active_tab" value="editcontent">
			<input type="hidden" name="editsiteprefs" value="true">
		</div>
		{if !$pretty_urls}
			<div class="warning" style="display: block;">{lang('warn_nosefurl')}&nbsp;&nbsp;{cms_help key2='settings_nosefurl' title=lang('warn_nosefurl')}</div>
		{/if}
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
			<br>
		</div>
		{if $pretty_urls}
			<div class="pageoverflow">
				<p class="pagetext"><label for="content_autocreate_urls">{lang('content_autocreate_urls')}:</label> {cms_help key2='settings_autocreate_url' title=lang('content_autocreate_urls')}</p>
				<p class="pageinput">
					<select id="content_autocreate_urls" name="content_autocreate_urls">
						{html_options options=$yesno selected=$content_autocreate_urls}
					</select>
				</p>
			</div>
		{/if}
		{if $pretty_urls}
			<div class="pageoverflow">
				<p class="pagetext"><label for="content_autocreate_flaturls">{lang('content_autocreate_flaturls')}:</label> {cms_help key2='settings_autocreate_flaturls' title=lang('content_autocreate_flaturls')}</p>
				<p class="pageinput">
					<select id="content_autocreate_flaturls" name="content_autocreate_flaturls">
						{html_options options=$yesno selected=$content_autocreate_flaturls}
					</select>
				</p>
			</div>
		{/if}
		{if $pretty_urls}
			<div class="pageoverflow">
				<p class="pagetext"><label for="content_mandatory_urls">{lang('content_mandatory_urls')}:</label> {cms_help key2='settings_mandatory_urls' title=lang('content_mandatory_urls')}</p>
				<p class="pageinput">
					<select id="content_mandatory_urls" name="content_mandatory_urls">
						{html_options options=$yesno selected=$content_mandatory_urls}
					</select>
				</p>
			</div>
		{/if}
		<div class="pageoverflow">
			<p class="pagetext"><label for="disallowed_contenttypes">{lang('disallowed_contenttypes')}:</label> {cms_help key2='settings_badtypes' title=lang('disallowed_contenttypes')}</p>
			<p class="pageinput">
				<select id="disallowed_contenttypes" name="disallowed_contenttypes[]" multiple size="5">
					{html_options options=$all_contenttypes selected=$disallowed_contenttypes}
				</select>
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="basic_attributes">{lang('basic_attributes')}:</label> {cms_help key2='settings_basicattribs2' title=lang('basic_attributes')}</p>
			<p class="pageinput">
				<select id="basic_attributes" class="multicolumn" name="basic_attributes[]" multiple size="5">
					{CmsFormUtils::create_option($all_attributes,$basic_attributes)}{*cms_html_options options=$all_attributes selected=$basic_attributes*}
				</select>
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="imagefield_path">{lang('content_imagefield_path')}:</label> {cms_help key2='settings_imagefield_path' title=lang('content_imagefield_path')}</p>
			<p class="pageinput">
				<input id="imagefield_path" type="text" name="content_imagefield_path" size="50" maxlength="255" value="{$content_imagefield_path|cms_escape}">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="thumbfield_path">{lang('content_thumbnailfield_path')}:</label> {cms_help key2='settings_thumbfield_path' title=lang('content_thumbnailfield_path')}</p>
			<p class="pageinput">
				<input id="thumbfield_path" type="text" name="content_thumbnailfield_path" size="50" maxlength="255" value="{$content_thumbnailfield_path|cms_escape}">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="contentimage_path">{lang('contentimage_path')}:</label> {cms_help key2='settings_contentimage_path' title=lang('contentimage_path')}</p>
			<p class="pageinput">
				<input type="text" id="contentimage_path" name="contentimage_path" size="50" maxlength="255" value="{$contentimage_path|cms_escape}">
			</p>
		</div>
		<div class="pageoverflow">
			<p class="pagetext"><label for="cssnameisblockname">{lang('cssnameisblockname')}:</label> {cms_help key2='settings_cssnameisblockname' title=lang('cssnameisblockname')}</p>
			<p class="pageinput">
				<select id="cssnameisblockname" name="content_cssnameisblockname">
					{cms_yesno selected=$content_cssnameisblockname}
				</select>
			</p>
		</div>
		<div class="pageoverflow">
			<br>
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
		</div>
	</form>

{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='sitedown'}
	<form id="siteprefform_sitedown" method="post" action="{$formurl}">
		<div>
			<input type="hidden" name="{$SECURE_PARAM_NAME}" value="{$CMS_USER_KEY}">
			<input type="hidden" name="active_tab" value="sitedown">
			<input type="hidden" name="editsiteprefs" value="true">
		</div>
		<div class="information" style="display: block;">{lang('info_settings_sitedown')}</div>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
			<br>
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
				<br><strong>{lang('your_ipaddress')}:</strong>&nbsp;<span style="color:red">{$site_ipaddr}</span><br>{$lang_info_sitedownexcludes}
			</p>
		</div>
		<br>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
		</div>
	</form>

{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='mail'}
<script>
function on_mailer()
{
  var v = $('#mailer').val();
  if( v == 'mail' ) {
    $('#set_smtp').find('input,select').prop('disabled',true);
    $('#set_sendmail').find('input,select').prop('disabled',true);
  }
  else if( v == 'smtp' ) {
    $('#set_smtp').find('input,select').prop('disabled',false);
    $('#set_sendmail').find('input,select').prop('disabled',true);
  }
  else if( v == 'sendmail' ) {
    $('#set_smtp').find('input,select').prop('disabled',true);
    $('#set_sendmail').find('input,select').prop('disabled',false);
  }
}
$(function() {
  $(document).on('click', '#mailertest', function(e) {
    $('#testpopup').dialog({
      width: 'auto',
      modal: true
    });
    return false;
  });

  $(document).on('click', '#testcancel', function(e) {
    $('#testpopup').dialog('close');
    return false;
  });
  $(document).on('click','#testsend', function(e) {
    $('#testpopup').dialog('close');
    $(this).closest('form').trigger('submit');
  });

  $('#mailer').on('change', function() {
    on_mailer();
  });
  on_mailer();
});
</script>

	<div id="testpopup" title="{lang('title_mailtest')}" style="display: none;">
		<form id="siteprefform_mail" method="post" action="{$formurl}">
			<div>
				<input type="hidden" name="{$SECURE_PARAM_NAME}" value="{$CMS_USER_KEY}">
				<input type="hidden" name="active_tab" value="mail">
				<input type="hidden" name="testmail" value="1">
			</div>
			<div class="information">{lang('info_mailtest')}</div>
			<div class="pageoverflow">
				<p class="pagetext"><label for="testaddress">{lang('settings_testaddress')}:</label> {cms_help key2='settings_mailtest_testaddress' title=lang('settings_testaddress')}</p>
				<p class="pageinput">
					<input type="text" id="testaddress" name="mailtest_testaddress" size="50" maxlength="255">
				</p>
			</div>
			<div class="pageoverflow">
				<p class="pagetext"></p>
				<p class="pageinput">
					<input id="testsend" type="submit" name="sendtest" data-ui-icon="ui-icon-mail-closed" value="{lang('sendtest')}">
					<input id="testcancel" type="submit" name="cancel" value="{lang('cancel')}">
				</p>
			</div>
		</form>
	</div>

	<form id="siteprefform_mail2" method="post" action="{$formurl}">
		<div>
			<input type="hidden" name="{$SECURE_PARAM_NAME}" value="{$CMS_USER_KEY}">
			<input type="hidden" name="active_tab" value="mail">
			<input type="hidden" name="editsiteprefs" value="true">
		</div>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input id="mailertest" type="submit" name="testemail" data-ui-icon="ui-icon-gear" value="{lang('test')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
			<br>
		</div>

		<fieldset id="set_general">
			<legend>{lang('general_settings')}:</legend>
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
			<legend>{lang('smtp_settings')}:</legend>
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
					<legend>{lang('settings_authentication')}:</legend>
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
			<legend>{lang('sendmail_settings')}:</legend>
				<div class="pageoverflow">
					<p class="pagetext"><label for="sendmail">{lang('settings_sendmailpath')}:</label> {cms_help key2='settings_mailprefs_sendmail' title=lang('settings_sendmailpath')}</p>
					<p class="pageinput">
						<input type="text" id="sendmail" name="mailprefs_sendmail" value="{$mailprefs.sendmail}" size="50" maxlength="255">
					</p>
				</div>
		</fieldset>
		<div class="pageoverflow">
			<br>
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
		</div>
	</form>

{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='setup'}
	<form id="siteprefform_setup" method="post" action="{$formurl}">
		<div>
			<input type="hidden" name="{$SECURE_PARAM_NAME}" value="{$CMS_USER_KEY}">
			<input type="hidden" name="active_tab" value="setup">
			<input type="hidden" name="editsiteprefs" value="true">
		</div>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
			<br>
		</div>

		<fieldset>
			<legend>{lang('browser_cache_settings')}:&nbsp;</legend>
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
			<legend>{lang('server_cache_settings')}:&nbsp;</legend>
				<div class="pageoverflow">
					<p class="pagetext"><label for="autoclearcache2">{lang('autoclearcache2')}:</label> {cms_help key2='settings_autoclearcache' title=lang('autoclearcache2')}</p>
					<p class="pageinput">
						<input id="autoclearcache2" type="text" name="auto_clear_cache_age" size="4" value="{$auto_clear_cache_age}" maxlength="4">
					</p>
				</div>
		</fieldset>
		<fieldset>
			<legend>{lang('general_operation_settings')}:&nbsp;</legend>
				<div class="pageoverflow">
					<p class="pagetext"><label for="umask">{lang('global_umask')}:</label> {cms_help key2='settings_umask' title=lang('global_umask')}</p>
					<p class="pageinput">
						<input id="umask" type="text" class="pagesmalltextarea" name="global_umask" size="4" value="{$global_umask}">
					</p>
				</div>
				{if isset($testresults)}
					<div class="pageoverflow">
						<p class="pagetext">{lang('results')}</p>
						<p class="pageinput"><strong>{$testresults}</strong></p>
					</div>
				{/if}
				<div class="pageoverflow">
					<p class="pagetext">&nbsp;</p>
					<p class="pageinput"><input type="submit" name="testumask" data-ui-icon="ui-icon-gear" value="{lang('test')}" class="pagebutton"></p>
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
		<div class="pageoverflow">
			<br>
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
		</div>
	</form>

{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='smarty'}
	<form id="siteprefform_smarty" method="post" action="{$formurl}">
		<div>
			<input type="hidden" name="{$SECURE_PARAM_NAME}" value="{$CMS_USER_KEY}">
			<input type="hidden" name="active_tab" value="smarty">
			<input type="hidden" name="editsiteprefs" value="true">
		</div>
		<div class="pageoverflow">
			<p class="pageinput">
				<input type="submit" name="submit" value="{lang('submit')}" class="pagebutton">
				<input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton">
			</p>
			<br>
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
			<p class="pagetext"><label for="compilecheck">{lang('prompt_smarty_compilecheck')}:</label> {cms_help key2='settings_smartycompilecheck' title=lang('prompt_smarty_compilecheck')}</p>
			<p class="pageinput">
				<select id="compilecheck" name="use_smartycompilecheck">
					{html_options options=$yesno selected=$use_smartycompilecheck}
				</select>
			</p>
		</div>
	</form>
{tab_end}

</div>
