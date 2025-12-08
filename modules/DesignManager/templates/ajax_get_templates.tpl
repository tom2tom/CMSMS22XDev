{form_start action='defaultadmin' __activetab='templates'}{strip}

<div class="row">
  <div class="pageoptions startalign last">
  {if $has_add_right && !empty($list_types)}
    <a id="addtemplate" accesskey="a" title="{$mod->Lang('create_template')}">{admin_icon icon='newobject.gif' alt=$mod->Lang('create_template')}&nbsp;{$mod->Lang('create_template')}</a>&nbsp;&nbsp;
  {/if}
{if !empty($templates) || !empty($tpl_filter[0])}
    <a id="edittplfilter" accesskey="f" title="{$mod->Lang('prompt_editfilter')}">{admin_icon icon='view.gif' alt=$mod->Lang('prompt_editfilter')}&nbsp;{$mod->Lang('filter')}</a>&nbsp;&nbsp;
  {if $have_tpl_locks}
    <a id="clearlocks" accesskey="l" title="{$mod->Lang('title_clearlocks')}" href="{cms_action_url action='admin_clearlocks' type='template'}">{admin_icon icon='run.gif' alt=''}&nbsp;{$mod->Lang('prompt_clearlocks')}</a>&nbsp;&nbsp;
  {elseif !empty($have_tpl_selflocks)}
    <a accesskey="l" title="{$mod->Lang('title_clearlocks2')}{if !empty($which_selflocks)} ({$which_selflocks}){/if}" href="{cms_action_url action='admin_clearlocks' type='template' self=1}">{admin_icon icon='run.gif' alt=''}&nbsp;{$mod->Lang('prompt_clearlocks2')}</a>&nbsp;&nbsp;
  {/if}
  {if !empty($tpl_filter[0])}
    <span id="filtermsg" title="{$mod->Lang('title_filterapplied')}">({$mod->Lang('filterapplied')})</span>
  {/if}
{/if}
  </div>

 {if !empty($tpl_nav) && $tpl_nav.numpages > 1}
  <div class="pageoptions endalign last">
    <label for="tpl_page">{$mod->Lang('prompt_page')}:</label>&nbsp;
    <select id="tpl_page" name="{$actionid}tpl_page">
      {cms_pageoptions numpages=$tpl_nav.numpages curpage=$tpl_nav.curpage}
    </select>
    &nbsp;<input type="submit" data-ui-icon="ui-icon-triangle-2-e-w" value="{$mod->Lang('go')}">
  </div>
 {/if}
</div>

