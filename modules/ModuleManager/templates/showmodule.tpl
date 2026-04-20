{if isset($title)}
<h3>{$title}</h3>
{/if}

{if isset($errmessage1)}
<p class="pageerror">{$errmessage1}</p>
<br>
{/if}

{if !empty($items)}
{strip}
{function get_module_status_icon}
{if $status == 'stale'}
{$stale_img}
{elseif $status == 'warn'}
{$warn_img}
{elseif $status == 'new'}
{$new_img}
{/if}
{/function}
{/strip}
<table class="pagetable scrollable">
	<thead>
		<tr>
			<th></th>
			<th>{$mod->Lang('nametext')}</th>
			<th><span title="{$mod->Lang('title_modulelastversion')}">{$mod->Lang('vertext')}</span></th>
			<th><span title="{$mod->Lang('title_modulereleasedate')}">{$mod->Lang('releasedate')}</span></th>
{*			<th><span title="{$mod->Lang('title_moduledownloads')}">{$mod->Lang('downloads')}</span></th>*}
			<th>{$mod->Lang('sizetext')}</th>
			<th>{$mod->Lang('statustext')}</th>
			<th>&nbsp;</th>
			<th>&nbsp;</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
{foreach $items as $entry}
		{cycle values="row1,row2" assign='rowclass'}
		<tr class="{$rowclass}"{if $entry->age=='new'} style="font-weight:bold"{/if}>
			<td>{get_module_status_icon status=$entry->age}</td>
			<td><span title="{$entry->description|default:''|adjust:'strip_tags'|cms_escape}">{$entry->name}</span></td>
			<td>{$entry->version}</td>
			<td>{$entry->date|localedate_format:'%x'}</td>
{*			<td>{$entry->downloads}</td>*}
			<td>{$entry->size}</td>
			<td>{$entry->status}</td>
			<td><span title="{$mod->Lang('title_modulereleasedepends')}">{$entry->dependslink}</span></td>
			<td><span title="{$mod->Lang('title_modulereleasehelp')}">{$entry->helplink}</span></td>
			<td><span title="{$mod->Lang('title_modulereleaseabout')}">{$entry->aboutlink}</span></td>
		</tr>
{/foreach}
	</tbody>
</table>
{else}
<p class="pageerror">{$errmessage2}</p>
{/if}
