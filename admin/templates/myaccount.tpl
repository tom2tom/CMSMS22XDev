<script type="text/javascript">
$(function() {
  $('.helpicon').on('click',function() {
    var x = $(this).attr('name');
    $('#'+x).dialog();
  });
});
</script>

<div class="pagecontainer">
{$tab_start}

{if $manageaccount}
  {$maintab_start}
  <form method="post" action="{$formurl}">
    <input type="hidden" name="active_tab" value="maintab" />
    <div class="pageoverflow">
      <div class="pageinput">
        <input class="pagebutton" type="submit" name="submit_account" value="{lang('submit')}" />
        <input class="pagebutton" type="submit" name="cancel" value="{lang('cancel')}" />
      </div>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="username">*{lang('name')}:</label>&nbsp;{cms_help key2='help_myaccount_username' title=lang('name')}
      </p>
      <p class="pageinput"><input type="text" id="username" name="user" maxlength="25" value="{$userobj->username}" class="standard" /></p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="password">{lang('password')}:</label>&nbsp;{cms_help key2='help_myaccount_password' title=lang('password')}</p>
      <p class="pageinput">
        <input type="password" id="password" name="password" maxlength="100" value="" />&nbsp;{lang('info_edituser_password')}
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="passwordagain">{lang('passwordagain')}:</label>&nbsp;{cms_help key2='help_myaccount_passwordagain' title=lang('passwordagain')}</p>
      <p class="pageinput"><input type="password" id="passwordagain" name="passwordagain" maxlength="100" value="" class="standard" />&nbsp;{lang('info_edituser_passwordagain')}</p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="firstname">{lang('firstname')}:</label>&nbsp;{cms_help key2='help_myaccount_firstname' title=lang('firstname')}</p>
      <p class="pageinput"><input type="text" id="firstname" name="firstname" maxlength="50" value="{$userobj->firstname}" class="standard" /></p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="lastname">{lang('lastname')}:</label>&nbsp;{cms_help key2='help_myaccount_lastname' title=lang('lastname')}</p>
      <p class="pageinput"><input type="text" id="lastname" name="lastname" maxlength="50" value="{$userobj->lastname}" class="standard" /></p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="email">{lang('email')}:</label>&nbsp;{cms_help key2='help_myaccount_email' title=lang('email')}</p>
      <p class="pageinput"><input type="text" id="email" name="email" maxlength="255" value="{$userobj->email}" class="standard" /></p>
    </div>
  </form>
  {$tab_end}
{/if}

