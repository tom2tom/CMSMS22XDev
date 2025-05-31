<div class="pagecontainer">
  <div class="pageoverflow">
    {$header}
    <h3>{$event} {lang('event')}</h3>
    {if $desctext}<h4>{lang('description')}</h4>
    {$desctext}{/if}
    {$text}
    <h4>{lang('eventhandler')}</h4>
{if $hlist}
    {if count($hlist) > 1}
    <ul>
{foreach $hlist as $te}
      <li>{strip}{$te['handler_order']}
    {if !empty($te['tag_name'])}
       . {lang('user_tag')}: {$te['tag_name']}
    {elseif !empty($te['module_name'])}
       . {lang('module')}: {$te['module_name']}
    {/if}
      {/strip}</li>
{/foreach}
    </ul>
    {else}
    {strip}{$te=$hlist.0}
    {if !empty($te['tag_name'])}
       {lang('user_tag')}: {$te['tag_name']}
    {elseif !empty($te['module_name'])}
       {lang('module')}: {$te['module_name']}
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
      <input type="hidden" name="{$hiddenname}" value="{$hiddenval}">
      <input type="submit" name="close" data-ui-icon="ui-icon-close" value="{lang('close')}">
    </form>
  </div>
</div>
