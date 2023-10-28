{strip}
<div id="admin-alerts" class="notification" role="alert">
	<div class="box-shadow">
		&nbsp;
	</div>
	{if count($items)}
		<a href="javascript:void(0);" class="open" title="{lang('notifications')}"><span>{$cnt=count($items)}{if $cnt > 1}{lang('notifications_to_handle',$cnt)}{else}{lang('notification_to_handle',$cnt)}{/if}</span></a>
		<div class="alert-dialog dialog" role="alertdialog" title="{lang('alerts')}">
			<ul>
			{foreach $items as $one}
				<li class="alert-box" data-alert-name="{$one->get_prefname()}">
					<div class="alert-head ui-corner-all {if $one->priority == '_high'}ui-state-error red{elseif $one->priority == '_normal'}ui-state-highlight orange{else}ui-state-highlightblue{/if}">
					   {$icon=$one->get_icon()}
					   {if $icon}
						 <img class="alert-icon ui-icon" alt="" src="{$icon}" title="{lang('remove_alert')}">
					   {else}
						 <span class="alert-icon ui-icon {if $one->priority != '_low'}ui-icon-alert{else}ui-icon-info{/if}" title="{lang('remove_alert')}"></span>
					   {/if}
					   <span class="alert-title">{$one->title|default:'No title given'}</span>
					   <span class="alert-remove ui-icon ui-icon-close" title="{lang('remove_alert')}"></span>
					   <div class="alert-msg">{$one->get_message()}</div>
					</div>
				</li>
			{/foreach}
			</ul>
		</div>
	{/if}
</div>
{/strip}
