{$sid=$css->get_id()}{$snm=$css->get_name()}
<h3>{$mod->Lang('delete_stylesheet')}: {$snm} ({$sid})</h3>

<fieldset>
{if $sid > 0}
  <div class="startside">
{/if}
    <div class="pageoverflow">
      <p class="pagetext"><label>{$mod->Lang('prompt_name')}:</label></p>
      <p class="pageinput">{$snm}</p>
    </div>
{if $sid > 0}
  </div>{* column *}
  <p class="startside" style="width:5%;min-width:1em"></p>
  <div class="startside last">
    <div class="pageoverflow">
      <p class="pagetext"><label>{$mod->Lang('prompt_created')}:</label></p>
      <p class="pageinput">{$css->get_created()|localedate_format:'%x %X'}</p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label>{$mod->Lang('prompt_modified')}:</label></p>
      <p class="pageinput">{$css->get_modified()|localedate_format:'%x %X'}</p>
    </div>
  </div>{* column *}
{/if}
</fieldset>
<br>
{form_start css=$sid __activetab='stylesheets'}
<div class="pageoverflow">
  <p class="pageinput">
    <input id="check1" type="checkbox" name="{$actionid}check1" value="1">&nbsp;<label for="check1">{$mod->Lang('confirm_delete_css_1')}</label><br>
    <input id="check2" type="checkbox" name="{$actionid}check2" value="1">&nbsp;<label for="check2">{$mod->Lang('confirm_delete_css_2')}</label>
  </p>
</div>
<br>
<div class="pageoverflow">
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-minusthick" value="{$mod->Lang('remove')}">
    <input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
  </p>
</div>
{form_end}
