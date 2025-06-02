<h3>{$mod->Lang('actiondelete')}</h3>
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('deleteselected')}:</p>
  <p class="pageinput">
    {'<br>'|adjust:'implode':$selall}
  </p>
</div>
<br>
<div class="pageoverflow">
 {$startform}
  <p class="pageinput">
{if empty($errors)}
    <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-trash" value="{lang('delete')}">
    <input type="submit" name="{$actionid}cancel" data-ui-icon="ui-icon-cancel" value="{lang('cancel')}">
{else}
    <input type="submit" name="{$actionid}cancel" data-ui-icon="ui-icon-closethick" value="{lang('close')}">
{/if}
  </p>
 {$endform}
</div>
