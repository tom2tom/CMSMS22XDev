<script>
$(function() {
{if !empty($aitems)}
  $('#bulkactions').hide();
  $('#bulk_category').hide();
  $('#selall').cmsms_checkall();
  $('a.delete_article').on('click', function(ev) {
    ev.preventDefault();
    var _url = $(this).attr('href');
    cms_confirm('{$mod->Lang('areyousure')|escape:'javascript'}').done(function() {
      window.location.href = _url;
    });
  });
  $('#articlelist').on('cms_checkall_toggle','[type="checkbox"]',function() {
    var l = $('#articlelist :checked').length;
    if (l === 0) {
      $('#bulkactions').hide(50);
    } else {
      $('#bulkactions').show(50);
    }
  });
  $('#selbulk').on('change',function() {
    var v = $(this).val();
    if (v === 'setcategory') {
      $('#bulk_category').show(50);
    } else {
      $('#bulk_category').hide(50);
    }
  });
  $('#bulkactions').on('click','#btnbulk',function(ev) {
    ev.preventDefault();
    var form = $(this).closest('form');
    cms_confirm('{$mod->Lang('areyousure_multiple')|escape:'javascript'}').done(function() {
      form.trigger('submit');
    });
  });
{/if}
  $('#toggle_filter').on('click', function(ev) {
    ev.preventDefault();
    $('#filter').dialog({
      width: 'auto',
      modal: true
    });
    $('#closefilter').on('click', function(ev) {
      ev.preventDefault();
      $('#filter').dialog('close');
    });
    return false;
  });
});
</script>
<div id="filter" title="{$filtertext}" style="display:none">
	<form method="post" action="moduleinterface.php">
	<div class="hidden">
		<input type="hidden" name="mact" value="News,m1_,defaultadmin,0">
		<input type="hidden" name="{$securename}" value="{$securekey}">
	</div>
	<div class="pageoverflow">
	<p class="pagetext"><label for="filter_category">{$prompt_category}:</label> {cms_help key='help_articles_filtercategory' title=$prompt_category}</p>
	<p class="pageinput">
		<input type="hidden" name="{$actionid}allcategories" value="0">
		<select id="filter_category" name="{$actionid}category">
		{html_options options=$categorylist selected=$curcategory}
		</select>
		<input id="filter_allcategories" type="checkbox" name="{$actionid}allcategories" style="vertical-align:middle" value="yes"{if $allcategories=="yes"} checked{/if}>
		<label for="filter_allcategories">{$prompt_showchildcategories}</label>
		{cms_help key='help_articles_filterchildcats' title=$prompt_showchildcategories}
	</p>
	</div>
	<div class="pageoverflow">
	<p class="pagetext"><label for="filter_sortby">{$prompt_sorting}:</label> {cms_help key='help_articles_sortby' title=$prompt_sorting}</p>
	<p class="pageinput">
		<select id="filter_sortby" name="{$actionid}sortby">
		{html_options options=$sortlist selected=$sortby}
		</select>
	</p>
	</div>
	<div class="pageoverflow">
	<p class="pagetext"><label for="filter_pagelimit">{$prompt_pagelimit}:</label> {cms_help key='help_articles_pagelimit' title=$prompt_pagelimit}</p>
	<p class="pageinput">
		<select id="filter_pagelimit" name="{$actionid}pagelimit">
		{html_options options=$pagelimits selected=$sortby}
		</select>
	</p>
	</div>
	<div class="pageoverflow">
		<div class="dialogoptions">
			<input type="submit" name="{$actionid}submitfilter" data-ui-icon="ui-icon-caret-1-n" value="{$mod->Lang('apply')}">
			<input type="submit" name="{$actionid}resetfilter" data-ui-icon="ui-icon-arrowrefresh-1-n" value="{$mod->Lang('reset')}">
			<input type="submit" id="closefilter" data-ui-icon="ui-icon-cancel" value="{$mod->Lang('cancel')}">
		</div>
	</div>
	{$endform}
