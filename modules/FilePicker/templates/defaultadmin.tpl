{if !empty($profiles)}
<script>
$(function() {
 $('.delete').on('click', function(e) {
  e.preventDefault();
  var _url = $(this).attr('href');
  cms_confirm('{$mod->Lang('confirm_delete')|escape:'javascript'}').done(function() {
   window.location.href = _url;
  });
 });
});
</script>
{/if}
<div class="pageoptions">
	<a href="{cms_action_url action=edit_profile}">{admin_icon alt="{$mod->Lang('add_profile')}" title="{$mod->Lang('add_profile')}" icon='newobject.gif'} {$mod->Lang('add_profile')}</a>
</div>
{if !empty($profiles)}
<table class="pagetable">
	<thead>
		<tr>
		<th>{$mod->Lang('th_id')}</th>
		<th>{$mod->Lang('th_name')}</th>
		<th>{$mod->Lang('th_reltop')}</th>
		<th style="text-align:center">{$mod->Lang('th_default')}</th>
		<th>{$mod->Lang('th_created')}</th>
		<th>{$mod->Lang('th_last_edited')}</th>
		<th class="pageicon">&nbsp;</th>
		<th class="pageicon">&nbsp;</th>
		<th class="pageicon">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
{foreach $profiles as $profile}
		<tr class="{cycle values='row1,row2'}">
			{cms_action_url action=edit_profile pid=$profile->id assign='edit_url'}
			<td>{$profile->id}</td>
			<td><a href="{$edit_url}" title="{$mod->Lang('edit_profile')}">{$profile->name|cms_escape}</a></td>
			<td>{$profile->reltop}</td>
			<td style="text-align:center">
{if $profile->id == $dflt_profile_id}
			{admin_icon title=lang('yes') icon='true.gif'}
{else}
			<a href="{cms_action_url action=setdflt_profile pid=$profile->id}">{admin_icon title=lang('no') icon='false.gif'}</a>
{/if}
			</td>
			<td>{$profile->created|cms_date_format}</td>
			<td>{$profile->modified|cms_date_format}</td>
			<td><a href="{$edit_url}" class="pageoptions">{admin_icon alt="{$mod->Lang('edit_profile')}" title="{$mod->Lang('edit_profile')}" icon='edit.gif'}</a></td>
			<td><a href="{cms_action_url action=edit_profile pid=$profile->id clone=1}">{admin_icon alt="{$mod->Lang('title_copy_profile')}" title="{$mod->Lang('title_copy_profile')}" icon='copy.gif'}</a></td>
			<td><a href="{cms_action_url action=delete_profile pid=$profile->id}" class="pageoptions delete">{admin_icon alt="{$mod->Lang('delete_profile')}" title="{$mod->Lang('delete_profile')}" icon='delete.gif'}</a></td>
		</tr>
{/foreach}
	</tbody>
</table>
{else}
<p class="information">{$mod->Lang('no_profiles')}</p>
{/if}
