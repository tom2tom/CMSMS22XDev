<script>
$(function() {
{if !empty($developer_mode)}
  $('#reseturl').on('click', function(ev) {
    ev.preventDefault();
    var form = $(this).closest('form');
    cms_confirm('{$mod->Lang('confirm_reseturl')|escape:'javascript'}').done(function() {
      $('#inp_reset').val(1);
      form.trigger('submit');
    });
  });
{/if}
  $('#settings_submit').on('click', function(ev) {
    ev.preventDefault();
    var form = $(this).closest('form');
    cms_confirm('{$mod->Lang('confirm_settings')|escape:'javascript'}').done(function() {
      form.trigger('submit');
    });
  });
});
</script>
{if isset($message)}<p>{$message}</p>{/if}

{form_start action='setprefs'}
{if !empty($developer_mode)}
  <input type="hidden" id="inp_reset" name="{$actionid}reseturl" value="">
  <div class="pageoverflow">
    <p class="pagetext"><label for="txturl">{$mod->Lang('prompt_repository_url')}:</label></p>
    <p class="pageinput">
      <input type="text" name="{$actionid}url" id="txturl" size="50" maxlength="255" value="{$module_repository}">
      <input type="submit" id="reseturl" data-ui-icon="ui-icon-arrowrefresh-1-n" value="{$mod->Lang('reset')}">
    </p>
  </div>
{/if}
  <div class="pageoverflow">
    <p class="pagetext"><label for="chunksize">{$mod->Lang('prompt_dl_chunksize')}:</label>&nbsp;{cms_help key2='help_dl_chunksize' title=$mod->Lang('prompt_dl_chunksize')}</p>
    <p class="pageinput">
      <input type="text" id="chunksize" name="{$actionid}dl_chunksize" value="{$dl_chunksize}" size="4" maxlength="4">
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext"><label for="sellatest">{$mod->Lang('latestdepends')}:</label>&nbsp;{cms_help key2='help_latestdepends' title=$mod->Lang('latestdepends')}</p>
    <p class="pageinput">
      <select id="sellatest" name="{$actionid}latestdepends">{cms_yesno selected=$latestdepends}</select>
    </p>
  </div>
{if !empty($developer_mode)}
  <div class="pageoverflow">
    <p class="pagetext"><label for="seluninstall">{$mod->Lang('allowuninstall')}:</label>&nbsp;{cms_help key2='help_allowuninstall' title=$mod->Lang('allowuninstall')}</p>
    <p class="pageinput">
      <select id="seluninstall" name="{$actionid}allowuninstall">{cms_yesno selected=$allowuninstall}</select>
    </p>
    <p class="warning">{$mod->Lang('warn_uninstall')}</p
  </div>
  <div class="pageoverflow">
    <p class="pagetext"><label for="selcaching">{$mod->Lang('prompt_disable_caching')}:</label>&nbsp;{cms_help key2='help_disable_caching' title=$mod->Lang('prompt_disable_caching')}</p>
    <p class="pageinput">
      <select id="selcaching" name="{$actionid}disable_caching">{cms_yesno selected=$disable_caching}</select>
    </p>
  </div>
{/if}
  <br>
  <div class="pageoverflow">
    <p class="pageinput">
      <input type="submit" id="settings_submit" name="{$actionid}submit" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
    </p>
  </div>
</form>