{if $managesettings}
{$advancedtab_start}
<form method="post" action="{$formurl}">
  <input type="hidden" name="active_tab" value="advtab" />
    <div class="pageoverflow">
      <div class="invisible">
      <input type="hidden" name="edituserprefs" value="true" />
      <input type="hidden" name="old_default_cms_lang" value="{$old_default_cms_lang}" />
      </div>
      <p class="pageinput">
        <input type="submit" name="submit_prefs" value="{lang('submit')}" class="pagebutton" />
        <input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton" />
      </p>
    </div>
    <fieldset>
      <legend>{lang('lang_settings_legend')}:</legend>
      <div class="pageoverflow">
        <p class="pagetext"><label for="language">{lang('language')}:</label>&nbsp;{cms_help key2='help_myaccount_language' title=lang('language')}</p>
        <p class="pageinput">
          <select id="language" name="default_cms_language">
            {html_options options=$language_opts selected=$default_cms_language}
          </select>
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext"><label for="dateformat">{lang('date_format_string')}:</label>&nbsp;{cms_help key2='help_myaccount_dateformat' title=lang('date_format_string')}</p>
        <p class="pageinput">
           <input class="pagenb" size="20" maxlength="255" type="text" name="date_format_string" value="{$date_format_string}" />
           {lang('date_format_string_help')}
        </p>
      </div>
    </fieldset>

    <fieldset>
      <legend>{lang('content_editor_legend')}:</legend>
      <div class="pageoverflow">
        <p class="pagetext"><label for="wysiwyg">{lang('wysiwygtouse')}:</label>&nbsp;{cms_help key2='help_myaccount_wysiwyg' title=lang('wysiwygtouse')}</p>
        <p class="pageinput">
          <select id="wysiwyg" name="wysiwyg">
            {html_options options=$wysiwyg_opts selected=$wysiwyg}
          </select>
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext"><label for="syntaxh">{lang('syntaxhighlightertouse')}:</label>&nbsp;{cms_help key2='help_myaccount_syntax' title=lang('syntaxhighlightertouse')}</p>
        <p class="pageinput">
          <select id="syntaxh" name="syntaxhighlighter">
            {html_options options=$syntax_opts selected=$syntaxhighlighter}
          </select>
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext"><label for="ce_navdisplay">{lang('ce_navdisplay')}:</label>&nbsp;{cms_help key2='help_myaccount_ce_navdisplay' title=lang('ce_navdisplay')}</p>
        <p class="pageinput">
          {$opts['']=lang('none')}
          {$opts['menutext']=lang('menutext')}
          {$opts['title']=lang('title')}
          <select id="ce_navdisplay" name="ce_navdisplay">
          {html_options options=$opts selected=$ce_navdisplay}
          </select>
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext"><label for="parent_id">{lang('defaultparentpage')}:</label>&nbsp;{cms_help key2='help_myaccount_dfltparent' title=lang('defaultparentpage')}</p>
        <p class="pageinput">{$default_parent}</p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext"><label for="indent">{lang('adminindent')}:</label>&nbsp;{cms_help key2='help_myaccount_indent' title=lang('adminindent')}</p>
        <p class="pageinput">
          <input class="pagenb" type="checkbox" id="indent" name="indent"{if $indent} checked="checked"{/if} />
          {lang('indent')}
        </p>
      </div>
      <!-- content display //-->
    </fieldset>

    <fieldset>
      <legend>{lang('admin_layout_legend')}:</legend>
      <div class="pageoverflow">
        <p class="pagetext"><label for="admintheme">{lang('admintheme')}:</label>&nbsp;{cms_help key2='help_myaccount_admintheme' title=lang('admintheme')}</p>
        <p class="pageinput">
          <select id="admintheme" name="admintheme">
            {html_options options=$themes_opts selected=$admintheme}
          </select>
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext"><label for="homepage">{lang('homepage')}:</label>&nbsp;{cms_help key2='help_myaccount_homepage' title=lang('homepage')}</p>
        <p class="pageinput">
          {$homepage}
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext"><label for="admincallout">{lang('admincallout')}:</label>&nbsp;{cms_help key2='help_myaccount_admincallout' title=lang('admincallout')}</p>
        <p class="pageinput">
          <input class="pagenb" id="admincallout" type="checkbox" name="bookmarks"{if $bookmarks} checked="checked"{/if} />
          {lang('showbookmarks')}
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext"><label for="hidehelp">{lang('hide_help_links')}:</label>&nbsp;{cms_help key2='help_myaccount_hidehelp' title=lang('hide_help_links')}</p>
        <p class="pageinput">
          <input class="pagenb" id="hidehelp" type="checkbox" name="hide_help_links"{if $hide_help_links} checked="checked"{/if} />
          {lang('hide_help_links_help')}
        </p>
      </div>

   <div class="pageoverflow">
     <div class="invisible">
        <input type="hidden" name="edituserprefs" value="true" />
        <input type="hidden" name="old_default_cms_lang" value="{$old_default_cms_lang}" />
     </div>
     <p class="pageinput">
        <input type="submit" name="submit_prefs" value="{lang('submit')}" class="pagebutton" />
        <input type="submit" name="cancel" value="{lang('cancel')}" class="pagebutton" />
     </p>
   </div>
 </form>

{$tab_end}
{/if}

{$tabs_end}
</div>
