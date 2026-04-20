<div class="pagecontainer">
{if $padd}
  <div class="pageoptions">
    <a href="addgroup.php?{$secureparam}">{$iconadd}</a>
    <a class="pageoptions" href="addgroup.php?{$secureparam}">{lang('addgroup')}</a>
  </div>
{/if}
{if !empty($pagination)}
  <div class="pageshowrows">
    {$pagination}
  </div>
{/if}
{if $grouplist}
  <table class="pagetable">
    <thead>
      <tr>
        <th class="pagew60">{lang('name')}</th>
        <th class="pagepos">{lang('active')}</th>
        <th class="pageicon">&nbsp;</th>
        <th class="pageicon">&nbsp;</th>
        <th class="pageicon">&nbsp;</th>
        <th class="pageicon">&nbsp;</th>
      </tr>
    </thead>
    <tbody>
    {foreach $grouplist as $onegroup}{$gid=$onegroup[0]->id}
      <tr class="{cycle values='row1,row2'}">
        <td><a title="{$onegroup[0]->description}" href="editgroup.php?group_id={$gid}&{$secureparam}">{$onegroup[0]->name}</a></td>
        <td class="pagepos icons_wide">
      {if $gid == 1}&nbsp;{elseif $onegroup[0]->active}{$icontrue}{else}$iconfalse{/if}
        </td>
        <td class="pagepos icons_wide"><a href="changegroupperm.php?group_id={$gid}&{$secureparam}">{$iconperms}</a></td>
        <td class="pagepos icons_wide"><a href="changegroupassign.php?group_id{$gid}&{$secureparam}">{$icongroup}</a></td>
        <td class="icons_wide"><a href="editgroup.php?group_id={$gid}&{$secureparam}">{$iconedit}</a></td>
        <td class="icons_wide">{strip}
{if (!($gid == 1 || $onegroup.1))}{$gname=$onegroup[0]->name}
      <a href="deletegroup.php?group_id={$gid}&{$secureparam}" onclick="return confirm('{lang('deleteconfirm',{$gname})}');">
        {$icondelete}
      </a>
{else}
      &nbsp;
{/if}
      {/strip}</td>
      </tr>
{/foreach}
    </tbody>
  </table>
{if $padd && count($grouplist) > 10}
  <br>
  <div class="pageoptions">
    <a href="addgroup.php?{$secureparam}">{$iconadd}</a>
    <a class="pageoptions" href="addgroup.php?{$secureparam}">{lang('addgroup')}</a>
  </div>
{/if}
{else}
  <p class="information">{lang('none')}</p>
{/if}
</div>
