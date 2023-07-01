{strip}
{if !isset($depth)}{$depth='0'}{/if}
{if $depth == '0'}
<nav class="navigation" id="oe_menu" role="navigation">
	<div class="box-shadow">&nbsp;</div>
	<ul id="oe_pagemenu">
{/if}
{foreach $nav as $navitem}
	<li class="nav{if !isset($navitem.system) && (isset($navitem.module) || isset($navitem.firstmodule))} module{/if}{if !empty($navitem.selected) || (isset($smarty.get.section) && $smarty.get.section == $navitem.name|lower)} current{/if}">
		<a href="{$navitem.url}" class="{$navitem.name|lower}{if isset($navitem.children)} parent{/if}"{if isset($navitem.target)} target="_blank"{/if} title="{if !empty($navitem.description)}{$navitem.description|adjust:'strip_tags'}{else}{$navitem.title|adjust:'strip_tags'}{/if}"{if substr($navitem.url,0,6) == 'logout' && isset($is_sitedown)} onclick="return confirm('{lang("maintenance_warning")|escape:"javascript"}');"{/if}>
			{$navitem.title}
		</a>
	{if $depth == '0'}
		<span class="open-nav" title="{lang('open')}/{lang('close')} {$navitem.title|adjust:'strip_tags'}">{lang('open')}/{lang('close')} {$navitem.title}</span>
	{/if}
	{if !empty($navitem.children)}
		{if $depth == '0'}<ul>{/if}
		{include file=$smarty.template nav=$navitem.children depth=$depth+1}
		{if $depth == '0'}</ul>{/if}
	{/if}
	</li>
{/foreach}
{if $depth == '0'}
	</ul>
</nav>
{/if}
{/strip}
