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
<form method="post" action="{$filter_action}">
  <div class="hidden">
    <input type="hidden" name="{$cms_secure_param_name}" value="{$cms_user_key}">
  </div>
  <label for="groupsel">{$selectgroup}:</label>&nbsp;
  <select name="groupsel" id="groupsel">
  {foreach $allgroups as $thisgroup}
    {if $thisgroup->id == $disp_group}
    <option value="{$thisgroup->id}" selected="selected">{$thisgroup->name}</option>
    {else}
    <option value="{$thisgroup->id}">{$thisgroup->name}</option>
    {/if}
  {/foreach}
  </select>&nbsp;
  <input type="submit" name="filter" data-ui-icon="ui-icon-disk" value="{$apply}">
</form>
</div><br>

{$form_start}
{$hidden|default:''}
<div class="hidden">
  <input type="hidden" name="{$cms_secure_param_name}" value="{$cms_user_key}">
</div>
<div class="pageoptions">
  {$submit} {$cancel}
</div>
<table class="pagetable" id="permtable">
  <thead>
  <tr>{$group_count=count($group_list)}
    <th>{if isset($title_group)}{$title_group}{/if}</th>
    {foreach $group_list as $thisgroup}
      {if $thisgroup->id != -1}
        {$title=''}
        {$text=$thisgroup->name}
        {$tag='span'}
        {if !$thisgroup->active}
          {$tag='em'}
          {$title=lang('info_group_inactive')}
          {$text=$thisgroup->name}
          {if $group_count >= 5}
            {$text=$thisgroup->name|cat:'!'}
          {else}
            {$text=$thisgroup->name|cat:"&nbsp;({lang('inactive')})"}
          {/if}
        {/if}
        <th class="g{$thisgroup->id}">
          <{$tag} title="{$title}">{$text}</{$tag}>
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
        {else}
      <td></td>
        {/if}
      {/foreach}
    </tr>
  {/foreach}
  </tbody>
</table>
{if count($users) > 8}
<div class="pageoptions">
  {$submit} {$cancel}
</div>
{/if}
</form>
</div>
