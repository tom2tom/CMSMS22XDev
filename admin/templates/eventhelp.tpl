<div class="pagecontainer">
  <div class="pageoverflow">
    <h3>{$event} {lang('event')}</h3>
    {if $desctext}<h4>{lang('description')}</h4>
    {$desctext}{/if}
    {$text}
    <h4>{lang('eventhandler')}</h4>
{if $hlist}
    {if count($hlist) > 1}
    <ul>
{foreach $hlist as $te}
      <li{if !empty($te.truncated)} title="{lang('title_callable')}"{/if}>{$te.handler_order}. {strip}
{if ($te.handler_type == 1)}{*aka Events::HANDLERMOD*}
       {lang('module')}: {$te.handler}
{elseif ($te.handler_type == 2)}{*aka Events::HANDLERUDT*}
       {lang('user_tag')}: {$te.handler}
{elseif ($te.handler_type == 3)}{*aka Events::HANDLERCALL*}
       {lang('callable')}: {$te.handler}{if !empty($te.truncated)}<strong>...</strong>{/if}
{/if}
      {/strip}</li>
{/foreach}
    </ul>
    {else}
    {strip}{$te=$hlist.0}
{if ($te.handler_type == 1)}
       {lang('module')}: {$te.handler}
{elseif ($te.handler_type == 2)}
       {lang('user_tag')}: {$te.handler}
{elseif ($te.handler_type == 3)}
       {lang('callable')}: {$te.handler}{if !empty($te.truncated)}<strong>...</strong>{/if}
{else}
       {lang('none')}
{/if}
    <br>
{/strip}
    {/if}
{else}
    {lang('none')}<br>
{/if}
    <br>
    <form action="eventhandlers.php" method="post">
      <input type="hidden" name="{$securename}" value="{$secureval}">
      <input type="submit" name="close" data-ui-icon="ui-icon-closethick" value="{lang('close')}">
    </form>
  </div>
</div>
