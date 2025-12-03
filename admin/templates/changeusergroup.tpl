<div class="pagecontainer">
{$header}
<div class="information">
  {lang('info_changeusergroup')} {cms_help key2='help_group_permissions' title=lang('info_changeusergroup')}
</div>
{*
<div id="admin_group_warning" style="display:none">
{$admin_group_warning}
</div>
*}
{if isset($message)}
<p class="pagemessage">{$message}</p>
{/if}

<div class="pageoptions endalign">
<form action="changegroupassign.php" method="post">
  <div class="hidden">
    <input type="hidden" name="{$hiddenname}" value="{$hiddenval}">
  </div>
  <label for="groupsel">{lang('selectgroup')}:</label>&nbsp;
  <select name="groupsel" id="groupsel">
  {foreach $allgroups as $thisgroup}
    {if $thisgroup->id == $disp_group}
    <option value="{$thisgroup->id}" selected>{$thisgroup->name}</option>
    {else}
    <option value="{$thisgroup->id}">{$thisgroup->name}</option>
    {/if}
  {/foreach}
  </select>&nbsp;
  <input type="submit" name="filter" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
</form>
</div>

<form id="groupname" action="changegroupassign.php" method="post">
<div class="hidden">
  <input type="hidden" name="{$hiddenname}" value="{$hiddenval}">
  <input type="hidden" name="submitted" value="1">
</div>
<div class="pageoptions">
  <input type="submit" name="changegrp" value="{lang('submit')}">
  <input type="submit" name="cancel" value="{lang('cancel')}">
</div>
<table class="pagetable" id="permtable">
  <thead>
  <tr>{$group_count=count($group_list)}
    <th>{if isset($title_group)}{$title_group}{/if}</th>
    {foreach $group_list as $thisgroup}{$gid=$thisgroup->id}
      {if $gid != -1}
        {$title=''}
        {$text=$thisgroup->name}
        {if !$thisgroup->active}
          {$title=lang('info_group_inactive')}
          {$text=$thisgroup->name}
          {if $group_count >= 5}
            {$text=$thisgroup->name|cat:'!'}
          {else}
            {$text=$thisgroup->name|cat:"&nbsp;({lang('inactive')})"}
          {/if}
        {/if}
        <th class="g{$gid}">
          <span {if !$thisgroup->active}style="font-style:italic" {/if}title="{$title}">{$text}</span>
        </th>
      {/if}
    {/foreach}
  </tr>
  </thead>
  <tbody>
  {foreach $users as $user}
    {cycle values='row1,row2' assign='currow'}
    <tr class="{$currow}">
      <td>{$user->name}</td>
      {foreach $group_list as $thisgroup}{$gid=$thisgroup->id}
        {if $user->id == $user_id}
          {if $gid != -1}
      <td class="g{$gid}">--</td>
          {/if}
        {elseif $gid != -1}
          {if ($gid == 1 && $user->id == 1)}
      <td class="g{$gid}">&nbsp;</td>
          {else}
      <td class="g{$gid}">
        <input type="checkbox" name="ug_{$user->id}_{$gid}" value="1"{if isset($user->group[$gid])} checked{/if}>
      </td>
          {/if}
        {/if}
      {/foreach}
    </tr>
  {/foreach}
  </tbody>
</table>
{if count($users) > 8}
<div class="pageoptions">
  <input type="submit" name="changegrp" value="{lang('submit')}">
  <input type="submit" name="cancel" value="{lang('cancel')}">
</div>
{/if}
</form>
</div>
