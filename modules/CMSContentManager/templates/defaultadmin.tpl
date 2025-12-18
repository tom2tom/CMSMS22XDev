{if empty($ajax)}
<script>
function cms_CMloadUrl(link, lang) {
  $(document).on('click', link, function(e) {
    var url = $(this).attr('href') + '&showtemplate=false&{$actionid}ajax=1';

    var _do_ajax = function() {
      $.ajax({
        url: url,
      }).done(function() {
        $('#content_area').autoRefresh('refresh').done(function() {
          console.debug('after refresh');
        });
      });
    };

    e.preventDefault();
    $('#ajax_find').val('');

    if (typeof lang === 'string' && lang.length > 0) {
      cms_confirm(lang).done(_do_ajax);
    } else {
      _do_ajax();
    }
  });
}

function cms_CMtoggleState(el) {
  $(el).prop('disabled', true);
  $('input:checkbox').on('click', function() {
    var state = $('input:checkbox').is(':checked');
    $(el).prop('disabled', !state);
  });
}

$(function() {
  cms_busy();
  // dynamically populate the pages-list div
  $('#content_area').autoRefresh({
    url: '{$ajax_get_content}',
    done_handler: function() {
      cms_CMtoggleState('#multiaction');
      cms_CMtoggleState('#multisubmit');
      $('#selectall').cmsms_checkall({
       target: '#contenttable'
      });
      $('#ajax_find').autocomplete({
        source: '{cms_action_url action=admin_ajax_pagelookup forjs=1}&showtemplate=false',
        minLength: 2,
        position: {
          my: 'right top',
          at: 'right bottom'
        },
        change: function(event, ui) {
          // goes back to the full list, no options
          $('#ajax_find').val('');
          $('#content_area').autoRefresh('option', 'url', '{$ajax_get_content}');
        },
        select: function(event, ui) {
          event.preventDefault();
          $(this).val(ui.item.label);
          var url = '{cms_action_url action=ajax_get_content forjs=1}&showtemplate=false&{$actionid}seek=' + ui.item.value;
          $('#content_area').autoRefresh('option', 'url', url).autoRefresh('refresh').done(function() {
            $('html,body').animate({
              scrollTop: $('#row_' + ui.item.value).offset().top
            });
          });
        }
      })
      .on('keypress', function(e) {
        $('#content_area').autoRefresh('reset');
        if (e.which == 13) e.preventDefault();
      });
    } // done
  })
  .on('click', '.steal_lock', function(ev) {
    // confirm lock-steal
    ev.preventDefault();
    var _url = $(this).attr('href');
    cms_confirm('{$mod->Lang('confirm_steal_lock')|escape:'javascript'}').done(function() {
      window.location.href = _url;
    });
  })
  .on('click', '.page_edit', function(ev) {
    var v = $(this).data('steal_lock');
    $(this).removeData('steal_lock');
    if (typeof v !== 'undefined' && v != null && !v) return false;
    if (typeof v === 'undefined' || v != null) return true;
    // double-check whether this page is locked
    var content_id = $(this).attr('data-cms-content');
    var opts = {
      opt: 'check',
      type: 'content',
      oid: content_id
    };
    opts[cms_data.secure_param_name] = cms_data.user_key;
    $.ajax('{$admin_url}/ajax_lock.php?showtemplate=false', {
      url: url,
      data: opts,
      success: function(data, textStatus, jqXHR) {}
    }).done(function(data) {
      if (data.status === 'success') {
        if (data.locked) {
          // gotta display a message.
          ev.preventDefault();
          cms_alert('{$mod->Lang('error_contentlocked')|escape:'javascript'}');
        }
      } else {
        ev.preventDefault();
        cms_alert('{lang('error_internal')|escape:'javascript'}');
      }
    });
  })
  .on('change', '#filter_type', function() {
    var map = {
      'DESIGN_ID': '#filter_design',
      'TEMPLATE_ID': '#filter_template',
      'OWNER_UID': '#filter_owner',
      'EDITOR_UID': '#filter_editor'
    };
    var v = $(this).val();
    $('.filter_fld').hide();
    $(map[v]).show();
  })
  .on('click', '#myoptions', function() {
    $('#pagelistoptions').dialog({
      width: 'auto',
      minHeight: 225,
      resizable: true,
      buttons: [
       {
        text: '{lang('apply')|escape:'javascript'}',
        icon: 'ui-icon-caret-1-n',
        click: function() {
         $(this).dialog('close');
         $('#myoptions_form').trigger('submit');
        }
       },
       {
        text: '{lang('reset')|escape:'javascript'}',
        icon: 'ui-icon-arrowrefresh-1-n',
        click: function() {
         $(this).dialog('close');
         $('#settype').val(-1);
         $('#myoptions_form').trigger('submit');
        }
       },
       {
        text: '{lang('cancel')|escape:'javascript'}',
        icon: 'ui-icon-cancel',
        click: function() {
         $(this).dialog('close');
        }
       }
      ]
    });
  })
  .on('change', '#curpage', function() {
    $(this).closest('form').trigger('submit');
  })
  .on('click', '#selectall', function() {
    var state = $(this).is(':checked');
    $('#multiaction, #multisubmit').prop('disabled', !state);
  })
  .on('change', '#selectall,input.multicontent', function() {
    $('#content_area').autoRefresh('reset');
  });

  // these links can't use ajax as they affect pagination.
//cms_CMloadUrl('a.expandall');
//cms_CMloadUrl('a.collapseall');
//cms_CMloadUrl('a.page_collapse');
//cms_CMloadUrl('a.page_expand');

  cms_CMloadUrl('a.page_sortup');
  cms_CMloadUrl('a.page_sortdown');
  cms_CMloadUrl('a.page_setinactive', '{$mod->Lang('confirm_setinactive')|escape:'javascript'}');
  cms_CMloadUrl('a.page_setactive');
  cms_CMloadUrl('a.page_setdefault', '{$mod->Lang('confirm_setdefault')|escape:'javascript'}');
  cms_CMloadUrl('a.page_delete', '{$mod->Lang('confirm_delete_page')|escape:'javascript'}');

  $('#filter_type').trigger('change');

  // other events
/*  $('#selectall,input.multicontent').on('change', function() {
    $('#content_area').autoRefresh('reset');
  });
*/
  $(document).ajaxComplete(function() {
    $('#selectall').cmsms_checkall();
    $('tr.selected').css('background', 'yellow');
  });

  $('#clearlocks').on('click', function(ev) {
    ev.preventDefault();
    var _url = $(this).attr('href');
    cms_confirm('{$mod->Lang('confirm_clearlocks')|escape:'javascript'}').done(function() {
      window.location.href = _url;
    });
  });

  $('#ordercontent').on('click', function(e) {
    var have_locks = {$have_locks};
    if (!have_locks) {
      // check whether anything is locked
      var opts = {
        opt: 'check',
        type: 'content'
      };
      opts[cms_data.secure_param_name] = cms_data.user_key;
      $.ajax('{$admin_url}/ajax_lock.php?showtemplate=false', {
        async: false,
        data: opts
      }).done(function(data) {
        if (data.status != 'success') { return; }
        if (data.locked) { have_locks = 1; }
      });
    }
    if (have_locks) {
      e.preventDefault();
      cms_alert("{$mod->Lang('error_action_contentlocked')|escape:'javascript'}");
    }
  });
});
</script>

