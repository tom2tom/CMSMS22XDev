{if !empty($guides)}
<table class="guidetable">
  <thead>
  <tr>
    <th></th>
    <th></th>
    <th class="pageicon"></th>
  </tr>
  </thead>
  <tbody>{$t=$mod->Lang('display')}{$icon="<img src=\"{$iconurl}\" class=\"viewicon\" alt=\"view guide\">"}
{foreach $guides as $one}{cms_action_url action='default' gid=$one.id assign='view_url'}
  <tr class="{if $one@index is even}row1{else}row2{/if}">
    <td><a href="{$view_url}" title="{$t}">{$one.name}</a></td>
    <td>{if $one.latest}{$one.latest|cms_date_format}{else}--{/if}</td>
    <td><a href="{$view_url}" title="{$t}">{$icon}</a></td>
  </tr>
{/foreach}
  </tbody>
</table>
{else}
<p class="information">{$mod->Lang('no_guide')}</p>
{/if}
