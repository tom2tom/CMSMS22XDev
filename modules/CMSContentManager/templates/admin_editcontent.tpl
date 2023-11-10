<script>
$(function() {
  var do_locking = {if isset($content_id) && $content_id > 0 && isset($lock_timeout) && $lock_timeout > 0}1{else}0{/if};

  // initialize the dirtyform stuff.
  $('#Edit_Content').dirtyForm({
    beforeUnload: function(is_dirty) {
      if( do_locking ) $('#Edit_Content').lockManager('unlock').done(function() {
        console.log('after dirtyform unlock');
      });
    },
    unloadCancel: function() {
      if( do_locking ) $('#Edit_Content').lockManager('relock');
    }
  });

  // initialize lock manager
  if( do_locking ) {
    $('#Edit_Content').lockManager({
      type: 'content',
      oid: {$content_id|default:-1},
      uid: {$userid},
      lock_timeout: {$lock_timeout|default:0},
      lock_refresh: {$lock_refresh|default:0},
      error_handler: function(err) {
        cms_alert('Locking error: ' + err.type + ' -- ' + err.msg);
      },
      lostlock_handler: function(err) {
        // we lost the lock on this content... make sure we can't save anything.
        // and display a nice message.
        $('[name$="cancel"]').fadeOut().val('{$mod->Lang("close")}').fadeIn();
        $('#Edit_Content').dirtyForm('option', 'dirty', false);
        cms_alert('{$mod->Lang("msg_lostlock")|escape:"javascript"}');
      }
    });
  }

{if $content_obj->HasPreview()}
  $('#_preview_').on('click', function() {
    if( typeof tinymce !== 'undefined') tinymce.triggerSave(); // TODO create a "save editor content" API that can be generally used
    // serialize the form data
    var data = $('#Edit_Content').find('input:not([type="submit"]), select, textarea').serializeArray();
    data.push({
      'name': '{$actionid}preview',
      'value': 1
    },
    {
      'name': '{$actionid}ajax',
      'value': 1
    });
    $.post('{$preview_ajax_url}&showtemplate=false', data, function(resultdata, text) {
      if( typeof resultdata !== 'undefined' && resultdata && resultdata.response == 'Error' ) {
        $('#previewframe').attr('src','about:blank').hide();
        //$('#preview_errors').html('<ul></ul>');
        for( var i = 0; i < resultdata.details.length; i++ ) {
          $('#preview_errors').append('<li>'+resultdata.details[i]+'</li>');
        }
        $('#previewerror').show();
      }
      else {
        var x = new Date().getTime();
        var url = '{$preview_url}&junk='+x;
        $('#previewerror').hide();
          $('#previewframe').attr('src', url).show();
        }
    },'json');
  });
{/if}

  // submit the form if disable wysiwyg, template id, and/or content-type fields are changed.
  $('#id_disablewysiwyg, #template_id, #content_type').on('change', function() {
    // disable the dirty form stuff, and unlock because we're gonna relockit on reload.
    var self = this;
    var this_id = $(this).attr('id');
    $('#Edit_Content').dirtyForm('disable');
    if( this_id != 'content_type') $('#active_tab').val('{$options_tab_name}');
    if( do_locking ) {
      if( do_locking) $('#Edit_Content').lockManager('unlock',1).done(function() {
        $(self).closest('form').trigger('submit');
      });
    } else {
      $(self).closest('form').trigger('submit');
    }
  });

  // handle cancel/close ... and unlock
  $(document).on('click', '[name$="cancel"]', function(ev) {
    // turn off all required elements, we're cancelling
    $('#Edit_Content :hidden').removeAttr('required');
    // do not touch the dirty flag, so that theunload handler stuff can warn us.
    if( do_locking ) {
      // unlock the item, and submit the form.
      var self = this;
      var form = $(this).closest('form');
      ev.preventDefault();
      $('#Edit_Content').lockManager('unlock',1).done(function() {
        $('<input/>',{
         type: 'hidden',
         name: $(self).attr('name'),
         val: $(self).val()
        }).appendTo(form);
        form.trigger('submit');
      });
    }
  });

  $(document).on('click', '[name$="submit"]', function(ev) {
    // set the form to not dirty.
    $('#Edit_Content').dirtyForm('option','dirty',false);
    if( do_locking ) {
      // unlock the item, and submit the form
      var self = this;
      ev.preventDefault();
      var form = $(this).closest('form');
      $('#Edit_Content').lockManager('unlock',1).done(function() {
        $('<input/>',{
         type: 'hidden',
         name: $(self).attr('name'),
         val: $(self).val()
        }).appendTo(form);
        form.trigger('submit');
      });
    }
  });

  // handle apply (ajax submit)
  $(document).on('click', '[name$="apply"]', function() {
    // apply does not do an unlock.
    if( typeof tinymce !== 'undefined') tinymce.triggerSave(); // TODO create a "save editor content" API that can be generally used
    var data = $('#Edit_Content').find('input:not([type="submit"]), select, textarea').serializeArray();
    data.push({
      'name': '{$actionid}ajax',
      'value': 1
    },
    {
      'name': '{$actionid}apply',
      'value': 1
    });
    $.ajax({
      type: 'POST',
      url: '{$apply_ajax_url}',
      data: data,
      dataType: 'json',
    }).done(function(data, text) {
      var event = $.Event('cms_ajax_apply');
      event.response = data.response;
      event.details = data.details;
      event.close = '{$mod->Lang("close")|escape:"javascript"}';
      if( typeof data.url !== 'undefined' ) event.url = data.url;
      $('body').trigger(event);
    });
    return false;
  });

  $(document).on('cms_ajax_apply',function(e) {
    $('#Edit_Content').dirtyForm('option','dirty',false);
    if( typeof e.url !== 'undefined' ) {
      $('a#viewpage').attr('href',e.url);
    }
  });

{if isset($designchanged_ajax_url)}
  $('#design_id').on('change', function(e,edata) {
    var v = $(this).val();
    var lastValue = $(this).data('lastValue');
    var data = { '{$actionid}design_id': v };
    $.get('{$designchanged_ajax_url}',data,function(data,text) {
      if( typeof data === 'object' ) {
        var sel = $('#template_id').val();
        var fnd = false;
        var first = null;
        var key;
        for( key in data ) {
          if( data.hasOwnProperty(key) ) {
            if( first === null ) first = key;
            if( key == sel ) fnd = true;
          }
        }
        if( !first ) {
          $('#design_id').val(lastValue);
          cms_alert('{$mod->Lang("warn_notemplates_for_design")}');
        }
        else {
          $('#template_id').val('');
          $('#template_id').empty();
          for( key in data ) {
            if( data.hasOwnProperty(key) ) {
              $('#template_id').append('<option value="'+key+'">'+data[key]+'</option>');
            }
          }
          if( fnd ) {
            $('#template_id').val(sel);
          }
          else if( first ) {
            $('#template_id').val(first);
          }
          if( typeof edata === 'undefined' || typeof edata.skip_fallthru === 'undefined' ) {
            $('#template_id').trigger('change');
          }
        }
      }
    },'json');
  });

  $('#design_id').trigger('change', [{ skip_fallthru: 1 }]);
  $('#design_id').data('lastValue',$('#design_id').val());
  $('#template_id').data('lastValue',$('#template_id').val());
  $('#Edit_Content').dirtyForm('option','dirty',false);
{/if}
});
</script>

