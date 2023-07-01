<div class="pagecontainer">

{$theme->StartTabHeaders()}
	{$theme->SetTabHeader('content',lang('sysmaintab_content'),isset($active_content))}
	{$theme->SetTabHeader('database',lang('sysmaintab_database'),isset($active_database))}
	{if isset($changelog)}
		{$theme->SetTabHeader('changelog',lang('sysmaintab_changelog'),isset($active_changelog))}
	{/if}
{$theme->EndTabHeaders()}

{$theme->StartTabContent()}

	{$theme->StartTab('content')}
		<form action="{$formurl}" method="post">
			<fieldset>
				<legend>{lang('sysmain_cache_status')}&nbsp;</legend>
				<div class="pageoverflow">
					<p class="pagetext">{lang('clearcache')}:</p>
					<p class="pageinput">
						<input class="pagebutton" type="submit" name="clearcache" value="{lang('clear')}" />
					</p>
				</div>
			</fieldset>
		</form>

		<fieldset>
			<legend>{lang('sysmain_content_status')}&nbsp;</legend>
			<form action="{$formurl}" method="post">
				{$pagecount} {lang('sysmain_pagesfound')}

				<div class="pageoverflow">
					<p class="pagetext">{lang('sysmain_updatehierarchy')}:</p>
					<p class="pageinput">
						<input class="pagebutton" type="submit" name="updatehierarchy" value="{lang('sysmain_update')}" />
					</p>
				</div>
			</form>

			<form action="{$formurl}" method="post">
				<div class="pageoverflow">
					<p class="pagetext">{lang('sysmain_updateurls')}:</p>
					<p class="pageinput">
						<input class="pagebutton" type="submit" name="updateurls" value="{lang('sysmain_update')}" />
					</p>
				</div>
			</form>

			{if $withoutaliascount!="0"}
				<form action="{$formurl}" method="post" onsubmit="return confirm('{lang("sysmain_confirmfixaliases")|escape:"javascript"}');">
					<div class="pageoverflow">
						<p class="pagetext">{$withoutaliascount} {lang('sysmain_pagesmissinalias')}:</p>
						<p class="pageinput">
							{foreach $pagesmissingalias as $page}
								{*{$page.count}.*} {$page.content_name}<br />
							{/foreach}
							<br />
							<input class="pagebutton" type="submit" name="addaliases" value="{lang('sysmain_fixaliases')}" />
						</p>
					</div>
				</form>
			{/if}

			{if $invalidtypescount!="0"}
				<form action="{$formurl}" method="post" onsubmit="return confirm('{lang("sysmain_confirmfixtypes")|escape:"javascript"}');">
					<div class="pageoverflow">
						<p class="pagetext">{$invalidtypescount} {lang('sysmain_pagesinvalidtypes')}:</p>
						<p class="pageinput">
							{foreach $pageswithinvalidtype as $page}
								{$page.content_name} <em>({$page.content_alias}) - {$page.type}</em><br />
							{/foreach}
							<br />
							<input class="pagebutton" type="submit" name="fixtypes" value="{lang('sysmain_fixtypes')|escape:'javascript'}" />
						</p>
					</div>
				</form>
			{/if}

			{if $invalidtypescount=="0" && $withoutaliascount==""}
				<p class='green'><strong>{lang('sysmain_nocontenterrors')}</strong></p>
			{/if}

		</fieldset>
	{$theme->EndTab()}

	{$theme->StartTab('database')}
		<form action="{$formurl}" method="post">
			<fieldset>
				<legend>{lang('sysmain_database_status')}:&nbsp;</legend>
				<p>{$tablecount} {lang('sysmain_tablesfound',$nonseqcount)}</p>

				{if $errorcount==0}
					<p class='green'><strong>{lang('sysmain_nostr_errors')}</strong></p>
				{else}
					<p class='red'><strong>{$errorcount} {if $errorcount>1}{lang('sysmain_str_errors')}{else}{lang('sysmain_str_error')}{/if}:  {$errortables}</strong></p>
				{/if}

				<div class="pageoverflow">
					<p class="pagetext">{lang('sysmain_optimizetables')}:</p>
					<p class="pageinput">
						<input class="pagebutton" type="submit" name="optimizeall" value="{lang('sysmain_optimize')}" />
					</p>
				</div>
				<div class="pageoverflow">
					<p class="pagetext">{lang('sysmain_repairtables')}:</p>
					<p class="pageinput">
						<input class="pagebutton" type="submit" name="repairall" value="{lang('sysmain_repair')}" />
					</p>
				</div>
			</fieldset>
		</form>
	{$theme->EndTab()}

	{if isset($changelog)}
		{$theme->StartTab('changelog')}
			<p class='file'>{$changelogfilename}</p>
			<div class="changelog">{$changelog}</div>
		{$theme->EndTab()}
	{/if}

{$theme->EndTabContent()}

</div>
