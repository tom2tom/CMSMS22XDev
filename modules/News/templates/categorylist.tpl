<script type="text/javascript">
$(function() {
  $('a.del_cat').on('click', function(ev) {
    var self = $(this);
    ev.preventDefault();
    cms_confirm("{$mod->Lang('areyousure')|escape:'javascript'}").done(function() {
      window.location.href = self.attr('href');
    });
  });
});
</script>

<div class="pageoptions"><p class="pageoptions">
  <a href="{cms_action_url action='addcategory'}" title="{$mod->Lang('addcategory')}">{admin_icon icon='newobject.gif'} {$mod->Lang('addcategory')}</a>
  &nbsp;
  {if $itemcount > 1}<a href="{cms_action_url action='admin_reorder_cats'}" title="{$mod->Lang('reorder')}">{admin_icon icon='reorder.gif'} {$mod->Lang('reorder')}</a>{/if}
</p></div>

{if !empty($items)}
<table class="pagetable">
	<thead>
		<tr>
			<th>{$categorytext}</th>
			<th class="pageicon">&nbsp;</th>
			<th class="pageicon">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
{foreach $items as $entry}
		<tr class="{$entry->rowclass}">
			<td>{repeat string='&nbsp;&gt;&nbsp' times=$entry->depth}<a href="{$entry->edit_url}" title="{$mod->Lang('edit')}">{$entry->name|cms_escape}</a></td>
			<td><a href="{$entry->edit_url}" title="{$mod->Lang('edit')}">{admin_icon icon='edit.gif'}</a></td>
			<td><a href="{$entry->delete_url}" title="{$mod->Lang('delete')}" class="del_cat">{admin_icon icon='delete.gif'}</a></td>
		</tr>
{/foreach}
	</tbody>
</table>
{/if}
