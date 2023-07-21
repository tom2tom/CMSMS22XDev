<div class="pagecontainer">
<div class="pageoverflow">
<h2>{si_lang a=systeminfo_copy_paste}</h2>
<br />
</div>
<hr />

<div class="pageoverflow">

<div id="copy_paste_in_forum">

<p>----------------------------------------------</p>

<p><strong>{'cms_version'|replace:'_':' '|adjust:'ucwords'}</strong>: {$cms_version}</p>
<p><strong>{'installed_modules'|replace:'_':' '|adjust:'ucwords'}</strong>:</p>
<ul>
{foreach $installed_modules as $module}
	<li>{$module.module_name}: {$module.version}</li>
{/foreach}
</ul>
<br />
{if $count_config_info > 1}
<p><strong>{'config_information'|replace:'_':' '|adjust:'ucwords'}</strong>:</p>
<ul>
	{foreach $config_info as $view => $tmp}
		{if $view < 1}
			{foreach $tmp as $key => $test}
	<li>{$key}:
		{if isset($test->value)}{$test->value}
		{/if}
	</li>
			{/foreach}
		{/if}
	{/foreach}
</ul>
<br />
{/if}


{if $count_php_information > 1}
<p><strong>{'php_information'|replace:'_':' '|adjust:'ucwords'}</strong>:</p>
<ul>
	{foreach $php_information as $view => $tmp}
		{if $view < 1}
			{foreach $tmp as $key => $test}
	<li>{$key}:
		{if isset($test->secondvalue) && $test->secondvalue !== ''}{$test->value} ({$test->secondvalue})
		{elseif isset($test->value)}{$test->value}
		{/if}
	</li>
			{/foreach}
		{/if}
	{/foreach}
</ul>
<br />
{/if}

{if count($performance_info)}
<p><strong>Performance Information:</strong></p>
<ul>
  {$list=$performance_info[0]}
  {foreach $list as $key => $test}
    <li>{$key}:
	{if isset($test->secondvalue) && $test->secondvalue !== ''}{$test->value} ({$test->secondvalue})
	{elseif isset($test->value)}{$test->value}
	{/if}
    </li>
  {/foreach}
</ul>
{/if}

{if $count_server_info > 1}
<p><strong>{'server_information'|replace:'_':' '|adjust:'ucwords'}</strong>:</p>
<ul>
	{foreach $server_info as $view => $tmp}
		{if $view < 1}
			{foreach $tmp as $key => $test}
	<li>{$key|replace:'_':' '|adjust:'ucwords'}:
		{if isset($test->value)}{$test->value}
		{/if}
	</li>
			{/foreach}
		{/if}
	{/foreach}
</ul>
<br />
{/if}
{if $count_permission_info > 1}
<p><strong>{'permission_information'|replace:'_':' '|adjust:'ucwords'}</strong>:</p>
<ul>
	{foreach $permission_info as $view => $tmp}
		{if $view < 1}
			{foreach $tmp as $key => $test}
	<li>{$key}:
		{if isset($test->secondvalue) && $test->secondvalue !== ''}{$test->value} ({$test->secondvalue})
		{elseif isset($test->value)}{$test->value}
		{/if}
	</li>
			{/foreach}
		{/if}
	{/foreach}
</ul>
{/if}

<p>----------------------------------------------</p>

</div>

{literal}
<script type="text/javascript">
	function fnSelect(objId) {
		fnDeSelect();
		if (document.selection) {
		var range = document.body.createTextRange();
		range.moveToElementText(document.getElementById(objId));
		range.select();
		}
		else if (window.getSelection) {
		var range = document.createRange();
		range.selectNode(document.getElementById(objId));
		window.getSelection().addRange(range);
		}
	}

	function fnDeSelect() {
		if (document.selection) document.selection.empty();
		else if (window.getSelection)
			window.getSelection().removeAllRanges();
	}
	fnSelect('copy_paste_in_forum');
</script>
{/literal}

</div>
</div>
