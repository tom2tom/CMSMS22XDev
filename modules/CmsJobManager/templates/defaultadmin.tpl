<div class="information">
  {if $modhelp}{$mod->Lang('info_background_jobs')}{else}{$mod->Lang('info_background_jobs2')}{/if}
  <div class="close-warning"></div>
</div>
{if empty($jobs)}
<br><p class="blueinfo">{$mod->Lang('info_no_jobs')}</p>
{else}
<table class="pagetable">
  <thead>
    <tr>
      <th>{$mod->Lang('name')}</th>
      <th>{$mod->Lang('module')}</th>
      <th>{$mod->Lang('created')}</th>
      <th>{$mod->Lang('start')}</th>
      <th>{$mod->Lang('frequency')}</th>
      <th>{$mod->Lang('until')}{if $gmtime} (GMT){/if}</th>
      <th>{$mod->Lang('errors')}</th>
    </tr>
  </thead>
  <tbody>
  {foreach $jobs as $job}{$p1 = $job->created|relative_time}{if $job->start > 0}{$p2 = $job->start|relative_time}{else}{$p2 = ''}{/if}
    <tr class="{cycle values='row1,row2'}">
      <td{if $job->desc} title="{$job->desc}"{/if}>{$job->name}</td>
      <td>{$job->module|default:''}</td>
      <td>{if ($job->start == 0 || $p1 != $p2)}{$p1}{/if}</td>
      <td>
        {if $job->start > 0}
        {if $job->start < $smarty.now - $async_freq}<span style="color:red">{$p2}</span>
        {elseif $job->start < $smarty.now + $async_freq}<span style="color:green">{$p2}</span>
        {else}{$p2}{/if}
        {/if}
      </td>
      <td>{if $job->recurs}{$job->recurs}{/if}</td>
      <td>{if $job->until}{$job->until|localedate_format:'%x %X'}{/if}</td>
      <td>{if $job->errors > 0}<span style="color:red">{$job->errors}</span>{/if}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
{/if}