</div>
<div class="row c_full">
	<div class="pageoptions grid_6" style="margin-top:8px">
	{if isset($addlink)}{$addlink}&nbsp;{/if}
	<a id="toggle_filter"{if $curcategory} style="font-weight:bold;color:green"{/if}>{admin_icon icon='view.gif' alt=$mod->Lang('viewfilter')}{if $curcategory} *{/if}
	{$mod->Lang('viewfilter')}</a>
	</div>
{if $aitemcount > 0 && $pagecount > 1}
	<div class="pageoptions grid_6 endalign">
		<form method="post" action="moduleinterface.php">
		<div class="hidden">
			<input type="hidden" name="mact" value="News,m1_,defaultadmin,0">
			<input type="hidden" name="{$securename}" value="{$securekey}">
		</div>
		<label for="pnum">{$mod->Lang('prompt_page')}</label>&nbsp;
		<select id="pnum" name="{$actionid}pagenumber">
		{cms_pageoptions numpages=$pagecount curpage=$pagenumber}
		</select>&nbsp;
		<input type="submit" name="{$actionid}paginate" data-ui-icon="ui-icon-triangle-2-e-w" value="{$mod->Lang('prompt_go')}">
		{$endform}
	</div>
{/if}
</div>{* .row *}

{if !empty($aitems)}
<form method="post" action="moduleinterface.php">
<div class="hidden">
	<input type="hidden" name="mact" value="News,m1_,defaultadmin,0">
	<input type="hidden" name="{$securename}" value="{$securekey}">
</div>
<table class="pagetable" id="articlelist">
	<thead>
		<tr>
			<th>#</th>
			<th>{$titletext}</th>
			<th>{$postdatetext}</th>
			<th>{$startdatetext}</th>
			<th>{$enddatetext}</th>
			<th>{$categorytext}</th>
			<th class="pageicon">{$statustext}</th>
			<th class="pageicon">&nbsp;</th>
			<th class="pageicon">&nbsp;</th>
			<th class="pageicon"><input type="checkbox" id="selall" value="1" title="{$mod->Lang('selectall')}"></th>
		</tr>
	</thead>
	<tbody>
	{foreach $aitems as $entry}
		<tr class="{$entry->rowclass}">
			<td>{$entry->id}</td>
			<td>
			{if isset($entry->edit_url)}
				<a href="{$entry->edit_url}" title="{$mod->Lang('editarticle')}">{$entry->news_title|cms_escape}</a>
			{else}
				{$entry->news_title|cms_escape}
			{/if}
			</td>
			<td>{$entry->u_postdate|cms_date_format}</td>
			<td>{if !empty($entry->u_enddate)}{$entry->u_startdate|cms_date_format}{/if}</td>
			<td>{if $entry->expired == 1}
				<div class="important">
					{$entry->u_enddate|cms_date_format}
				</div>
			{else}
				{$entry->u_enddate|cms_date_format}
			{/if}
			</td>
			<td>{$entry->category|cms_escape}</td>
			<td style="text-align:center">{if isset($entry->approve_link)}{$entry->approve_link}{/if}</td>
			<td>
			{if isset($entry->edit_url)}
			<a href="{$entry->edit_url}" title="{$mod->Lang('editarticle')}">{admin_icon icon='edit.gif' alt=$mod->Lang('edit')}</a>
			{/if}
			</td>
			<td>
			{if isset($entry->delete_url)}
			<a class="delete_article" href="{$entry->delete_url}" title="{$mod->Lang('delete_article')}">{admin_icon icon='delete.gif' alt=$mod->Lang('delete')}</a>
			{/if}
			</td>
			<td><input type="checkbox" name="{$actionid}sel[]" value="{$entry->id}" title="{$mod->Lang('toggle_bulk')}"></td>
		</tr>
	{/foreach}
	</tbody>
</table>
{else}
	<p class="warning">{if $curcategory}{$mod->Lang('noarticles')}{else}{$mod->Lang('noarticlesinfilter')}{/if}</p>
{/if}

<div style="width:99%">
{if isset($addlink) && $aitemcount > 8}
	<div class="pageoptions startside last">
		<p class="pageoptions">{$addlink}</p>
	</div>
{/if}
{if $aitemcount > 0}
	<div id="bulkactions" class="pageoptions endside last">
		<label for="selbulk">{$mod->Lang('with_selected')}:</label>
		<select id="selbulk" name="{$actionid}bulk_action">
{if isset($submit_massdelete)}
			<option value="delete">{$mod->Lang('bulk_delete')}</option>
{/if}
			<option value="setdraft">{$mod->Lang('bulk_setdraft')}</option>
			<option value="setpublished">{$mod->Lang('bulk_setpublished')}</option>
			<option value="setcategory">{$mod->Lang('bulk_setcategory')}</option>
		</select>
		<div id="bulk_category" style="display:inline-block">
			{$mod->Lang('category')}: {$categoryinput}
		</div>
		<input type="submit" id="btnbulk" name="{$actionid}submit_bulkaction" value="{$mod->Lang('submit')}">
	</div>
{/if}
{*	<div class="clearb"></div>*}
</div>
{$endform}
