{$startform}
<div class="pageoverflow">
  <p class="pagetext"><label for="newdir">{$mod->Lang('newdir')}:</label></p>
  <p class="pageinput"><input type="text" name="{$actionid}newdirname" id="newdir" value="{$newdirname}" size="40"></p>
</div>
<br>
<div class="pageoverflow">
  <p class="pageinput">
   <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-circle-plus" value="{$mod->Lang('create')}">
   <input type="submit" name="{$actionid}cancel" data-ui-icon="ui-icon-cancel" value="{$mod->Lang('cancel')}">
  </p>
</div>
{$endform}
