<div class="pagecontainer">
	{tab_header name='content' label=lang('sysmaintab_content') active=$active_content}
	{tab_header name='database' label=lang('sysmaintab_database') active=$active_database}
{if !empty($pjobs)}
	{tab_header name='jobs' label=lang('sysmaintab_jobs') active=$active_jobs}
{/if}
{if !empty($changelog)}
	{tab_header name='changelog' label=lang('sysmaintab_changelog') active=$active_changelog}
{/if}
	{tab_start name='content'}
		<form action="{$formurl}" method="post">
			<fieldset>
				<legend>{lang('sysmain_cache_status')}&nbsp;</legend>
{if isset($filescount)}				<p>{lang('sysmain_filesfound',$filescount)}</p>{/if}
				<div class="pageoverflow">
					<p class="pagetext">
						<label for="btnclear">{lang('clearcache')}:</label>
					</p>
					<p class="pageinput">
						<input type="submit" id="btnclear" name="clearcache" data-ui-icon="ui-icon-minusthick" value="{lang('clear')}">
					</p>
				</div>
			</fieldset>
		</form>

		<fieldset>
			<legend>{lang('sysmain_content_status')}&nbsp;</legend>
			<p>{lang('sysmain_pagesfound',$pagecount)}</p>
{if $invalidtypescount == 0 && $withoutaliascount == 0}
			<p class="green"><strong>{lang('sysmain_nocontenterrors')}</strong></p>
{/if}
			<br>
			<form action="{$formurl}" method="post">
				<div class="pageoverflow">
					<p class="pagetext">
						<label for="btnhier">{lang('sysmain_updatehierarchy')}:</label>
					</p>
					<p class="pageinput">
						<input type="submit" id="btnhier" name="updatehierarchy" data-ui-icon="ui-icon-gear" value="{lang('sysmain_update')}">
					</p>
				</div>
			</form>

			<form action="{$formurl}" method="post">
				<div class="pageoverflow">
					<p class="pagetext">
						<label for="btnurls">{lang('sysmain_updateurls')}:</label>
					</p>
					<p class="pageinput">
						<input type="submit" id="btnurls" name="updateurls" data-ui-icon="ui-icon-gear" value="{lang('sysmain_update')}">
					</p>
				</div>
			</form>

{if $withoutaliascount > 0}
			<form action="{$formurl}" method="post" onsubmit="return confirm('{lang('sysmain_confirmfixaliases')|escape:'javascript'}');">
				<div class="pageoverflow">
					<p class="pagetext"><label>{lang('sysmain_pagesmissinalias',$withoutaliascount)}:</label></p>
					<p class="pageinput">
						{foreach $pagesmissingalias as $page}
							{*{$page.count}.*} {$page.content_name}<br>
						{/foreach}
						<br>
						<input type="submit" name="addaliases" data-ui-icon="ui-icon-gear" value="{lang('sysmain_fixaliases')}">
					</p>
				</div>
			</form>
{/if}

{if $invalidtypescount > 0}
			<form action="{$formurl}" method="post" onsubmit="return confirm('{lang('sysmain_confirmfixtypes')|escape:'javascript'}');">
				<div class="pageoverflow">
					<p class="pagetext"><label>{lang('sysmain_pagesinvalidtypes',$invalidtypescount)}:</label></p>
					<p class="pageinput">
						{foreach $pageswithinvalidtype as $page}
							{$page.content_name} <em>({$page.content_alias}) - {$page.type}</em><br>
						{/foreach}
						<br>
						<input type="submit" name="fixtypes" data-ui-icon="ui-icon-gear" value="{lang('sysmain_fixtypes')|escape:'javascript'}">
					</p>
				</div>
			</form>
{/if}
		</fieldset>

	{tab_start name='database'}
		<form action="{$formurl}" method="post">
			<fieldset>
				<legend>{lang('sysmain_database_status')}</legend>
				<p>{$tablecount} {lang('sysmain_tablesfound',$nonseqcount)}</p>

				{if $errorcount == 0}
					<p class="green"><strong>{lang('sysmain_nostr_errors')}</strong></p>
				{else}
					<p class="red"><strong>{$errorcount} {if $errorcount>1}{lang('sysmain_str_errors')}{else}{lang('sysmain_str_error')}{/if}: {$errortables}</strong></p>
				{/if}

				<div class="pageoverflow">
					<p class="pagetext">
						<label for="btntables">{lang('sysmain_optimizetables')}:</label>
					</p>
					<p class="pageinput">
						<input type="submit" id="btntables" name="optimizeall" data-ui-icon="ui-icon-star" value="{lang('sysmain_optimize')}">
					</p>
				</div>
				<div class="pageoverflow">
					<p class="pagetext">
						<label for="btnrepair">{lang('sysmain_repairtables')}:</label>
					</p>
					<p class="pageinput">
						<input type="submit" id="btnrepair" name="repairall" data-ui-icon="ui-icon-gear" value="{lang('sysmain_repair')}">
					</p>
				</div>
			</fieldset>
		</form>
{if !empty($pjobs)}
	{tab_start name='jobs'}
		<fieldset>
			<legend>{lang('async_status')}</legend>
			<p>{lang('jobscount',$jobscount)}</p>
{if $jobscount > 0}
{if empty($jobserrs)}
			<p class="green"><strong>{lang('noerrorsinjobs')}</strong></p>
{else}
			<p class="red">{lang('errorsinjobs')}</p>
			<ul style="margin-top:0">
{foreach $jobserrs as $name => $num}			<li>{$name} ({$num})</li>
{/foreach}
			</ul>
{/if}
			<form action="{$formurl}" method="post">
				<p class="pagetext">
					<label for="btnjobs">{lang(clearjobrecords)}:</label>
				</p>
				<p class="pageinput">
					<input type="submit" id="btnjobs" name="clearjobs" data-ui-icon="ui-icon-minusthick" value="{lang('clear')|escape:'javascript'}">
				</p>
			</form>
		</fieldset>
{/if}
{/if}
{if !empty($changelog)}
	{tab_start name='changelog'}
{*		<p class="file">{$changelogfilename}</p>*}
		<div class="changelog">
			{$changelog}
		</div>
{/if}
	{tab_end}
</div>
