<script>
$(function() {
{if !empty($aitems)}
  $('#selbulk,#selcategory,#btnbulk').prop('disabled', true);
  $('#selall').cmsms_checkall();
  $('a.delete_article').on('click', function(ev) {
    ev.preventDefault();
    var _url = $(this).attr('href');
    cms_confirm('{$mod->Lang('areyousure')|escape:'javascript'}').done(function() {
      window.location.href = _url;
    });
  });
  $('#articlelist').on('cms_checkall_toggle','[type="checkbox"]', function() {
    var l = $('#articlelist :checked').length;
    if (l === 0) {
      $('#selbulk,#selcategory,#btnbulk').prop('disabled', true);
    } else {
      $('#selbulk,#btnbulk').prop('disabled', false);
      if ($('#selbulk').val() == 'setcategory') {
        $('#selcategory').prop('disabled', false);
      }
    }
  });
  $('#selpnum').on('change',function(ev) {
    ev.preventDefault();
    $(this).closest('form').trigger('submit');
  });
  $('#selbulk').on('change', function() {
    var v = $(this).val(),
     st = (v !== 'setcategory');
     $('#selcategory').prop('disabled', st);
  });
  $('#btnbulk').on('click', function(ev) {
    ev.preventDefault();
    var l = $('#articlelist :checked').length; // double-check
    if (l > 0) {
      var form = $(this).closest('form');
      cms_confirm('{$mod->Lang('areyousure_multiple')|escape:'javascript'}').done(function() {
        form.trigger('submit');
      });
    }
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
<div id="filter" style="display:none" title ="{$mod->Lang('prompt_settings')}">
	<form method="post" action="moduleinterface.php">
	<div class="hidden">
		<input type="hidden" name="mact" value="News,m1_,defaultadmin,0">
		<input type="hidden" name="{$securename}" value="{$secureval}">
	</div>
	<div class="pageoverflow">
	<p class="pagetext"><label for="filter_category">{$prompt_category}:</label> {cms_help key='help_articles_filtercategory' title=$prompt_category}</p>
	<p class="pageinput">
		<input type="hidden" name="{$actionid}allcategories" value="0">
		<select id="filter_category" name="{$actionid}category">
		{html_options options=$categorylist selected=$curcategory}
		</select>
		<input id="filter_allcategories" type="checkbox" name="{$actionid}allcategories" style="vertical-align:middle" value="1"{if $allcategories} checked{/if}>
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
<div class="row">
	<div class="pageoptions startalign last"{if $aitemcount > 0 && $pagecount > 1} style="float:{$stside}"{/if}>
	{if isset($addlink)}{$addlink}&nbsp;{/if}
	<a id="toggle_filter" title="{$mod->Lang('title_editsettings')}">
	{admin_icon icon='edit.gif' alt=$mod->Lang('title_editsettings')} {lang('settings')}
	</a>
	{if $have_filter}&nbsp;<span id="filtermsg" title="{$mod->Lang('title_filterapplied')}">({$mod->Lang('filterapplied')})</span>{/if}
	</div>
{if $aitemcount > 0 && $pagecount > 1}
	<div class="pageoptions endalign last" style="float:{$ndside};margin-top:-0.5em">
		<form method="post" action="moduleinterface.php">
		<div class="hidden">
			<input type="hidden" name="mact" value="News,m1_,defaultadmin,0">
			<input type="hidden" name="{$securename}" value="{$secureval}">
		</div>
		<label for="selpnum">{$mod->Lang('prompt_page')}</label>&nbsp;
		<select id="selpnum" name="{$actionid}pagenumber">
			{cms_pageoptions numpages=$pagecount curpage=$pagenumber}
		</select>
{*		<button class="ui-button ui-corner-all">
			<span class="ui-button-icon-primary ui-icon ui-icon-arrowthick-1-{if $stside=='left'}w{else}e{/if}"></span>
			<span class="ui-button-text">{$mod->Lang('prompt_go')}</span>
		</button>*}
		</form>
	</div>
{/if}
</div>{* .row *}

{if !empty($aitems)}
<form method="post" action="moduleinterface.php">
<div class="hidden">
	<input type="hidden" name="mact" value="News,m1_,defaultadmin,0">
	<input type="hidden" name="{$securename}" value="{$secureval}">
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
			<th style="text-align:center">{$statustext}</th>
			<th class="pageicon">&nbsp;</th>
{if $can_delete}			<th class="pageicon">&nbsp;</th>{/if}
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
{if $can_delete}
			<td>
			<a class="delete_article" href="{$entry->delete_url}" title="{$mod->Lang('delete_article')}">{admin_icon icon='delete.gif' alt=$mod->Lang('delete')}</a>
			</td>
{/if}
			<td><input type="checkbox" name="{$actionid}sel[]" value="{$entry->id}" title="{$mod->Lang('toggle_bulk')}"></td>
		</tr>
	{/foreach}
	</tbody>
</table>
{else}
	<p class="information">{if !$have_filter}{$mod->Lang('noarticles')}{else}{$mod->Lang('noarticlesinfilter')}{/if}</p>
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
{if $can_delete}
			<option value="delete">{$mod->Lang('bulk_delete')}</option>
{/if}
			<option value="setdraft">{$mod->Lang('bulk_setdraft')}</option>
			<option value="setpublished">{$mod->Lang('bulk_setpublished')}</option>
			<option value="setcategory">{$mod->Lang('bulk_setcategory')}</option>
		</select>
{if !empty($categoryinput)}
		<div id="bulk_category" style="display:inline-block">
			{$mod->Lang('category')}: {$categoryinput}
		</div>
{/if}
		<button id="btnbulk" class="ui-button ui-corner-all" name="{$actionid}submit_bulkaction">
			<span class="ui-button-icon-primary ui-icon ui-icon-gear"></span>
			<span class="ui-button-text">{$mod->Lang('submit')}</span>
		</button>
	</div>
{/if}
{*	<div class="clearb"></div>*}
</div>
{$endform}
