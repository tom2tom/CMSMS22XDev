<div class="pagecontainer">
 {$header}
 <fieldset>
 <legend>{lang('event')}</legend>
 <div class="pageoverflow">
   <p class="pagetext">{lang('name')}</p>
   <p class="pageinput">{$event}</p>
 </div>
 <div class="pageoverflow">
   <p class="pagetext">{lang('originator')}</p>
   <p class="pageinput">{$modulename}</p>
 </div>
 <div class="pageoverflow">
   <p class="pagetext">{lang('description')}</p>
   <p class="pageinput">{$description}</p>
 </div>
 </fieldset>
 <h4>{lang('eventhandler')}</h4>
{if !empty($handlers)}
  <table class="pagetable">
  <thead>
    <tr>
      <th>{lang('order')}</th>
      <th>{lang('user_tag')}</th>
      <th>{lang('originator')}</th>
      <th class="pageicon"></th>
      <th class="pageicon"></th>
      <th class="pageicon"></th>
    </tr>
  </thead>
  <tbody>{foreach $handlers as $one}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>{$one.handler_order}</td>
      <td>{$one.tag_name}</td>
      <td>{$one.module_name}</td>
      <td class="pagepos icons_wide">
      {if !$one@first}
      <a href="{$selfurl}{$urlext}&event={$event}&module={$module}&action=up&order={$one.handler_order}&handler={$one.handler_id}">{$iconup}</a>
      {/if}
      </td>
      <td class="pagepos icons_wide">
      {if !$one@last}
      <a href="{$selfurl}{$urlext}&event={$event}&module={$module}&action=down&order={$one.handler_order}&handler={$one.handler_id}">{$icondown}</a>
      {/if}
      </td>
      <td class="pagepos icons_wide">
      {if $one.removable}{if $one.tag_name}{$myname=$one.tag_name}{else}{$myname=$one.module_name}{/if}
{*TODO replace link onclick handler with some jquery*}
      <a href="{$selfurl}{$urlext}&event={$event}&module={$module}&action=delete&handler={$one.handler_id}" onclick="return confirm('{lang('deleteconfirm', $myname)}');">{$icondel}</a>
      {/if}
      </td>
{/strip}
    </tr>
  {/foreach}</tbody>
  </table>
{else}
{lang('none')}<br>
{/if}
<div class="pageinput">
{if $allhandlers}
 <form action="editevent.php" method="post">
   <input type="hidden" name="{$hiddenname}" value="{$hiddenval}">
   <input type="hidden" name="module" value="{$module}">
   <input type="hidden" name="event" value="{$event}">
   <select name="handler">
{foreach $allhandlers as $key => $value}
     <option value="{$value}">{$key}</option>
{/foreach}
   </select>
   <input type="submit" name="add" data-ui-icon="ui-icon-circle-plus" value="{lang('add')}">
 </form>
 <br>
{/if}
 <form action="editevent.php" method="post">
   <input type="hidden" name="{$hiddenname}" value="{$hiddenval}">
   <input type="submit" name="close" data-ui-icon="ui-icon-close" value="{lang('close')}">
 </form>
</div>
