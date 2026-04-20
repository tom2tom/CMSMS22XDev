{$startform}

<div class="pageoverflow">
  <p class="pagetext"><label for="newname">{$newnametext}:</label></p>
  <p class="pageinput"><input id="newname" type="text" name="{$actionid}newname" value="{$newname}" size="40"></p>
</div>
<br>
<div class="pageoverflow">
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-gear" value="{$mod->Lang('rename')}">
    <input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
  </p>
</div>

{$endform}
