<script>
$(function() {
  // dynamically populate the templates area
  cms_busy();
  $('#template_area').autoRefresh({
    url: '{$ajax_templates_url}',
    data: {
      filter: '{$jsonfilter}'
    },
    done_handler: function() {
      $('#tpl_bulk_action,#tpl_bulk_submit').prop('disabled', true);
{*    $('#tpl_bulk_submit').button({ 'disabled': true });TODO extra .button needed?*}

      $('#tpl_selall,.tpl_select').on('click', function() {
        var l = $('.tpl_select:checked').length,
          st = l === 0;
        $('#tpl_bulk_action').prop('disabled', st);
        $('#tpl_bulk_submit').prop('disabled', st);
{*      $('#tpl_bulk_submit').button({ 'disabled': st });TODO extra .button needed?*}
      });

      $(document).on('click', 'a.steal_tpl_lock', function() {
        // we're gonna confirm stealing this lock.
        return confirm("{$mod->Lang('confirm_steal_lock')|escape:'javascript'}");
      });

      $(document).on('click', 'a.sedit_tpl', function(ev) {
        if ($(this).hasClass('steal_tpl_lock')) return true;

        // double check whether this page is locked.
        var tpl_id = $(this).attr('data-tpl-id');
        var url = '{$admin_url}/ajax_lock.php?showtemplate=false';
        var opts = {
          opt: 'check',
          type: 'template',
          oid: tpl_id
        };
        opts[cms_data.secure_param_name] = cms_data.user_key;
        $.ajax({
          url: url,
          data: opts,
        }).done(function(data) {
          if (data.status == 'success') {
            if (data.locked) {
              // gotta display a message.
              ev.preventDefault();
              cms_alert("{$mod->Lang('error_contentlocked')|escape:'javascript'}");
            }
          }
        });
      });
    }
  });

  $(document).on('click', '#tpl_bulk_submit', function() {
    var n = $('input:checkbox:checked.tpl_select').length;
    if (n == 0) {
      cms_alert("{$mod->Lang('error_nothingselected')|escape:'javascript'}");
      return false;
    }
  });

  $('#template_area').on('click', '#edittplfilter', function() {
    $('#filterdialog').dialog({
      width: 'auto',
      buttons: {
        "{$mod->Lang('submit')|escape:'javascript'}": function() {
          $(this).dialog('close');
          $('#filterdialog_form').trigger('submit');
        },
        "{$mod->Lang('reset')|escape:'javascript'}": function() {
          $(this).dialog('close');
          $('#submit_filter_tpl').val('-1');
          $('#filterdialog_form').trigger('submit');
        },
        "{$mod->Lang('cancel')|escape:'javascript'}": function() {
          $(this).dialog('close');
        }
      }
    });
  });
{if $has_add_right && !empty($list_types)}
  $(document).on('click', '#addtemplate', function() {
    $('#addtemplatedialog').dialog({
      width: 'auto',
      buttons: {
        "{$mod->Lang('submit')|escape:'javascript'}": function() {
          $(this).dialog('close');
          $('#addtemplate_form').trigger('submit');
        },
        "{$mod->Lang('cancel')|escape:'javascript'}": function() {
          $(this).dialog('close');
        }
      }
    });
  });
{/if}
});
</script>

<div id="filterdialog" style="display: none;" title="{$mod->Lang('tpl_filter')|escape:'javascript'}">
  {form_start action='defaultadmin' id='filterdialog_form' __activetab='templates'}
    <input type="hidden" id="submit_filter_tpl" name="{$actionid}submit_filter_tpl" value="1">
    <div class="c_full">
      <label for="filter_tpl" class="grid_3 text-right">{$mod->Lang('prompt_options')}:</label>
      <select id="filter_tpl" name="{$actionid}filter_tpl" title="{$mod->Lang('title_filter')}" class="grid_9">
        {html_options options=$filter_tpl_options selected=$tpl_filter.tpl}
      </select>
    </div>
    <div class="c_full">
      <label for="filter_sortby" class="grid_3 text-right">{$mod->Lang('prompt_sortby')}:</label>
      <select id="filter_sortby" name="{$actionid}filter_sortby" title="{$mod->Lang('title_sortby')}" class="grid_9">
        <option value="name"{if $tpl_filter.sortby == 'name'} selected="selected"{/if}>{$mod->Lang('name')}</option>
        <option value="type"{if $tpl_filter.sortby == 'type'} selected="selected"{/if}>{$mod->Lang('type')}</option>
        <option value="created"{if $tpl_filter.sortby == 'created'} selected="selected"{/if}>{$mod->Lang('created')}</option>
        <option value="modified"{if $tpl_filter.sortby == 'modified'} selected="selected"{/if}>{$mod->Lang('modified')}</option>
      </select>
    </div>
    <div class="c_full">
      <label for="filter_sortorder" class="grid_3 text-right">{$mod->Lang('prompt_sortorder')}:</label>
      <select id="filter_sortorder" name="{$actionid}filter_sortorder" title="{$mod->Lang('title_sortorder')}" class="grid_9">
        <option value="asc"{if $tpl_filter.sortorder == 'asc'} selected="selected"{/if}>{$mod->Lang('asc')}</option>
        <option value="desc"{if $tpl_filter.sortorder == 'desc'} selected="selected"{/if}>{$mod->Lang('desc')}</option>
      </select>
    </div>
    <div class="c_full">
      <label for="filter_limit" class="grid_3 text-right">{$mod->Lang('prompt_limit')}:</label>
      <select id="filter_limit" name="{$actionid}filter_limit_tpl" title="{$mod->Lang('title_filterlimit')}" class="grid_9">
        <option value="10"{if $tpl_filter.limit == 10} selected="selected"{/if}>10</option>
        <option value="25"{if $tpl_filter.limit == 25} selected="selected"{/if}>25</option>
        <option value="50"{if $tpl_filter.limit == 50} selected="selected"{/if}>50</option>
        <option value="100"{if $tpl_filter.limit == 100} selected="selected"{/if}>100</option>
      </select>
    </div>
  {form_end}
</div>{* #filterdialog *}
{if $has_add_right && !empty($list_types)}
  <div id="addtemplatedialog" style="display: none;" title="{$mod->Lang('create_template')}">
    {form_start id="addtemplate_form"}
      <div class="pageoverflow">
        <input type="hidden" name="{$actionid}submit_create" value="1">
        <p class="pagetext"><label for="tpl_import_type">{$mod->Lang('tpl_type')}:</label></p>
          <select name="{$actionid}import_type" id="tpl_import_type" title="{$mod->Lang('title_tpl_import_type')}">
            {html_options options=$list_types}
          </select>
       <p class="pageinput"></p>
      </div>
    {form_end}
  </div>{* #addtemplatedialog *}
{/if}
<div id="template_area"></div>
