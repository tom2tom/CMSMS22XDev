<script>
$(function() {
  cms_busy();
  // dynamically populate the templates-list div
  $('#template_area').autoRefresh({
    url: '{$ajax_templates_url}',
    data: {
      filter: '{$jsonfilter}'
    },
    done_handler: function() {
      $('#template_area').find('#tpl_bulk_sel,#tpl_bulk_submit').prop('disabled', true);
{*    $('#tpl_bulk_submit').button({'disabled':true});TODO extra .button needed?*}
      $('#template_area,#tpl_selall').cmsms_checkall();
    }
  })
  // delegated-event handlers
  .on('click', '#tpl_selall,.tpl_select', function() {
    // if any .tpl_select is checked, enable the bulk actions
    var l = $('.tpl_select:checked').length,
      st = l === 0;
    $('#tpl_bulk_sel').prop('disabled', st);
    $('#tpl_bulk_submit').prop('disabled', st);
{*  $('#tpl_bulk_submit').button({'disabled':st});TODO extra .button needed?*}
  })
  .on('click', '#tpl_bulk_submit', function() {
    var n = $('input:checkbox:checked.tpl_select').length;
    if (n === 0) {
      cms_alert('{$mod->Lang('error_nothingselected')|escape:'javascript'}');
      return false;
    }
  })
  .on('click', '.steal_tpl_lock', function(ev) {
    // confirm lock-steal
    ev.preventDefault();
    var _url = $(this).attr('href');
    cms_confirm('{$mod->Lang('confirm_steal_lock')|escape:'javascript'}').done(function() {
      window.location.href = _url;
    });
  })
  .on('click', '.edit_tpl', function(ev) {
    if ($(this).hasClass('steal_tpl_lock')) return true;
    // double-check whether this template is locked
    var tpl_id = $(this).attr('data-tpl-id');
    var opts = {
      opt: 'check',
      type: 'template',
      oid: tpl_id
    };
    opts[cms_data.secure_param_name] = cms_data.secure_param_value;
    $.ajax('{$admin_url}/ajax_lock.php?showtemplate=false', {
      data: opts
    }).done(function(data) {
      if (data.status == 'success') {
        if (data.locked) {
          ev.preventDefault();
          cms_alert('{$mod->Lang('error_contentlocked')|escape:'javascript'}');
        }
      } else {
        ev.preventDefault();
        cms_alert('{lang('error_internal')|escape:'javascript'}');
      }
    });
  })
{if $has_add_right && !empty($list_types)}{*select dialog-type in frontend why?*}
  .on('click', '#addtemplate', function() {
    $('#addtemplatedlg').dialog({
      width: 'auto',
      buttons: [
       {
        text: '{$mod->Lang('submit')|escape:'javascript'}',
        icon: 'ui-icon-check',
        click: function() {
         $(this).dialog('close');
         $('#addtemplate_form').trigger('submit');
        }
       },
       {
        text: '{$mod->Lang('cancel')|escape:'javascript'}',
        icon: 'ui-icon-cancel',
        click: function() {
         $(this).dialog('close');
        }
       }
      ]
    });
  })
{/if}
{if 1}{*TODO test*}
  .on('change', '#tpl_page', function() {
    $(this).closest('form').trigger('submit');
  })
{/if}
  .on('click', '#edittplfilter', function() {
    $('#filtertpldlg').dialog({
      width: 'auto',
      buttons: [
       {
        text: '{$mod->Lang('apply')|escape:'javascript'}',
        icon: 'ui-icon-caret-1-n',
        click: function() {
         $(this).dialog('close');
         $('#filtertpl_form').trigger('submit');
        }
       },
       {
        text: '{$mod->Lang('reset')|escape:'javascript'}',
        icon: 'ui-icon-arrowrefresh-1-n',
        click: function() {
         $(this).dialog('close');
         $('#submit_filter_tpl').val('-1');
         $('#filtertpl_form').trigger('submit');
        }
       },
       {
        text: '{$mod->Lang('cancel')|escape:'javascript'}',
        icon: 'ui-icon-cancel',
        click: function() {
         $(this).dialog('close');
        }
       }
      ]
    });
  });
});
</script>

