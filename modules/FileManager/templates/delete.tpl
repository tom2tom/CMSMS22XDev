<h3>{$mod->Lang('actiondelete')}:</h3>
{$cancellabel=$mod->Lang('cancel')}
{if isset($errors)}
{$cancellabel=$mod->Lang('return')}
{/if}

{$startform}
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('deleteselected')}:</p>
  <p class="pageinput">
    {'<br/>'|implode:$selall}
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext"></p>
  <p class="pageinput">
    {if !isset($errors)}
    <input type="submit" name="{$actionid}submit" value="{$mod->Lang('delete')}"/>
    {/if}
    <input type="submit" name="{$actionid}cancel" value="{$cancellabel}"/>
  </p>
</div>
{$endform}
