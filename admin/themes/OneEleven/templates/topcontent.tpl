<div id="topcontent_wrap">
	{strip}
{foreach $nodes as $node}
	{if $node.show_in_menu && $node.url && $node.title}
	<div class="dashboard-box{if $node@index % 3 == 2} last{/if}">
		<nav class="dashboard-inner cf">
		{if !empty($node.img)}
			<a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}{if $node.selected} class="selected"{/if} tabindex="-1">
				<img src="{$node.img}" alt="{$node.title}"{if $node.description} title="{$node.description|adjust:'strip_tags'}"{/if}>
			</a>
		{/if}
			<h3>
				<a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}{if $node.selected} class="selected"{/if}>{$node.title}</a>
			</h3>
			{if $node.description}
			<span class="description">{$node.description}</span>
			{/if}
			{if !empty($node.children)}
			<h4>{lang('subitems')}</h4>
			<ul class="subitems cf">
			{foreach $node.children as $one}
				<li><a href="{$one.url}"{if isset($one.target)} target="{$one.target}"{/if}{if substr($one.url,0,6) == 'logout' and isset($is_sitedown)} onclick="return confirm('{lang("maintenance_warning")|escape:"javascript"}');"{/if}>{$one.title}</a></li>
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
