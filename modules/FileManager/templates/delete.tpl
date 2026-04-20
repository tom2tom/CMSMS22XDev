<h3>{$mod->Lang('actiondelete')}</h3>
<div class="pageoverflow">
  <p class="pagetext"><label>{$mod->Lang('deleteselected')}:</label></p>
  <p class="pageinput">
    {'<br>'|adjust:'implode':$selall}
  </p>
</div>
<br>
<div class="pageoverflow">
 {$startform}
  <div class="pageinput">
{if empty($errors)}
    <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-trash" value="{lang('delete')}">
    <input type="submit" name="{$actionid}cancel" data-ui-icon="ui-icon-cancel" value="{lang('cancel')}">
{else}
    <input type="submit" name="{$actionid}cancel" data-ui-icon="ui-icon-closethick" value="{lang('close')}">
{/if}
  </div>
 {$endform}
</div>
