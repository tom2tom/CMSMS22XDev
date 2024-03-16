<script>
$(function() {
{$locker=$css_id > 0 && isset($lock_timeout) && $lock_timeout > 0}
{if $locker}
    $('#form_editcss').dirtyForm({
        beforeUnload: function() {
            $('#form_editcss').lockManager('unlock');
        },
        unloadCancel: function() {
            $('#form_editcss').lockManager('relock');
        }
    })
    // initialize lock manager (oid == 0 does nothing)
    .lockManager({
        type: 'stylesheet',
        oid: {$css_id},
        uid: {$userid},
        lock_timeout: {$lock_timeout|default:0},
        lock_refresh: {$lock_refresh|default:0},
        error_handler: function(err) {
            cms_alert('Lock error '+err.type+' // '+err.msg);
        },
        lostlock_handler: function(err) {
            // lost the lock on this stylesheet, prevent the user saving
            // anything and display a message
            console.debug('lost lock handler');
            $('#submitbtn,#applybtn').prop('disabled',true);
{*          $('#submitbtn,#applybtn').button({ 'disabled' : true });TODO extra .button needed?*}
            $('#cancelbtn').fadeOut().val('{lang("close")}').fadeIn();
            $('#form_editcss').dirtyForm('option','dirty',false);
            $('.lock-warning').removeClass('hidden-item');
            cms_alert("{$mod->Lang('msg_lostlock')|escape:'javascript'}");
        }
    });
{/if}
    $(document).on('cmsms_textchange',function() {
        // editor textchange, set the form dirty
        $('#form_editcss').dirtyForm('option','dirty',true);
    });
    // if user clicks one of these buttons, the form is no longer considered dirty for the purposes of warnings
    $('#submitbtn,#cancelbtn,#applybtn').on('click',function() {
        $('#form_editcss').dirtyForm('option','dirty',false);
    });
{if $locker}
    // only one of #importbtn, #exportbtn will exist
    // no lock-removal for #applybtn click
    $('#submitbtn,#cancelbtn,#importbtn,#exportbtn').on('click',function(e) {
        // async unlock might interfere with next-displayed page?
//      $('#form_editcss').lockManager('unlock');
        e.preventDefault();
        // unlock the item before submitting the form
        var self = this;
        $('#form_editcss').lockManager('unlock').done(function() {
            $(self).off('click').trigger('click');
        });
    });
{/if}
    $('#applybtn').on('click',function(e) {
        e.preventDefault();
        var tform = $('#form_editcss'),
          url = tform.attr('action')+'?showtemplate=false&{$actionid}apply=1',
          data = tform.serializeArray();

        $.post(url,data,function(data,textStatus) {
            var response = $('<aside></aside>',{ 'class':'message' });
            if (data.status === 'success') {
                response.addClass('pagemcontainer')
                    .append($('<span></span>',{ 'class':'close-warning',text:'{lang("close")}' }))
                    .append($('<p></p>',{ text:data.message }));
            } else if (data.status === 'error') {
                response.addClass('pageerrorcontainer')
                    .append($('<span></span>',{ 'class':'close-warning',text:'{lang("close")}' }))
                    .append($('<p></p>',{ text:data.message }));
            }

            $('body').append(response.hide());
            response.slideDown(1000,function() {
                window.setTimeout(function() {
                    response.slideUp();
                    response.remove();
                },10000);
            });

            $('#cancelbtn').button('option','label','{$mod->Lang("cancel")}');
            $('#tpl_modified_cont').hide();
            $('#content').trigger('focus');
        });
    });
    // disable Media Type checkboxes if media query is in use
    if ($('#mediaquery').val() !== '') {
        $('.media-type :checkbox').prop({
            disabled: true,
            checked: false
        });
    }
    $('#mediaquery').on('keyup',function(e) {
        if ($('#mediaquery').val() !== '') {
            $('.media-type:checkbox').prop({
                disabled: true,
                checked: false
            });
        } else {
            $('.media-type:checkbox').prop('disabled',false);
        }
    });
});
</script>

{$get_lock = $css->get_lock()}
{capture assign='disable'}
{if (isset($get_lock) && ($userid != $get_lock.uid))} disabled{/if}
{/capture}
{if isset($get_lock)}
<p class="warning lock-warning">{$mod->Lang('lock_warning')}</p>
{/if}

{form_start id='form_editcss' extraparms=$extraparms}
<div class="cf">
    <div class="pageoverflow">
        <p class="pageinput">
            <input type="submit" id="submitbtn" name="{$actionid}submit" value="{$mod->Lang('submit')}"{$disable|strip}>
            <input type="submit" id="cancelbtn" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
{if $css_id > 0}
            <input type="submit" id="applybtn" name="{$actionid}apply" data-ui-icon="ui-icon-disk" value="{$mod->Lang('apply')}"{$disable|strip}>
{/if}
        </p>
    </div>
{if $css_id > 0}
    <fieldset>
    <div class="grid_6" style="margin-left:0;margin-right:0">
{/if}
        <div class="pageoverflow">
            <p class="pagetext"><label for="css_name">*{$mod->Lang('prompt_name')}:</label>&nbsp;{cms_help key2=help_stylesheet_name title=$mod->Lang('prompt_name')}</p>
            <p class="pageinput">
                <input id="css_name" type="text" name="{$actionid}name" size="50" maxlength="90" value="{$css->get_name()}" placeholder="{$mod->Lang('new_stylesheet')}">
            </p>
        </div>
{if $css_id > 0}
    </div>{* column *}
    <div class="grid_6">
        <div class="pageoverflow">
            <p class="pagetext"><label for="css_created">{$mod->Lang('prompt_created')}:</label>&nbsp;{cms_help key2=help_stylesheet_created title=$mod->Lang('prompt_created')}</p>
            <p class="pageinput" id="css_created">
                {$css->get_created()|localedate_format:'%x %X'}
            </p>
        </div>
        <div class="pageoverflow">
            <p class="pagetext"><label for="css_modified">{$mod->Lang('prompt_modified')}:</label>&nbsp;{cms_help key2=help_stylesheet_modified title=$mod->Lang('prompt_modified')}</p>
            <p class="pageinput" id="css_modified">
                {$css->get_modified()|localedate_format:'%x %X'}
            </p>
        </div>
    </div>{* column *}
    </fieldset>
{/if}
</div>