<div id="pagelistoptions" style="display:none" title="{$mod->Lang('title_userpageoptions')}">
	{form_start action='defaultadmin' id='myoptions_form'}
		<input type="hidden" id="settype" name="{$actionid}setoptions" value="1">
		<div style="display:table">
		<div style="display:table-row">
			<label for="page_limits" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_pagelimit')}:</label>
			<select id="page_limits" style="display:table-cell" name="{$actionid}pagelimit">
				{html_options options=$pagelimits selected=$pagelimit}
			</select>
		</div>
		{if $can_manage_content}
			{if $filter}{$type=$filter->type}{else}{$type=''}{/if}
			<div style="display:table-row">
				<label for="filter_type" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_filter_type')}:</label>
				<select id="filter_type" style="display:table-cell" name="{$actionid}filter_type">
					{html_options options=$options_list selected=$type}
				</select>
			</div>
			{if $filter}{$expr=$filter->expr}{else}{$expr=''}{/if}
			<div style="display:table-row" class="filter_fld" id="filter_design">
				<label for="designsel" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_design')}:</label>
				<select id="designsel" style="display:table-cell" name="{$actionid}filter_design">
					{html_options options=$design_list selected=$expr}
				</select>
			</div>
			<div style="display:table-row" class="filter_fld" id="filter_template">
				<label for="tplsel" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_template')}:</label>
				<select id="tplsel" style="display:table-cell" name="{$actionid}filter_template">
					{html_options options=$template_list selected=$expr}
				</select>
			</div>
			<div style="display:table-row" class="filter_fld" id="filter_owner">
				<label for="ownersel" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_owner')}:</label>
				<select id="ownersel" style="display:table-cell" name="{$actionid}filter_owner">
					{html_options options=$user_list selected=$expr}
				</select>
			</div>
			<div style="display:table-row" class="filter_fld" id="filter_editor">
				<label for="editorssel" class="endalign" style="display:table-cell;padding-{$ndside}:.5em">{$mod->Lang('prompt_editor')}:</label>
				<select id="editorssel" style="display:table-cell" name="{$actionid}filter_editor">
					{html_options options=$user_list selected=$expr}
				</select>
			</div>
		{/if}
		</div>
	{form_end}
</div>
<div class="clearb"></div>
{/if}{* not ajax *}
<div id="content_area"></div>
