{if $itemcount > 0}

<table class="pagetable">
	<thead>
		<tr>
			<th>{$filenametext}</th>
			<th class="pageicon">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
	{foreach $items as $entry}
		<tr class="{$entry->rowclass}">
			<td>{$entry->filename}</td>
			<td>{if isset($entry->importlink)}{$entry->importlink}{/if}</td>
		</tr>
	{/foreach}
	</tbody>
</table>
{else}
<h4>{$nofilestext}</h4>
{/if}
