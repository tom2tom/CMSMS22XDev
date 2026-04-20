<div class="pagecontainer">
{if empty($smarty.get.cleanreport)}
  <p class="pageshowrows">
    <span class="ui-button ui-state-default ui-corner-all ui-button-text-icon-primary" style="height:16px" role="button">
      <a href="{$systeminfo_cleanreport}">
        <span class="ui-button-icon-primary ui-icon ui-icon-circle-zoomin"></span>
        <span class="ui-button-text">{lang('copy_paste_forum')}</span>
      </a>
    </span>
  </p>
{/if}
  <div class="pageoverflow">
    <div class="information">
      <p>{lang('help_systeminformation')}</p>
    </div>
    <hr>
    <table class="pagetable">
      <thead>
        <tr>
          <th colspan="3">{lang('cms_install_information')}</th>
        </tr>
      </thead>
      <tbody>
        <tr class="{cycle values='row1,row2'}">
          <td style="width:45%">{lang('cms_version')}</td>
          <td style="width:5%"></td>
          <td style="width:50%">{$cms_version}</td>
        </tr>
      </tbody>
    </table>
    <br>
    <table class="pagetable">
      <thead>
        <tr>
          <th colspan="3">{lang('installed_modules')}</th>
        </tr>
      </thead>
      <tbody>
        {foreach $installed_modules as $module}
        <tr class="{cycle values='row1,row2'}">
          <td style="width:45%">{$module.module_name}</td>
          <td style="width:5%"></td>
          <td style="width:50%">{$module.version}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>
    <br>
    <table class="pagetable">
      <thead>
        <tr>
          <th colspan="3">{lang('config_information')}</th>
        </tr>
      </thead>
      <tbody>
      {foreach $config_info as $view => $tmp}
        {foreach $tmp as $key => $test}
        <tr class="{cycle values='row1,row2'}">
          <td style="width:45%">{$test->title}</td>
          <td style="width:5%">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}">{/if}</td>
          <td style="width:50%">
{if isset($test->value)}{$test->value}{else}&nbsp;{/if}
{if isset($test->secondvalue)}({$test->secondvalue}){/if}
{if isset($test->error_fragment)}<a class="external" rel="external" href="{$cms_install_help_url}#{$test->error_fragment}"><img src="themes/{$themename}/images/icons/system/info-external.gif"{* title="?"*} alt="Info icon"></a>{/if}
{if isset($test->message)}<br>{$test->message}{/if}
          </td>
        </tr>
        {/foreach}
      {/foreach}
      </tbody>
    </table>
    <br>
    <table class="pagetable">
      <thead>
        <tr>
          <th colspan="3">{lang('performance_information')}</th>
        </tr>
      </thead>
      <tbody>
      {foreach $performance_info as $view => $tmp}
        {foreach $tmp as $key => $test}
        <tr class="{cycle values='row1,row2'}">
          <td style="width:45%">{$test->title}</td>
          <td style="width:5%">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}">{/if}</td>
          <td style="width:50%">
{if isset($test->value)}{$test->value}{else}&nbsp;{/if}
{if isset($test->secondvalue)}({$test->secondvalue}){/if}
{if isset($test->error_fragment)}<a class="external" rel="external" href="{$cms_install_help_url}#{$test->error_fragment}"><img src="themes/{$themename}/images/icons/system/info-external.gif"{* title="?"*} alt="Info icon"></a>{/if}
{if isset($test->message)}<br>{$test->message}{/if}
          </td>
        </tr>
        {/foreach}
      {/foreach}
      </tbody>
    </table>
    <br>
    <table class="pagetable">
      <thead>
        <tr>
          <th colspan="3">{lang('php_information')}</th>
        </tr>
      </thead>
      <tbody>
      {foreach $php_information as $view => $tmp}
        {foreach $tmp as $key => $test}
        <tr class="{cycle values='row1,row2'}">
          <td style="width:45%">{lang($key)} ({$key})</td>
          <td style="width:5%">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}">{/if}</td>
          <td style="width:50%">
{if isset($test->value) && $test->display_value != 0}{$test->value}{else}&nbsp;{/if}
{if isset($test->secondvalue)}({$test->secondvalue}){/if}
{if isset($test->error_fragment)}<a class="external" rel="external" href="{$cms_install_help_url}#{$test->error_fragment}"><img src="themes/{$themename}/images/icons/system/info-external.gif"{* title="?"*} alt="Info icon"></a>{/if}
{if isset($test->message)}{$test->message}{/if}
{if isset($test->opt)}
        {foreach $test->opt as $key => $opt}
          <br>{$key}: {$opt.message} <img class="systemicon" src="themes/{$themename}/images/icons/extra/{$opt.res}.gif" alt="{$opt.res_text|default:''}" title="{$opt.res_text|default:''}">
        {/foreach}
  {/if}
          </td>
        </tr>
        {/foreach}
      {/foreach}
      </tbody>
    </table>
    <br>
    <table class="pagetable">
      <thead>
        <tr>
          <th colspan="3">{lang('server_information')}</th>
        </tr>
      </thead>
      <tbody>
      {foreach $server_info as $view => $tmp}
        {foreach $tmp as $key => $test}
        <tr class="{cycle values='row1,row2'}">
          <td style="width:45%">{lang($key)} ({$key})</td>
          <td style="width:5%">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res|default:"space"}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}">{/if}</td>
          <td style="width:50%">
{if isset($test->value)}{$test->value|lower}{else}&nbsp;{/if}
{if isset($test->secondvalue)}({$test->secondvalue}){/if}
{if isset($test->message)}<br>{$test->message}{/if}
          </td>
        </tr>
        {/foreach}
      {/foreach}
      </tbody>
    </table>
    <br>
    <table class="pagetable">
      <thead>
        <tr>
          <th colspan="3">{lang('permission_information')}</th>
        </tr>
      </thead>
      <tbody>
      {foreach $permission_info as $view => $tmp}
        {foreach $tmp as $key => $test}
      <tr class="{cycle values='row1,row2'}">
        <td style="width:45%">{$key}</td>
        <td style="width:5%">{if isset($test->res)}<img class="systemicon" src="themes/{$themename}/images/icons/extra/{$test->res}.gif" title="{$test->res_text|default:''}" alt="{$test->res_text|default:''}">{/if}</td>
        <td style="width:50%">
{if isset($test->value)}{$test->value}{else}&nbsp;{/if}
{if isset($test->secondvalue)}({$test->secondvalue}){/if}
{if isset($test->message)}<br>{$test->message}{/if}
        </td>
      </tr>
        {/foreach}
      {/foreach}
      </tbody>
    </table>
  </div>
</div>