{$extra_content|default:''}

{if $content_id < 1}
  <h3>{$mod->Lang('prompt_editpage_addcontent')}</h3>
{else}
  <h3>{$mod->Lang('prompt_editpage_editcontent')}&nbsp;<em>({$content_id})</em></h3>
{/if}

{function submit_buttons}
  <p class="pagetext"></p>
  <p class="pageinput">
  <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}" class="pagebutton" title="{$mod->Lang('title_editpage_submit')}">
  <input type="submit" name="{$actionid}cancel" formnovalidate value="{$mod->Lang('cancel')}" class="pagebutton" title="{$mod->Lang('title_editpage_cancel')}">
  {if $content_id > 0}
  <input type="submit" name="{$actionid}apply" value="{$mod->Lang('apply')}" class="pagebutton" title="{$mod->Lang('title_editpage_apply')}">
  {/if}
  {if ($content_id > 0) && $content_obj->IsViewable() && $content_obj->Active()}
  <a id="viewpage" rel="external" href="{$content_obj->GetURL()}" title="{$mod->Lang('title_editpage_view')}">{admin_icon icon='view.gif' alt=lang('view_page')}</a>
  {/if}
</p>
{/function}

<div id="Edit_Content_Result"></div>
<div id="Edit_Content">
{form_start content_id=$content_id}
  <input type="hidden" id="active_tab" name="{$actionid}active_tab">
  {submit_buttons}

  {* tab headers *}
  {foreach $tab_names as $key => $tabname}
    {tab_header name=$key label=$tabname active=$active_tab}
  {/foreach}
  {if $content_obj->HasPreview()}
    {tab_header name='_preview_' label=$mod->Lang('prompt_preview')}
  {/if}

  {* tab content *}
  {foreach $tab_names as $key => $tabname}
    {tab_start name=$key}
      {if isset($tab_message_array[$key])}{$tab_message_array[$key]}{/if}
      {if isset($tab_contents_array[$key])}
        {foreach $tab_contents_array.$key as $fld}{if $fld}
        <div class="pageoverflow">
          <p class="pagetext">{$fld.0|default:''}</p>
          <p class="pageinput">{$fld.1|default:''}{if $fld && is_array($fld) && count($fld) == 3}<br>{$fld.2}{/if}</p>
        </div>
        {/if}{/foreach}
      {/if}
  {/foreach}
  {if $content_obj->HasPreview()}
    {tab_start name='_preview_'}
      <div class="pagewarning">{$mod->Lang('info_preview_notice')}</div>
      <iframe name="_previewframe_" class="preview" id="previewframe"></iframe>
      <div id="previewerror" class="red" style="display:none;color:#000;">
        <fieldset>
          <legend>TODO</legend>
          <ul id="preview_errors"></ul>
        </fieldset>
      </div>
  {/if}
  {tab_end}
{form_end}
</div>{* #Edit_Content *}
