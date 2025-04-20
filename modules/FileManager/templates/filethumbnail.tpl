<h3>{$mod->Lang('createthumbnail')}</h3>
{$startform}
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('info_createthumb')}:</p>
  <p class="pagetext">{$thumb}</p>
</div>
<br>
<div class="pageoverflow">
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-star" value="{$mod->Lang('create')}">
    <input type="submit" name="{$actionid}cancel" data-ui-icon="ui-icon-cancel" value="{$mod->Lang('cancel')}">
  </p>
</div>
{$endform}
