<div class="row">
  <div class="pageoptions startalign last"{if !empty($css_nav) && $css_nav.numpages > 1} style="float:{$stside}"{/if}>
    <a id="addcss" accesskey="a" href="{cms_action_url action='admin_edit_css'}" title="{$mod->Lang('create_stylesheet')}">{admin_icon icon='newobject.gif'} {$mod->Lang('create_stylesheet')}</a>&nbsp;&nbsp;
{if !empty($stylesheets)}
 {if $have_css_locks}
    <a id="cssclearlocks" accesskey="l" title="{$mod->Lang('title_clearlocks')}" href="{cms_action_url action='admin_clearlocks' type='stylesheet'}">{admin_icon icon='run.gif' alt=''}&nbsp;{$mod->Lang('prompt_clearlocks')}</a>&nbsp;&nbsp;
 {elseif !empty($have_css_selflocks)}
    <a accesskey="l" title="{$mod->Lang('title_clearlocks2')}{if !empty($which_selflocks)} ({$which_selflocks}){/if}" href="{cms_action_url action='admin_clearlocks' type='stylesheet' self=1}">{admin_icon icon='run.gif' alt=''}&nbsp;{$mod->Lang('prompt_clearlocks2')}</a>&nbsp;&nbsp;
 {/if}
{/if}
{if !empty($stylesheets) || ($css_filter && $css_filter.design)}
    <a id="editcssfilter" accesskey="f" title="{$mod->Lang('title_editsettings')}">{admin_icon icon='edit.gif' alt=$mod->Lang('title_editsettings')} {lang('settings')}</a>&nbsp;&nbsp;
 {if $css_filter && $css_filter.design}
    <span id="filtermsg" title="{$mod->Lang('title_filterapplied')}">({$mod->Lang('filterapplied')})</span>
 {/if}
{/if}
  </div>
{if !empty($css_nav) && $css_nav.numpages > 1}
  <div class="pageoptions endalign last" style="float:{$ndside};margin-top:-0.5em">
    {form_start action='defaultadmin' __activetab='stylesheets'}
      <label for="css_page">{$mod->Lang('prompt_page')}:</label>&nbsp;
      <select id="css_page" name="{$actionid}css_page" style="margin-top:4px">
        {cms_pageoptions numpages=$css_nav.numpages curpage=$css_nav.curpage}
      </select>
{*      <button class="ui-button ui-corner-all">
        <span class="ui-button-icon-primary ui-icon ui-icon-arrowthick-1-{if $stside=='left'}w{else}e{/if}"></span>
        <span class="ui-button-text">{$mod->Lang('go')}</span>
      </button>*}
    {form_end}
  </div>
{/if}
</div>
{if !empty($stylesheets)}
  {strip}
  {form_start action='admin_bulk_css'}
  <table class="pagetable">
    <thead>
      <tr>
        <th title="{$mod->Lang('title_css_id')}">{$mod->Lang('prompt_id')}</th>
        <th class="pageicon"></th>
        <th title="{$mod->Lang('title_css_name')}">{$mod->Lang('prompt_name')}</th>
        <th title="{$mod->Lang('title_css_media')}">{$mod->Lang('prompt_media')}</th>
        <th title="{$mod->Lang('title_css_designs')}">{$mod->Lang('prompt_design')}</th>
        <th title="{$mod->Lang('title_css_filename')}">{$mod->Lang('prompt_filename')}</th>
        <th class="pageicon"></th>{* edit *}
        <th class="pageicon"></th>{* copy TODO if item addition permitted *}
        <th class="pageicon"></th>{* delete *}
        <th class="pageicon"><label for="css_selall" style="display:none">{$mod->Lang('title_css_selectall')}</label><input type="checkbox" value="1" id="css_selall" title="{$mod->Lang('title_css_selectall')}"></th>{* multiple *}
      </tr>
    </thead>
    <tbody>
      {foreach $stylesheets as $css}
        {cycle values="row1,row2" assign='rowclass'}
        {include file='module_file_tpl:DesignManager;admin_defaultadmin_csstooltip.tpl' assign='css_tooltip'}
        {cms_action_url action='admin_edit_css' css=$css->get_id() assign='edit_css'}
        {cms_action_url action='admin_copy_css' css=$css->get_id() assign='copy_css'}
        {cms_action_url action='admin_delete_css' css=$css->get_id() assign='delete_css'}
      <tr class="{$rowclass}">
    {if !$css->locked($userid)}
        <td><a href="{$edit_css}" data-css-id="{$css->get_id()}" class="edit_css tooltip" title="{$mod->Lang('edit_stylesheet')}" data-cms-description='{$css_tooltip}'>{$css->get_id()}</a></td>
        <td></td>
        <td><a href="{$edit_css}" data-css-id="{$css->get_id()}" class="edit_css tooltip" title="{$mod->Lang('edit_stylesheet')}" data-cms-description='{$css_tooltip}'>{$css->get_name()}</a></td>
    {else}
        <td>{$css->get_id()}</td>
        <td>{admin_icon icon='warning.gif' title=$mod->Lang('title_locked')}</td>
        <td><span class="tooltip" data-cms-description='{$css_tooltip}'>{$css->get_name()}</span></td>
    {/if}
        <td>{$css->get_media()}</td>
        <td>{$t1=$css->get_designs()}
      {if $t1 && count($t1) == 1}
        {$t1=$t1[0]}
        {$hn=$design_names.$t1}
{*      {if $manage_designs}
          {cms_action_url action=admin_edit_design design=$t1 assign='edit_design_url'}
          <a href="{$edit_design_url}" title="{$mod->Lang('edit_design')}">{$hn}</a>
        {else}
*}
          {$hn}
{*        {/if*}
      {elseif empty($t1)}
        <span title="{$mod->Lang('help_stylesheet_no_designs')}">{$mod->Lang('prompt_none')}</span>
      {else}
        {capture assign='tooltip_designs'}{strip}
            {$mod->Lang('prompt_attached_designs')}:<br>
        {foreach $t1 as $dsn_id}
          {$design_names.$dsn_id}<br>
        {/foreach}
        {/strip}{/capture}
        <a class="tooltip text-red" data-cms-description="{$tooltip_designs|adjust:'htmlentities'}" title="{$mod->Lang('help_stylesheet_multiple_designs')}">{$mod->Lang('prompt_multiple')} ({count($t1)})
      {/if}
        </td>
        <td>
     {if $css->has_content_file()}
       {basename($css->get_content_filename())}
     {/if}
        </td>
  {if !$lock_timeout || !$css->locked($userid)}
        <td><a href="{$edit_css}" data-css-id="{$css->get_id()}" class="edit_css" title="{$mod->Lang('edit_stylesheet')}">{admin_icon icon='edit.gif' title=$mod->Lang('edit_stylesheet')}</a></td>
        <td><a href="{$copy_css}" title="{$mod->Lang('copy_stylesheet')}">{admin_icon icon='copy.gif' title=$mod->Lang('copy_stylesheet')}</a></td>{*TODO if addition permitted*}
        <td><a href="{$delete_css}" title="{$mod->Lang('delete_stylesheet')}">{admin_icon icon='delete.gif' title=$mod->Lang('delete_stylesheet')}</a></td>
        <td>
          <label for="chkcss{$css@index}" style="display:none">{$mod->Lang('prompt_select')}:</label>
          <input id="chkcss{$css@index}" type="checkbox" class="css_select" name="{$actionid}css_select[]" value="{$css->get_id()}">
        </td>
  {else}
        <td>
   {$lock=$css->get_lock()}{if $lock && $lock.expires < $smarty.now}
       <a href="{$edit_css}&{$actionid}steal_lock={$lock.id}" data-css-id="{$css->get_id()}" accesskey="e" class="steal_css_lock"><img src="{$iconsteal_url}" class="edit_css steal_css_lock" title="{$mod->Lang('prompt_steal_lock')}"></a>
   {/if}
        </td>
        <td></td>
        <td></td>
        <td></td>
  {/if}
      </tr>
      {/foreach}
    </tbody>
  </table>
  {/strip}

  <div class="clearb"></div>
  <div class="row">
    <div id="bulkoptions" class="pageoptions endalign">
      <label for="css_bulk_sel">{$mod->Lang('prompt_with_selected')}:</label> {cms_help key2='help_css_bulk' title=$mod->Lang('prompt_delete')}
      <select name="{$actionid}css_bulk_action" id="css_bulk_sel" class="css_bulk_action" title="{$mod->Lang('title_css_bulkaction')}">
        <option value="delete" title="{$mod->Lang('title_delete')}">{$mod->Lang('prompt_delete')}</option>
        <option value="export">{$mod->Lang('export')}</option>
        <option value="import">{$mod->Lang('import')}</option>
      </select>
      <button id="css_bulk_submit" class="ui-button ui-corner-all css_bulk_action" name="{$actionid}submit_bulk_css">
        <span class="ui-button-icon ui-icon ui-icon-gear"></span>
        <span class="ui-button-text">{$mod->Lang('submit')}</span>
      </button>
    </div>
  </div>

  {form_end}
{else}
<p class="information">{$mod->Lang('warning_no_stylesheets')}</p>
{/if}
