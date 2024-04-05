<script>
$(function() {
  $('#showadds').on('click', function(ev) {
   ev.preventDefault();
   $('#tabadder').show();
  });
});
</script>
{form_start action='admin_general_tab'}
<div class="pageoverflow">
  <p class="pagetext"></p>
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}" accesskey="s">
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext"><label for="timeout">{$mod->Lang('prompt_locktimeout')}:</label>&nbsp;{cms_help key2='help_general_locktimeout' title=$mod->Lang('prompt_locktimeout')}</p>
  <p class="pageinput">
    <input type="text" id="timeout" name="{$actionid}locktimeout" value="{$locktimeout}" size="3" maxlength="3">
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext"><label for="refresh">{$mod->Lang('prompt_lockrefresh')}:</label>&nbsp;{cms_help key2='help_general_lockrefresh' title=$mod->Lang('prompt_lockrefresh')}</p>
  <p class="pageinput">
    <input type="text" id="refresh" name="{$actionid}lockrefresh" value="{$lockrefresh}" size="4" maxlength="4">
  </p>
</div>
<div class="pageoverflow">
  <fieldset>
    <legend>{$mod->Lang('legend_tabs')}</legend>
    <p class="information">{$mod->Lang('info_ordertabs')}</p><br>
{foreach $tab_orders as $key => $val}
    <label class="pagetext" for="order{$key}">{$mod->Lang('prompt_namedtab_order', ucfirst(strtolower($key)))}:</label>
    <p class="pageinput">
      <input type="text" id="order{$key}" name="{$actionid}taborders[{$key}]" value="{$val}" size="3" maxlength="3">
    </p>
{/foreach}
    <br>{$t=$mod->Lang('addtab')}
    <a class="pageoptions" id="showadds" href="javascript:void(0)" title="{$t}">{admin_icon icon='newobject.gif' alt=$t}&nbsp;{$t}</a>
    <div id="tabadder" style="display:none">
    <label class="pagetext" for="custmid">{$mod->Lang('prompt_tab_id')}:</label>&nbsp;{cms_help key2='help_tab_id' title=$mod->Lang('prompt_tab_id')}<br>
    <p class="pageinput">
      <input type="text" id="custmid" name="{$actionid}customtabid" value="" size="16" maxlength="20"><br>
    </p>
    <label class="pagetext" for="custmname">{$mod->Lang('prompt_tab_name')}:</label><br>
    <p class="pageinput">
      <input type="text" id="custmname" name="{$actionid}customtabname" value="" size="16" maxlength="24"><br>
    </p>
    <label class="pagetext" for="custmorder">{$mod->Lang('prompt_tab_order')}:</label><br>
    <p class="pageinput">
      <input type="text" id="custmorder" name="{$actionid}customtaborder" value="" size="3" maxlength="3"><br>
    </p>
    </div>
  </fieldset>
</div>
<div class="pageoverflow">
  <p class="pagetext"><label for="mode">{$mod->Lang('prompt_template_list_mode')}:</label>&nbsp;{cms_help key2='help_general_templatelistmode' title=$mod->Lang('prompt_template_list_mode')}</p>
  <p class="pageinput">
    <select id="mode" name="{$actionid}template_list_mode">
      {html_options options=$template_list_opts selected=$template_list_mode}    </select>
  </p>
</div>
{form_end}