{if !empty($templates)}
  <table class="pagetable">
    <thead>
      <tr>
        <th title="{$mod->Lang('title_tpl_id')}">{$mod->Lang('prompt_id')}</th>
        <th class="pageicon"></th>
        <th title="{$mod->Lang('title_tpl_name')}">{$mod->Lang('prompt_name')}</th>
        <th title="{$mod->Lang('title_tpl_type')}">{$mod->Lang('prompt_type')}</th>
        <th title="{$mod->Lang('title_tpl_design')}">{$mod->Lang('prompt_design')}</th>
        <th title="{$mod->Lang('title_tpl_filename')}">{$mod->Lang('prompt_filename')}</th>
        <th title="{$mod->Lang('title_tpl_dflt')}" class="pageicon">{$mod->Lang('prompt_dflt')}</th>{* dflt *}
        <th class="pageicon"></th>{* edit *}
{if $has_add_right}
        <th class="pageicon"></th>{* copy *}
{/if}
        <th class="pageicon"></th>{* delete *}
        <th class="pageicon"><input type="checkbox" value="1" id="tpl_selall" title="{$mod->Lang('prompt_select_all')}"></th>{* checkbox *}
      </tr>
    </thead>
    <tbody>
      {foreach $templates as $template}{strip}
        {cycle values="row1,row2" assign='rowclass'}
        {include file='module_file_tpl:DesignManager;admin_defaultadmin_tpltooltip.tpl' assign='tpl_tooltip'}
    <tr class="{$rowclass}">
      {cms_action_url action='admin_edit_template' tpl=$template->get_id() assign='edit_tpl'}
      {if $has_add_right}
        {cms_action_url action='admin_copy_template' tpl=$template->get_id() assign='copy_tpl'}
      {/if}
      {cms_action_url action='admin_delete_template' tpl=$template->get_id() assign='delete_tpl'}

      {* template id, icon and name columns *}
      {$type_id=$template->get_type_id()|default:0}
    {if !$template->locked($userid)}
      <td><a href="{$edit_tpl}" data-tpl-id="{$template->get_id()}" class="edit_tpl tooltip" title="{$mod->Lang('edit_template')}" data-cms-description='{$tpl_tooltip}'>{$template->get_id()}</a></td>
      <td></td>
      <td><a href="{$edit_tpl}" data-tpl-id="{$type_id}" class="edit_tpl tooltip" title="{$mod->Lang('edit_template')}" data-cms-description='{$tpl_tooltip}'>{$template->get_name()}</a></td>
    {else}
      <td>{$template->get_id()}</td>
      <td>{admin_icon icon='warning.gif' title=$mod->Lang('title_locked')}</td>
      <td><span class="tooltip" data-cms-description='{$tpl_tooltip}'>{$template->get_name()}</span></td>
    {/if}
      {* template type column *}
      <td>
        {include file='module_file_tpl:DesignManager;admin_defaultadmin_tpltype_tooltip.tpl' assign='tpltype_tooltip'}
        {if !empty($list_types)}<span class="tooltip" data-cms-description='{$tpltype_tooltip}'>{$list_types.$type_id}</span>{/if}
      </td>
      {* design column *}
      <td>{$t1=$template->get_designs()}
      {if count($t1) == 1}
          {$t1=$t1[0]}
       {if $manage_designs}
        {cms_action_url action=admin_edit_design design=$t1 assign='edit_design_url'}
        <a href="{$edit_design_url}" title="{$mod->Lang('edit_design')}">{$design_names.$t1}</a>
       {else}
        {$design_names.$t1}
       {/if}
      {elseif count($t1) == 0}
        <span title="{$mod->Lang('help_template_no_designs')}">{$mod->Lang('prompt_none')}</span>
      {else}
        <span title="{$mod->Lang('help_template_multiple_designs')}">{$mod->Lang('prompt_multiple')} ({count($t1)})</span>
      {/if}
      </td>
      {* filename column *}
      <td>
      {if $template->has_content_file()}
        {basename($template->get_content_filename())}
      {/if}
      </td>
      {* default column *}
      <td>
        {if !empty($list_all_types.$type_id)}{$the_type=$list_all_types.$type_id}
        {if $the_type->get_dflt_flag()}
          {if $template->get_type_dflt()}
        {admin_icon icon='true.gif' title=$mod->Lang('prompt_dflt_tpl')}
          {else}
        {admin_icon icon='false.gif' title=$mod->Lang('prompt_notdflt_tpl')}
          {/if}
        {else}
          <span title="{$mod->Lang('prompt_title_na')}">{$mod->Lang('prompt_na')}</span>
        {/if}{/if}
      </td>
      {* edit/copy icons, or steal icons *}
    {if !$lock_timeout || !$template->locked($userid)}
      <td><a href="{$edit_tpl}" data-tpl-id="{$template->get_id()}" class="edit_tpl" title="{$mod->Lang('edit_template')}">{admin_icon icon='edit.gif' title=$mod->Lang('prompt_edit')}</a></td>
     {if $has_add_right}
      <td><a href="{$copy_tpl}" title="{$mod->Lang('copy_template')}">{admin_icon icon='copy.gif' title=$mod->Lang('prompt_copy_template')}</a></td>
     {/if}
    {else}
      <td>
     {$lock=$template->get_lock()}{if $lock && $lock.expires < $smarty.now}
        <a href="{$edit_tpl}&{$actionid}steal_lock={$lock.id}" data-tpl-id="{$template->get_id()}" accesskey="e" class="steal_tpl_lock"><img src="{$iconsteal_url}" class="edit_tpl steal_tpl_lock" title="{$mod->Lang('prompt_steal_lock')}"></a>
     {/if}
      </td>
      {if $has_add_right}<td></td>{/if}
    {/if}
      {* delete column *}
      <td>
       {if !($template->locked($userid) || $template->get_type_dflt())}
        {if ($manage_templates || $template->get_owner_id() == $userid)}
        <a href="{$delete_tpl}" title="{$mod->Lang('delete_template')}">{admin_icon icon='delete.gif' title=$mod->Lang('delete_template')}</a>
        {/if}
       {/if}
      </td>
      {* checkbox column *}
      <td>
        {if (!$template->locked($userid) && ($manage_templates || $template->get_owner_id() == $userid))}{*TODO hidden label c.f. templates list*}
        <input type="checkbox" class="tpl_select" name="{$actionid}tpl_select[]" value="{$template->get_id()}" title="{$mod->Lang('title_tpl_bulk')}">
        {/if}
      </td>
    </tr>
      {/strip}{/foreach}
    </tbody>
  </table>

  <div class="row">
    <div class="pageoptions endalign">
      <label for="tpl_bulk_sel">{$mod->Lang('prompt_with_selected')}:</label> {cms_help key2='help_bulk_templates' title=$mod->Lang('prompt_delete')}
      <select name="{$actionid}tpl_bulk_action" id="tpl_bulk_sel" class="tpl_bulk_action" title="{$mod->Lang('title_tpl_bulkaction')}">
        <option value="delete">{$mod->Lang('prompt_delete')}</option>
        <option value="export">{$mod->Lang('export')}</option>
        <option value="import">{$mod->Lang('import')}</option>
      </select>
      <input id="tpl_bulk_submit" class="tpl_bulk_action" type="submit" name="{$actionid}submit_bulk_tpl" value="{$mod->Lang('submit')}">
    </div>
  </div>
{else}
  <p class="information">{$mod->Lang('warning_no_templates_available')}</p>
{/if}

{/strip}{form_end}
