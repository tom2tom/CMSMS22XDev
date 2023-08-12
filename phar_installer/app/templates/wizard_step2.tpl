{* wizard step 2 *}

{extends file='wizard_step.tpl'}
{block name='logic'}
    {$title = tr('title_step2')}
    {$current_step = '2'}
{/block}
{block name='contents'}

<script>
$(function() {
  $('#upgrade_info .link').css('cursor','pointer').on('click',function() {
    var e = '#'+$(this).data('content');
    $(e).dialog({
      minWidth: 500,
      modal: 'true'
    });
  });
});
</script>

<div class="installer-form">
  {wizard_form_start}
  {$label=tr('install')}

  {if $nofiles}
    <div class="message blue">{tr('step2_nofiles')}</div>
  {/if}

  {if empty($cmsms_info)}
    <div class="message blue">{tr('step2_nocmsms')}</div>
    {if !$install_empty_dir}
    <div class="message yellow">{tr('step2_install_dirnotempty2')}
      {if !empty($existing_files)}
      <ul>
        {foreach $existing_files as $one}
        <li>{$one}</li>
        {/foreach}
      </ul>
      {/if}
    </div>
    {/if}
  {else}
    {* its an upgrade or freshen *}
    {if isset($cmsms_info.error_status)}
      {if $cmsms_info.error_status == 'too_old'}
        <div class="message red">{tr('step2_cmsmsfoundnoupgrade')}</div>
      {elseif $cmsms_info.error_status == 'same_ver'}
        <div class="message blue">{tr('step2_errorsamever')}</div>
      {elseif $cmsms_info.error_status == 'too_new'}
        <div class="message red">{tr('step2_errortoonew')}</div>
      {else}
        <div class="message red">{tr('step2_errorother')}</div>
      {/if}
    {else}
      <div class="message yellow">{tr('step2_cmsmsfound')}</div>
    {/if}

    <ul class="existing-info no-list no-padding">
      <li class="row"><div class="six-col">{tr('step2_pwd')}:</div><div class="six-col"><span class="label blue"><i class="icon-folder-open"></i> {$pwd}</span></div></li>
      <li class="row"><div class="six-col">{tr('step2_version')}:</div><div class="six-col"><span class="label blue"><i class="icon-info"></i> {$cmsms_info.version} <em>({$cmsms_info.version_name})</em></span></div></li>
      <li class="row"><div class="six-col">{tr('step2_schemaver')}:</div><div class="six-col"><span class="label blue"><i class="icon-stack"></i> {$cmsms_info.schema_version}</span></div></li>
      <li class="row"><div class="six-col">{tr('step2_installdate')}:</div><div class="six-col"><span class="label blue"><i class="icon-calendar"></i> {$cmsms_info.mtime|localedate_format:'j %h Y'}</span></div></li>
    </ul>

    {if isset($cmsms_info.noupgrade)}
      <div class="message yellow">{tr('step2_minupgradever',$config.min_upgrade_version)}</div>
    {else}
      {$label=tr('upgrade')}
      {if !empty($upgrade_info)}
        <div class="message blue icon">
          <i class="icon-info message-icon"></i>
          <div class="content"><strong>{tr('step2_hdr_upgradeinfo')}</strong><br>{tr('step2_info_upgradeinfo')}</div>
        </div>
        <ul id="upgrade_info" class="no-list">
          {foreach $upgrade_info as $ver => $data}
          <li class="upgrade-ver row">
            <div class="four-col">{$ver}</div>
            <div class="four-col">
              {if $data.readme}
              <div class="label green link" data-content="r{$data@iteration}"><i class="icon-info"></i> {tr('readme_uc')}</div>
              {/if}
            </div>
            <div class="four-col">
              {if $data.changelog}
              <div class="label blue link" data-content="c{$data@iteration}"><i class="icon-info"></i> {tr('changelog_uc')}</div>
              {/if}
            </div>
          </li>
          {/foreach}
        </ul>
      {/if}
    {/if}
    {if isset($cmsms_info.error_status) && $cmsms_info.error_status == 'same_ver'}
    <div class="message blue">{tr('step2_info_freshen',$cmsms_info.config.db_prefix)}</div>
    {/if}
  {/if}

  <div id="bottom_nav">
    {if empty($cmsms_info)}
      {if isset($retry_url)}
      {* <a class="action-button orange" href="{$retry_url}" title="{tr('retry')}">{tr('retry')} <i class="icon-loop"></i></a> *}
      <a onClick="window.location.reload();" class="action-button orange" title="{tr('retry')}">{tr('retry')} <i class="icon-loop"></i></a>
      {/if}
      <input class="action-button positive" id="install" type="submit" name="install" value="{tr('install')}">
    {elseif !isset($cmsms_info.error_status)}
      <input class="action-button positive" id="upgrade" type="submit" name="upgrade" value="{tr('upgrade')} &rarr;">
    {elseif $cmsms_info.error_status == 'same_ver'}
      <input class="action-button positive" id="freshen" type="submit" name="freshen" value="{tr('freshen')} &rarr;">
    {/if}
  </div>

  {wizard_form_end}
</div>

<div class="hidden">
  {if !empty($upgrade_info)}
    {foreach $upgrade_info as $ver => $data}
      {if $data.readme}
      <div id="r{$data@iteration}" title="{tr('readme_uc')}: {$ver}">
        <div class="bigtext">{$data.readme}</div>
      </div>
      {/if}
      {if $data.changelog}
        <div id="c{$data@iteration}" title="{tr('changelog_uc')}: {$ver}">
          <div class="bigtext">{$data.changelog}</div>
        </div>
      {/if}
    {/foreach}
  {/if}
</div>
{/block}