{tab_header name='content' label=lang('content')}
{tab_header name='description' label=$mod->Lang('prompt_description')}
{tab_header name='media_type' label=$mod->Lang('prompt_media_type')}
{tab_header name='media_query' label=$mod->Lang('prompt_media_query')}
{if $has_designs_right}
    {tab_header name='designs' label=$mod->Lang('prompt_designs')}
{/if}
{if $css_id > 0}
{tab_header name='advanced' label=$mod->Lang('prompt_advanced')}
{/if}
{tab_start name='content'}
{if $css->has_content_file()}
  <div class="information">{$mod->Lang('info_css_content_file',$css->get_content_filename())}</div>
{else}
  <div class="pageoverflow">
      <p class="pagetext"><label for="stylesheet">{lang('content')}:</label>&nbsp;{cms_help key2=help_stylesheet_content title=lang('content')}</p>
      <p class="pageinput">
          {cms_textarea id='stylesheet' prefix=$actionid name='content' value=$css->get_content() type='css' rows=20 cols=80}
      </p>
  </div>
{/if}
{tab_start name='description'}
<div class="pageoverflow">
    <p class="pagetext"><label for="txt_description">{$mod->Lang('prompt_description')}:</label>&nbsp;{cms_help key2=help_css_description title=$mod->Lang('prompt_description')}</p>
    <p class="pageinput">
        <textarea id="txt_description" name="{$actionid}description" rows="10" cols="80">{$css->get_description()}</textarea>
    </p>
</div>
{tab_start name='media_type'}
<!-- media types -->
<div class="pagewarning">{$mod->Lang('info_editcss_mediatype_tab')}</div>
<div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('prompt_media_type')}:</p>
    {assign var='tmp' value='all,aural,speech,braille,embossed,handheld,print,projection,screen,tty,tv'}
    {assign var='all_types' value=explode(',',$tmp)}

    <p class="pageinput media-type">
    {foreach $all_types as $type}{strip}
        <input id="media_type_{$type}" type="checkbox" name="{$actionid}media_type[]" value="{$type}"{if $css->has_media_type($type)} checked{/if}>
        &nbsp;
        {$tmp='media_type_'|cat:$type}
        <label for="media_type_{$type}">{$mod->Lang($tmp)}</label>
        {if !$type@last}<br>{/if}
    {/strip}{/foreach}
    </p>
</div>
{tab_start name='media_query'}
<!-- media query -->
<div class="pagewarning">{$mod->Lang('info_editcss_mediaquery_tab')}</div>
<div class="pageoverflow">
    <p class="pagetext"><label for="mediaquery">{$mod->Lang('prompt_media_query')}:</label>&nbsp;{cms_help key2=help_css_mediaquery title=$mod->Lang('prompt_media_query')}</p>
    <p class="pageinput">
        <textarea id="mediaquery" name="{$actionid}media_query" rows="10" cols="80">{$css->get_media_query()}</textarea>
    </p>
</div>

{if $has_designs_right}
{tab_start name='designs'}
<!-- designs -->
<div class="pageoverflow">
    <p class="pagetext"><label for="designlist">{$mod->Lang('prompt_designs')}:</label>&nbsp;{cms_help key2=help_css_designs title=$mod->Lang('prompt_designs')}</p>
    <p class="pageinput">
        <select id="designlist" name="{$actionid}design_list[]" multiple size="5">
            {html_options options=$design_list selected=$css->get_designs()}
        </select>
    </p>
</div>
{/if}
{if $css_id > 0}
{tab_start name='advanced'}
<div class="pageoverflow">
{if $css->has_content_file()}{$inid='importbtn'}{else}{$inid='exportbtn'}{/if}
    <p class="pagetext"><label for="{$inid}">{$mod->Lang('prompt_cssfile')}:</label>&nbsp;{cms_help key2=help_stylsheet_file title=$mod->Lang('prompt_cssfile')}</p>
    <p class="pageinput">
{if $css->has_content_file()}
        <input type="submit" id="importbtn" name="{$actionid}import" data-ui-icon="ui-icon-circle-arrow-n" value="{$mod->Lang('import')}">
{else}
        <input type="submit" id="exportbtn" name="{$actionid}export" data-ui-icon="ui-icon-circle-arrow-s" value="{$mod->Lang('export')}">
{/if}
    </p>
</div>
{/if}
{tab_end}

{form_end}
