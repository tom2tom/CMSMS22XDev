<div id="topcontent_wrap">
	{strip}
{foreach $nodes as $node}
{$icon="themes/OneEleven/images/icons/topfiles/`$node.name`"}
{$module="../modules/`$node.name`/images/icon"}
	{if $node.show_in_menu && $node.url && $node.title}
	<div class="dashboard-box{if $node@index % 3 == 2} last{/if}">
		<nav class="dashboard-inner cf">
			<a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}{if $node.selected} class="selected"{/if} tabindex="-1">
			{if file_exists($module|cat:'.png')}
			<img src="{$module}.png" width="48" height="48" alt="{$node.title}"{if $node.description} title="{$node.description|adjust:'strip_tags'}"{/if} />
			{elseif file_exists($module|cat:'.gif')}
			<img src="{$module}.gif" width="48" height="48" alt="{$node.title}"{if $node.description} title="{$node.description|adjust:'strip_tags'}"{/if} />
			{elseif file_exists($icon|cat:'.png')}
			<img src="{$icon}.png" width="48" height="48" alt="{$node.title}"{if $node.description} title="{$node.description|adjust:'strip_tags'}"{/if} />
			{elseif file_exists($icon|cat:'.gif')}
			<img src="{$icon}.gif" width="48" height="48" alt="{$node.title}"{if $node.description} title="{$node.description|adjust:'strip_tags'}"{/if} />
			{else}
			<img src="themes/OneEleven/images/icons/topfiles/modules.png" width="48" height="48" alt="{$node.title}"{if $node.description} title="{$node.description|adjust:'strip_tags'}"{/if} />
			{/if}</a>
			<h3>
				<a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}{if $node.selected} class="selected"{/if}>{$node.title}</a>
			</h3>
			{if $node.description}
			<span class="description">{$node.description}</span>
			{/if}
			{if isset($node.children)}
			<h4>{lang('subitems')}</h4>
			<ul class="subitems cf">
			{foreach $node.children as $one}
				<li><a href="{$one.url}"{if isset($one.target)} target="{$one.target}"{/if} {if substr($one.url,0,6) == 'logout' and isset($is_sitedown)}onclick="return confirm('{lang("maintenance_warning")|escape:"javascript"}');"{/if}>{$one.title}</a></li>
			{/foreach}
			</ul>
			{/if}
		</nav>
	</div>
	{if $node@index % 3 == 2}
	<div class="clear"></div>
	{/if}
	{/if}
{/foreach}
{/strip}
</div>
