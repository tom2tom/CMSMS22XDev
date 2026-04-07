<script>
function doclick(e) {
 if (e.target.nodeName === 'A') {
  e.preventDefault();
  var to = $(e.target);
  to.off('click').find('img').trigger('click');
  to.on('click', doclick);
 }
}
$(function() {
 $('.showhelp').on('click', doclick);
});
</script>
<div class="pagecontainer">
  <div class="pageoptions{if $pdev} cf{/if}">
{if $pdev}
    <span style="float:{$stside}">
{/if}
      <a class="showhelp" href="javascript:void(0);">
{if $stside == 'left'}{lang('help')}&nbsp;{/if}
{cms_help realm='none' key='jobsinfo' width='75%' minWidth=200 minHeight=225}
{if $stside != 'left'}&nbsp;{lang('help')}{/if}
      </a>
{if $pdev}
    </span>
    <span style="float:{$ndside}">
      <a class="showhelp" href="javascript:void(0);">
{if $ndside == 'right'}{lang('configure')}&nbsp;{/if}
{cms_help realm='none' key='jobsconfig' width='75%' minWidth=200 minHeight=225}
{if $ndside != 'right'}&nbsp;{lang('configure')}{/if}
      </a>
    </span>
{/if}
  </div>
{if empty($jobs)}
  <p class="blueinfo">{lang('info_no_jobs')}</p>
{else}
  <table class="pagetable">
    <thead>
      <tr>
        <th>{lang('name')}</th>
        <th>{lang('module')}</th>
        <th>{lang('logged')}</th>
        <th>{lang('start')}</th>
        <th>{lang('frequency')}</th>
        <th>{lang('until')}{if $gmtime} (GMT){/if}</th>
        <th>{lang('errors')}</th>
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
          {if $job->start < $smarty.now - $async_freq}<span class="text-red">{$p2}</span>
          {elseif $job->start < $smarty.now + $async_freq}<span class="text-green">{$p2}</span>
          {else}{$p2}{/if}
          {/if}
        </td>
        <td>{if $job->recurs}{$job->recurs}{/if}</td>
        <td>{if $job->until}{$job->until|localedate_format:'%x %X'}{/if}</td>
        <td>{if $job->errors > 0}<span class="text-red">{$job->errors}</span>{/if}</td>
      </tr>
    {/foreach}
    </tbody>
  </table>
{/if}
</div>
<div id="cmshelp_jobsinfo" title="{lang('jobs_list')}" style="display:none">
{lang_by_realm('help','help_jobs')}
</div>
{if $pdev}
<div id="cmshelp_jobsconfig" title="{lang('jobs_configure')}" style="display:none">
{lang_by_realm('help','help_jobsconfigure')}
</div>
{/if}
