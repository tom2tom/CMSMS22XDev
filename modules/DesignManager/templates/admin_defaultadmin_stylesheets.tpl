<script>
$(function() {
  cms_busy();
  // dynamically populate the stylesheets-list div
  $('#stylesheet_area').autoRefresh({
    url: '{$ajax_stylesheets_url}',
    data: {
      filter: '{$jsoncssfilter}'
    },
    done_handler: function() {
      $('#stylesheet_area').find('#css_bulk_sel,#css_bulk_submit').prop('disabled', true);
{*    $('#css_bulk_submit').button({'disabled':true});TODO extra .button needed?*}
      $('#stylesheet_area,#css_selall').cmsms_checkall();
    }
  })
  // delegated-event handlers
  .on('click', '#css_selall,.css_select', function() {
    // if any .css_select is checked, enable the bulk actions
    var l = $('.css_select:checked').length,
      st = l === 0;
    $('#css_bulk_sel').prop('disabled', st);
    $('#css_bulk_submit').prop('disabled', st);
{*  $('#css_bulk_submit').button({'disabled':st});TODO extra .button needed?*}
  })
  .on('click', '#css_bulk_submit', function() {
    var n = $('input:checkbox:checked.css_select').length;
    if (n === 0) {
      cms_alert('{$mod->Lang('error_nothingselected')|escape:'javascript'}');
      return false;
    }
  })
  .on('click', '.steal_css_lock', function(ev) {
    // confirm lock-steal
    ev.preventDefault();
    var _url = $(this).attr('href');
    cms_confirm('{$mod->Lang('confirm_steal_lock')|escape:'javascript'}').done(function() {
      window.location.href = _url;
    });
  })
  .on('click', '.edit_css', function(ev) {
    if ($(this).hasClass('steal_css_lock')) return true;
    // double-check whether this sheet is locked
    var css_id = $(this).attr('data-css-id');
    var opts = {
      opt: 'check',
      type: 'stylesheet',
      oid: css_id
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
{if 1}{*TODO*page check*}
  .on('change', '#css_page', function() {
    $(this).closest('form').trigger('submit');
  })
{/if}
  .on('click', '#editcssfilter', function() {
    $('#filtercssdlg').dialog({
      width: 'auto',
      buttons: [
       {
        text: '{$mod->Lang('apply')|escape:'javascript'}',
        icon: 'ui-icon-caret-1-n',
        click: function() {
         $(this).dialog('close');
         $('#filtercss_form').trigger('submit');
        }
       },
       {
        text: '{$mod->Lang('reset')|escape:'javascript'}',
        icon: 'ui-icon-arrowrefresh-1-n',
        click: function() {
         $(this).dialog('close');
         $('#submit_filter_css').val('-1');
         $('#filtercss_form').trigger('submit');
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

<div id="filtercssdlg" style="display:none" title="{$mod->Lang('css_filter')}">
  {form_start action='defaultadmin' id='filtercss_form' __activetab='stylesheets'}
    <input type="hidden" id="submit_filter_css" name="{$actionid}submit_filter_css" value="1">
    <div style="display:table">
    <div style="display:table-row">
      <label for="filter_css_design" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_design')}:</label>
      <select id="filter_css_design" style="display:table-cell" name="{$actionid}filter_css_design" title="{$mod->Lang('title_filter_design')}">
        <option value="">{$mod->Lang('any')}</option>
        {html_options options=$design_names selected=$css_filter.design}
      </select>
    </div>
    <div style="display:table-row">
      <label for="filter_css_sortby" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_sortby')}:</label>
      <select id="filter_css_sortby" style="display:table-cell" name="{$actionid}filter_css_sortby" title="{$mod->Lang('title_sortby')}">
        <option value="name"{if $css_filter.sortby == 'name'} selected{/if}>{$mod->Lang('name')}</option>
        <option value="media"{if $css_filter.sortby == 'media'} selected{/if}>{$mod->Lang('media')}</option>
        <option value="created"{if $css_filter.sortby == 'created'} selected{/if}>{$mod->Lang('created')}</option>
        <option value="modified"{if $css_filter.sortby == 'modified'} selected{/if}>{$mod->Lang('modified')}</option>
      </select>
    </div>
    <div style="display:table-row">
      <label for="filter_css_sortorder" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_sortorder')}:</label>
      <select id="filter_css_sortorder" style="display:table-cell" name="{$actionid}filter_css_sortorder" title="{$mod->Lang('title_sortorder')}">
        <option value="asc"{if $css_filter.sortorder == 'asc'} selected{/if}>{$mod->Lang('asc')}</option>
        <option value="desc"{if $css_filter.sortorder == 'desc'} selected{/if}>{$mod->Lang('desc')}</option>
      </select>
    </div>
    <input type="hidden" name="{$actionid}filter_css_limit" value="">
{if count($css_filterpages) > 0}
    <div style="display:table-row">
      <label for="filter_css_limit" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_limit')}:</label>
      <select id="filter_css_limit" style="display:table-cell" name="{$actionid}filter_css_limit" title="{$mod->Lang('title_filterlimit')}">
        <option value="10"{if (isset($css_filter.limit) && $css_filter.limit == 10)} selected{/if}>10</option>
{if isset($css_filterpages.25)}        <option value="25"{if (isset($css_filter.limit) && $css_filter.limit == 25)} selected{/if}>25</option>{/if}
{if isset($css_filterpages.50)}        <option value="50"{if (isset($css_filter.limit) && $css_filter.limit == 50)} selected{/if}>50</option>{/if}
{if isset($css_filterpages.100)}        <option value="100"{if (isset($css_filter.limit) && $css_filter.limit == 100)} selected{/if}>100</option>{/if}
      </select>
    </div>
{/if}
    </div>
  {form_end}
</div>{*#filtercssdlg*}
<div id="stylesheet_area"></div>