<div id="filtertpldlg" style="display:none" title="{$mod->Lang('tpl_filter')|escape:'javascript'}">
  {form_start action='defaultadmin' id='filtertpl_form' __activetab='templates'}
    <input type="hidden" id="submit_filter_tpl" name="{$actionid}submit_filter_tpl" value="1">
    <div style="display:table">
    <div style="display:table-row">
      <label for="filter_tpl_options" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_options')}:</label>
      <select id="filter_tpl_options" style="display:table-cell" name="{$actionid}filter_tpl_options" title="{$mod->Lang('title_filter')}">
        {html_options options=$filter_tpl_options selected=$tpl_filter.tpl}
      </select>
    </div>
    <div style="display:table-row">
      <label for="filter_tpl_sortby" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_sortby')}:</label>
      <select id="filter_tpl_sortby" style="display:table-cell" name="{$actionid}filter_tpl_sortby" title="{$mod->Lang('title_sortby')}">
        <option value="name"{if $tpl_filter.sortby == 'name'} selected{/if}>{$mod->Lang('name')}</option>
        <option value="type"{if $tpl_filter.sortby == 'type'} selected{/if}>{$mod->Lang('type')}</option>
        <option value="created"{if $tpl_filter.sortby == 'created'} selected{/if}>{$mod->Lang('created')}</option>
        <option value="modified"{if $tpl_filter.sortby == 'modified'} selected{/if}>{$mod->Lang('modified')}</option>
      </select>
    </div>
    <div style="display:table-row">
      <label for="filter_tpl_sortorder" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_sortorder')}:</label>
      <select id="filter_tpl_sortorder" style="display:table-cell" name="{$actionid}filter_tpl_sortorder" title="{$mod->Lang('title_sortorder')}">
        <option value="asc"{if $tpl_filter.sortorder == 'asc'} selected{/if}>{$mod->Lang('asc')}</option>
        <option value="desc"{if $tpl_filter.sortorder == 'desc'} selected{/if}>{$mod->Lang('desc')}</option>
      </select>
    </div>
    <input type="hidden" name="{$actionid}filter_tpl_limit" value="">
{if count($tpl_filterpages) > 0}
    <div style="display:table-row">
      <label for="filter_tpl_limit" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_limit')}:</label>
      <select id="filter_tpl_limit" style="display:table-cell" name="{$actionid}filter_tpl_limit" title="{$mod->Lang('title_filterlimit')}">
        <option value="10"{if (isset($tpl_filter.limit) && $tpl_filter.limit == 10)} selected{/if}>10</option>
{if isset($tpl_filterpages.25)}        <option value="25"{if (isset($tpl_filter.limit) && $tpl_filter.limit == 25)} selected{/if}>25</option>{/if}
{if isset($tpl_filterpages.50)}        <option value="50"{if (isset($tpl_filter.limit) && $tpl_filter.limit == 50)} selected{/if}>50</option>{/if}
{if isset($tpl_filterpages.100)}        <option value="100"{if (isset($tpl_filter.limit) && $tpl_filter.limit == 100)} selected{/if}>100</option>{/if}
      </select>
    </div>
{/if}
    </div>
  {form_end}
</div>{*#filtertpldialog*}
{if $has_add_right && !empty($list_types)}{*select dialog-type in frontend why?*}
  <div id="addtemplatedlg" style="display:none" title="{$mod->Lang('create_template')}">
    {form_start action='defaultadmin' id='addtemplate_form'}
      <div class="pageoverflow">
        <input type="hidden" name="{$actionid}submit_create" value="1">
        <p class="pagetext"><label for="tpl_import_type">{$mod->Lang('tpl_type')}:</label></p>
        <select name="{$actionid}import_type" id="tpl_import_type" title="{$mod->Lang('title_tpl_import_type')}">
          {html_options options=$list_types}
        </select>
      </div>
    {form_end}
  </div>{*#addtemplatedlg*}
{/if}
<div id="template_area"></div>
