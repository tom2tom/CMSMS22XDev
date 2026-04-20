<div class="pagecontainer">
  <div class="pageoverflow">
    <form action="eventhandlers.php" method="post">
      <input type="hidden" name="{$securename}" value="{$secureval}">
      <div class="pageoptions endalign">
        <label for="filter_mod">{lang('filterbymodule')}:</label>
        <select id="filter_mod" name="modulefilter">
          <option value="">{lang('showall')}</option>
      {foreach $modlist as $onemod}
          <option value="{$onemod}"{if $onemod == $modulefilter} selected{/if}>{$onemod}</option>
      {/foreach}
        </select>
        <input type="submit" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
      </div>
    </form>
  {if $events}
    <table class="pagetable">
     <thead>
      <tr>
       <th title="{lang('title_event_originator')}">{lang('originator')}</th>
       <th title="{lang('title_event_name')}">{lang('event')}</th>
       <th title="{lang('title_event_handlers')}">{lang('eventhandler')}</th>
       <th title="{lang('title_event_description')}" style="width:50%">{lang('event_description')}</th>
       <th class="pageicon">&nbsp;</th>
    {if $access}
       <th class="pageicon">&nbsp;</th>
    {/if}
      </tr>
     </thead>
     <tbody>
        {foreach $events as $oneevent}
          {if $modulefilter == '' || $modulefilter == $oneevent.originator}
      <tr class="{cycle values='row1,row2'}">
          {if $oneevent.originator == 'Core'}
        {$desctext=Events::GetEventDescription($oneevent.event_name)}
         <td>{lang('core')}</td>
          {elseif ($mod = cms_utils::get_module($oneevent.originator)) }
        {$desctext=$mod->GetEventDescription($oneevent.event_name)}
         <td>{$mod->GetFriendlyName()}</td>
          {else}
        {$desctext=''}
         <td></td>
          {/if}
         <td>
    {if $access}
          <a href="editevent.php?action=edit&originator={$oneevent.originator}&event={$oneevent.event_name}&{$secureparam}" title="{lang('edit')}">
          {$oneevent.event_name}
          </a>
    {else}
          {$oneevent.event_name}
    {/if}
        </td>
        <td>
    {if $oneevent.usage_count > 0}
          <a href="eventhandlers.php?action=showeventhelp&originator={$oneevent.originator}&event={$oneevent.event_name}&{$secureparam}" title="{lang('help')}">{$oneevent.usage_count}</a>
    {/if}
        </td>
        <td>{$desctext}</td>
        <td class="icons_wide"><a href="eventhandlers.php?action=showeventhelp&originator={$oneevent.originator}&event={$oneevent.event_name}&{$secureparam}">{$infoImg}</a></td>
    {if $access}
        <td class="icons_wide"><a href="editevent.php?action=edit&originator={$oneevent.originator}&event={$oneevent.event_name}&{$secureparam}">{$editImg}</a></td>
    {/if}
      </tr>
      {/if}{* modulefilter *}
{/foreach}
     </tbody>
    </table>
  {else}
    <p class="information">No event or no matching event is recorded</p>{*TODO langify*}
  {/if}
  </div>
</div>
