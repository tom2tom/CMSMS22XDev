{if !empty($fitems)}
<script>
$(function() {
  $('a.del_fielddef').on('click', function(ev) {
    ev.preventDefault();
    var _url = $(this).attr('href');
    cms_confirm('{$mod->Lang('areyousure')|escape:'javascript'}').done(function() {
      window.location.href = _url;
    });
  });
});
</script>
{/if}
<div class="pageoptions">
	<a href="{$addurl}" title="{$mod->Lang('addfielddef')}">{admin_icon icon='newobject.gif'} {$mod->Lang('addfielddef')}</a>
</div>
{if !empty($fitems)}
<table class="pagetable">
	<thead>
		<tr>
			<th>{$fielddeftext}</th>
			<th>{$typetext}</th>
			<th class="pageicon">&nbsp;</th>
			<th class="pageicon">&nbsp;</th>
			<th class="pageicon">&nbsp;</th>
			<th class="pageicon">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
{foreach $fitems as $entry}
	{cycle values="row1,row2" assign='rowclass'}
		<tr class="{$rowclass}">
			<td>{$entry->name}</td>
			<td>{$entry->type}</td>
			<td>{$entry->uplink}</td>
			<td>{$entry->downlink}</td>
			<td>{$entry->editlink}</td>
			<td><a href="{$entry->delete_url}" class="del_fielddef">{admin_icon icon='delete.gif' alt=$mod->Lang('delete')}</a></td>
		</tr>
{/foreach}
	</tbody>
</table>
{/if}
