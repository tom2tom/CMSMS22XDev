<div class="pagecontainer">
{$header}
{if isset($message)}
	<p class="pageheader">{$message}</p>
{/if}

<div class="information">{lang('info_changegroupperms')} {cms_help key2='help_group_permissions' title=lang('info_changegroupperms')}</div>

<div class="pageoptions endalign">
	<form action="changegroupperm.php" method="post">
		<div class="hidden">
			<input type="hidden" name="{$hiddenname}" value="{$hiddenval}">
		</div>
		<label for="groupsel">{lang('selectgroup')}:</label>&nbsp;
		<select name="groupsel" id="groupsel">
		{foreach $allgroups as $thisgroup}
			<option value="{$thisgroup->id}"{if $thisgroup->id == $disp_group} selected{/if}>{$thisgroup->name}</option>
{/foreach}
		</select>&nbsp;
		<input type="submit" name="filter" data-ui-icon="ui-icon-caret-1-n" value="{lang('apply')}">
	</form>
</div>

<form id="groupname" action="changegroupperm.php" method="post">
	<div class="hidden">
		<input type="hidden" name="{$hiddenname}" value="{$hiddenval}">
		<input type="hidden" name="submitted" value="1">
		{$hidden2}
	</div>

	<div class="pageoverflow">
		<div class="pageoptions">
			<input type="submit" name="changeperm" value="{lang('submit')}">
			<input type="submit" name="cancel" value="{lang('cancel')}">
		</div>
	</div>
{$np=0}
	<table class="pagetable scrollable" id="permtable">
		<thead>
			<tr>{$ncols=1}
				<th>{lang('permission')}</th>
				{foreach $group_list as $thisgroup}{$gid=$thisgroup->id}
					{if $gid != -1}{$ncols=$ncols+1}<th class="g{$gid}">{$thisgroup->name}</th>{/if}
				{/foreach}
			</tr>
		</thead>
		<tbody>
			{foreach $perms as $section => $list}
				<tr>
					<td colspan="{$ncols}"><h3>{$section|upper}</h3></td>
				</tr>
				{foreach $list as $perm}{$np=$np+1}
					{cycle values='row1,row2' assign='currow'}
					<tr class="{$currow}">
						<td>
							&nbsp;&nbsp;&nbsp;<strong>{$perm->label}</strong>
							{if !empty($perm->description)}<div class="description">&nbsp;&nbsp;&nbsp;{$perm->description}</div>{/if}
						</td>
						{foreach $group_list as $thisgroup}{$gid=$thisgroup->id}
						{if $gid != -1}
							<td class="g{$gid}"><input type="checkbox" name="pg_{$perm->id}_{$gid}" value="1"{if isset($perm->group[$gid]) || $gid == 1} checked{/if}{if $gid == 1} disabled{/if}></td>
						{/if}
{/foreach}
					</tr>
{/foreach}
			{/foreach}
		</tbody>
	</table>
{if $np > 10}
	<div class="pageoverflow">
		<div class="pageoptions">
			<input type="submit" name="changeperm" value="{lang('submit')}">
			<input type="submit" name="cancel" value="{lang('cancel')}">
		</div>
	</div>
{/if}
</form>
</div>
