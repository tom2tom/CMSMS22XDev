{* wizard step 3 *}

{extends file='wizard_step.tpl'}
{block name='logic'}
    {$subtitle = tr('title_step3')}
    {$current_step = '3'}
{/block}

{block name='contents'}

{if $tests_failed}
  {if !$can_continue}
    <div class="message red">{tr('step3_failed')}</div>
  {else}
    <div class="message yellow">{tr('sometests_failed')}</div>
  {/if}
{/if}

{if $tests_failed || $verbose}
  <table class="table zebra-table bordered-table installer-test-information">
    <thead class="tbhead">
        <tr>
            <th>{tr('th_status')}</th>
            <th>{tr('th_testname')}</th>
        </tr>
    </thead>
    <tbody>
    {foreach $tests as $test}
        {cycle values='odd,even' assign='rowclass'}
        <tr class="{$rowclass}{if $test->status == 'test_fail'} error{/if}{if $test->status == 'test_warn'} warning{/if}">
            <td class="{$test->status}">{if $test->status == 'test_fail'}<i title="{tr('test_failed')}" class="icon-cancel-circle red"></i>{elseif $test->status == 'test_warn'}<i title="{tr('test_warning')}" class="icon-warning yellow"></i>{else}<i title="{tr('test_passed')|adjust:'strip_tags'}" class="icon-checkmark-circle green"></i>{/if}</td>
            <td>
                {tr($test->name)}
                {$str = $test->msg()}
                {if $str != '' && ($verbose || $test->status != 'test_pass')}
                  <br>
                  <span class="tests-infotext">{$str}</span>
                {/if}
            </td>
        </tr>
    {/foreach}
    </tbody>
  </table>
{else}
  <div class="message green">{tr('step3_passed')}</div>
{/if}

{if $tests_failed}
<table class="table bordered-table installer-test-legend small-font">
    <caption>
        {tr('legend')}
    </caption>
    <thead>
        <tr>
            <th>{tr('symbol')}</th>
            <th>{tr('meaning')}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="test_fail red"><i title="{tr('test_failed')}" class="icon-cancel-circle red"></td>
            <td>{tr('test_failed')}</td>
        </tr>
        <tr>
            <td class="test_pass green"><i title="{tr('test_passed')|adjust:'strip_tags'}" class="icon-checkmark-circle green"></i></td>
            <td>{tr('test_passed')}</td>
        </tr>
        <tr>
            <td class="test_warn yellow"><i title="{tr('test_warning')}" class="icon-warning yellow"></i></td>
            <td>{tr('test_warning')}</td>
        </tr>
    </tbody>
</table>
{/if}
<div class="message {if $tests_failed}yellow{else}blue{/if}">{tr('warn_tests')}</div>

<div id="bottom_nav">
{if $tests_failed}
  <a onclick="window.location.reload();" class="action-button orange" title="{tr('retry')}">{tr('retry')} <i class="icon-loop"></i></a>
{/if}
{if $can_continue} <a href="{$next_url}" class="action-button positive" title="{tr('next')}">{tr('next')} &rarr;</a>{/if}
</div>

{/block}
