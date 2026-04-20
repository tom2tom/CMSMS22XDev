{if !empty($handlers)}
<script>
$(function() {
  $('.deleteitem').on('click', function(e) {
    e.preventDefault();
    var hn = $(this).data('handler'); // name from clicked row
    var pr = '{lang('deleteconfirm','^^')|escape:'javascript'}'.replace('^^',hn);
    var _url = $(this).attr('href');
    cms_confirm(pr).done(function() {
      window.location.href = _url;
    });
  });
});
</script>
{/if}
<div class="pagecontainer">
 <fieldset>
 <legend>{lang('event')}</legend>
 <div class="pageoverflow">
   <p class="pagetext"><label>{lang('name')}</label></p>
   <p class="pageinput">{$event}</p>
 </div>
 <div class="pageoverflow">
   <p class="pagetext"><label>{lang('originator')}</label></p>
   <p class="pageinput">{$modulename}</p>
 </div>
 <div class="pageoverflow">
   <p class="pagetext"><label>{lang('description')}</label></p>
   <p class="pageinput">{$description}</p>
 </div>
 </fieldset>
 <h3>{lang('eventhandler')}</h3>
{if !empty($handlers)}
  <table class="pagetable">
  <thead>
    <tr>
      <th>{lang('order')}</th>
      <th>{lang('handler')}</th>
      <th class="pageicon"></th>
      <th class="pageicon"></th>
      <th class="pageicon"></th>
    </tr>
  </thead>
  <tbody>{foreach $handlers as $one}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>{$one.handler_order}</td>
      <td{if !empty($one.truncated)} title="{lang('title_callable')}"{/if}>
{if ($one.handler_type == 1)}{*aka Events::HANDLERMOD*}
       {lang('module')}: {$one.handler}
{elseif ($one.handler_type == 2)}{*aka Events::HANDLERUDT*}
       {lang('user_tag')}: {$one.handler}
{elseif ($one.handler_type == 3)}{*aka Events::HANDLERCALL*}
       {lang('callable')}: {$one.handler}{if !empty($one.truncated)}<strong>...</strong>{/if}
{/if}
     </td>
      <td class="pagepos icons_wide">
      {if !$one@first}
      <a href="{$selfurl}?event={$event}&originator={$originator}&action=up&order={$one.handler_order}&handler={$one.handler_id}&{$secureparam}">{$iconup}</a>
      {/if}
      </td>
      <td class="pagepos icons_wide">
      {if !$one@last}
      <a href="{$selfurl}?event={$event}&originator={$originator}&action=down&order={$one.handler_order}&handler={$one.handler_id}&{$secureparam}">{$icondown}</a>
      {/if}
      </td>
      <td class="pagepos icons_wide">
      {if $one.removable}
      <a href="{$selfurl}?event={$event}&originator={$originator}&action=delete&handler={$one.handler_id}&{$secureparam}" class="deleteitem" data-handler="{$one.handler}">{$icondel}</a>
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
   <input type="hidden" name="{$securename}" value="{$secureval}">
   <input type="hidden" name="originator" value="{$originator}">
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
   <input type="hidden" name="{$securename}" value="{$secureval}">
   <input type="submit" name="close" data-ui-icon="ui-icon-closethick" value="{lang('close')}">
 </form>
</div>
</div>
