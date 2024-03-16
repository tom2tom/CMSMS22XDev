<script>
$(function() {
{$locker=$tpl_id > 0 && isset($lock_timeout) && $lock_timeout > 0}
{if $locker}
    $('#form_edittemplate').dirtyForm({
        beforeUnload: function(is_dirty) {
            $('#form_edittemplate').lockManager('unlock');
        },
        unloadCancel: function() {
            $('#form_edittemplate').lockManager('relock');
        }
    })
    // initialize lock manager (oid == 0 does nothing)
    .lockManager({
        type: 'template',
        oid: {$tpl_id|default:0},
        uid: {$userid},
        lock_timeout: {$lock_timeout|default:0},
        lock_refresh: {$lock_refresh|default:0},
        error_handler: function(err) {
            cms_alert('Lock error '+err.type+' // '+err.msg);
        },
        lostlock_handler: function(err) {
            // lost the lock on this template, prevent the user saving
            // anything and display a message
            $('#submitbtn,#applybtn').prop('disabled',true);
{*          $('#submitbtn,#applybtn').button({ 'disabled' : true });TODO extra .button needed?*}
            $('#cancelbtn').fadeOut().val('{lang("close")}').fadeIn();
            $('#form_edittemplate').dirtyForm('option','dirty',false);
            $('.lock-warning').removeClass('hidden-item');
            cms_alert("{$mod->Lang('msg_lostlock')|escape:'javascript'}");
        }
    });
{/if}
    $(document).on('cmsms_textchange',function() {
        // editor textchange, set the form dirty
        $('#form_edittemplate').dirtyForm('option','dirty',true);
    });
    // if user clicks one of these buttons, the form is no longer considered dirty for the purposes of warnings
    $('#submitbtn,#cancelbtn,#applybtn').on('click',function() {
        $('#form_edittemplate').dirtyForm('option','dirty',false);
    });
{if $locker}
    // only one of #importbtn, #exportbtn will exist
    // no lock-removal for #applybtn click
    $('#submitbtn,#cancelbtn,#importbtn,#exportbtn').on('click',function(e) {
        // async unlock might interfere with next-displayed page?
//      $('#form_edittemplate').lockManager('unlock');
        e.preventDefault();
        // unlock the item before submitting the form
        var self = this;
        $('#form_edittemplate').lockManager('unlock').done(function() {
            $(self).off('click').trigger('click');
        });
    });
{/if}
    $('#applybtn').on('click',function(e) {
        e.preventDefault();
        var tform = $('#form_edittemplate'),
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
    $('#a_helptext').on('click',function(e) {
        e.preventDefault();
        var dlg = $('#helptext_dlg');
        if (dlg.length >0 ) dlg.dialog({ 'width': 'auto' });
    });
});
</script>

{$helptext=$type_obj->get_template_helptext($type_obj->get_name())}
{if $helptext}
<div id="helptext_dlg" title="{$mod->Lang('prompt_template_help')}" style="display: none;">
    {$helptext}
</div>
{/if}

{$get_lock = $template->get_lock()}
{capture assign='disable'}
{if (isset($get_lock) && ($userid != $get_lock.uid))} disabled{/if}
{/capture}
{if isset($get_lock)}
<p class="warning lock-warning">{$mod->Lang('lock_warning')}</p>
{/if}

{form_start id='form_edittemplate' extraparms=$extraparms}
<div class="cf">{$tplid=$template->get_id()}
    <div class="pageoverflow">
        <p class="pageinput">
            <input type="submit" id="submitbtn" name="{$actionid}submit" value="{$mod->Lang('submit')}"{$disable|strip}>
            <input type="submit" id="cancelbtn" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
{if $tplid > 0}
            <input type="submit" id="applybtn" name="{$actionid}apply" data-ui-icon="ui-icon-disk" value="{$mod->Lang('apply')}"{$disable|strip}>
{/if}
        </p>
    </div>
{if $tplid > 0}
    <fieldset>
    <div class="grid_6" style="margin-left:0;margin-right:0">
{/if}
        <div class="pageoverflow">
            <p class="pagetext"><label for="tpl_name">*{$mod->Lang('prompt_name')}:</label>&nbsp;{cms_help key2=help_template_name title=$mod->Lang('prompt_name')}</p>
            <p class="pageinput">
                <input id="tpl_name" type="text" name="{$actionid}name" size="50" maxlength="90" value="{$template->get_name()}"{if !$has_manage_right} readonly{/if} placeholder="{$mod->Lang('new_template')}">
            </p>
        </div>
{$usage_str=$template->get_usage_string()}
    {if $usage_str}
        <div class="pageoverflow">
            <p class="pagetext"><label>{$mod->Lang('prompt_usage')}:</label>&nbsp;{cms_help key2='help_tpl_usage' title=$mod->Lang('prompt_usage')}</p>
            <p class="pageinput">
                {$usage_str}
            </p>
        </div>
    {/if}
{if $tplid > 0}
    </div>{* column *}
    <div class="grid_6">
        <div class="pageoverflow">
            <p class="pagetext"><label for="tpl_created">{$mod->Lang('prompt_created')}:</label>&nbsp;{cms_help key2='help_tpl_created' title=$mod->Lang('prompt_created')}</p>
            <p class="pageinput">
                {$template->get_created()|cms_date_format}
            </p>
        </div>
        <div class="pageoverflow" id="tpl_modified_cont">
            <p class="pagetext"><label for="tpl_modified">{$mod->Lang('prompt_modified')}:</label>&nbsp;{cms_help key2='help_tpl_modified' title=$mod->Lang('prompt_modified')}</p>
            <p class="pageinput">
                {$template->get_modified()|cms_date_format}
            </p>
        </div>
    </div>{* column *}
    </fieldset>
{/if}
</div>

{tab_header name='content' label=lang('content')}
{tab_header name='description' label=$mod->Lang('prompt_description')}
{if $has_themes_right}
 {tab_header name='designs' label=$mod->Lang('prompt_designs')}
{/if}
{if ($has_manage_right || $template->get_owner_id() == $userid)}
 {tab_header name='permissions' label=$mod->Lang('prompt_permissions')}
{/if}
{if $has_manage_right}
 {tab_header name='advanced' label=$mod->Lang('prompt_advanced')}
{/if}

{tab_start name='content'}
<div class="pageoverflow">
    <p class="pagetext">
      <label for="content">{lang('content')}:</label>&nbsp;{cms_help key2=help_template_contents title=lang('content')}
      {if !empty($helptext)}
        <a id="a_helptext" href="javascript:void(0);" style="float: right;">{$mod->Lang('prompt_template_help')}</a>
      {/if}
    </p>
    {if $template->has_content_file()}
      <div class="information">{$mod->Lang('info_template_content_file',$template->get_content_filename())}</div>
    {else}
    <p class="pageinput">
        {cms_textarea id='content' prefix=$actionid name='contents' value=$template->get_content() type='smarty' rows=20}
    </p>
    {/if}
</div>

{tab_start name='description'}
<div class="pageoverflow">
    <p class="pagetext"><label for="description">{$mod->Lang('prompt_description')}:</label>&nbsp;{cms_help key2=help_template_description title=$mod->Lang('prompt_description')}</p>
    <p class="pageinput">
        <textarea id="description" name="{$actionid}description"{if !$has_manage_right} readonly{/if}>{$template->get_description()}</textarea>
    </p>
</div>

{if $has_themes_right}
    {tab_start name='designs'}
    <div class="pageoverflow">
        <p class="pagetext"><label for="designlist">{$mod->Lang('prompt_designs')}:</label>&nbsp;{cms_help key2=help_template_designlist title=$mod->Lang('prompt_designs')}</p>
        <p class="pageinput">
            <select id="designlist" name="{$actionid}design_list[]" multiple size="5">
                {html_options options=$design_list selected=$template->get_designs()}
            </select>
        </p>
    </div>
{/if}

{if ($has_manage_right || $template->get_owner_id() == $userid)}
    {tab_start name='permissions'}
    {if !empty($user_list)}
    <div class="pageoverflow">
        <p class="pagetext"><label for="tpl_owner">{$mod->Lang('prompt_owner')}:</label>&nbsp;{cms_help key2=help_template_owner title=$mod->Lang('prompt_owner')}</p>
        <p class="pageinput">
            <select id="tpl_owner" name="{$actionid}owner_id">
                {html_options options=$user_list selected=$template->get_owner_id()}
            </select>
        </p>
    </div>
    {/if}
    {if !empty($addt_editor_list)}
    <div class="pageoverflow">
        <p class="pagetext"><label for="tpl_addeditor">{$mod->Lang('additional_editors')}:</label>&nbsp;{cms_help key2=help_template_addteditors title=$mod->Lang('additional_editors')}</p>
        <p class="pageinput">
            <select id="tpl_addeditor" name="{$actionid}addt_editors[]" multiple size="5">
                {html_options options=$addt_editor_list selected=$template->get_additional_editors()}
            </select>
        </p>
    </div>
    {/if}
{/if}
{if $has_manage_right}
    {tab_start name='advanced'}
        <div class="pageoverflow">
            <p class="pagetext"><label for="tpl_listable">{$mod->Lang('prompt_listable')}:</label>&nbsp;{cms_help key2=help_template_listable title=$mod->Lang('prompt_listable')}</p>
            <p class="pageinput">
                <select id="tpl_listable" name="{$actionid}listable"{if $type_is_readonly} readonly{/if}>
                {cms_yesno selected=$template->get_listable()}
                </select>
            </p>
        </div>
        {if !empty($type_list)}
            <div class="pageoverflow">
                <p class="pagetext"><label for="tpl_type">{$mod->Lang('prompt_type')}:</label>&nbsp;{cms_help key2=help_template_type title=$mod->Lang('prompt_type')}</p>
                <p class="pageinput">
                    <select id="tpl_type" name="{$actionid}type"{if $type_is_readonly} readonly{/if}>
                        {html_options options=$type_list selected=$template->get_type_id()}
                    </select>
                </p>
            </div>
            {if $type_obj->get_dflt_flag()}
            <div class="pageoverflow">
                <p class="pagetext"><label for="tpl_dflt">{$mod->Lang('prompt_default')}:</label>&nbsp;{cms_help key2=help_template_dflt title=$mod->Lang('prompt_default')}</p>
                <p class="pageinput">
                    <select id="tpl_dflt" name="{$actionid}default"{if $template->get_type_dflt()} disabled{/if}>
                        {cms_yesno selected=$template->get_type_dflt()}
                    </select>
                </p>
            </div>
            {/if}
        {/if}
        {if !empty($category_list)}
        <div class="pageoverflow">
            <p class="pagetext"><label for="tpl_category">{$mod->Lang('prompt_category')}:</label>&nbsp;{cms_help key2=help_template_category title=$mod->Lang('prompt_category')}</p>
            <p class="pageinput">
                <select id="tpl_category" name="{$actionid}category_id">
                    {html_options options=$category_list selected=$template->get_category_id()}
                </select>
            </p>
        </div>
        {/if}
{if $tplid > 0}
{if $template->has_content_file()}{$inid='importbtn'}{else}{$inid='exportbtn'}{/if}
        <div class="pageoverflow">
            <p class="pagetext"><label style="pointer-events:none" for="{$inid}">{$mod->Lang('prompt_filetemplate')}:</label>&nbsp;{cms_help key2=help_template_file title=$mod->Lang('prompt_filetemplate')}</p>
            <p class="pageinput">
 {if $template->has_content_file()}
            <input type="submit" id="importbtn" name="{$actionid}import" data-ui-icon="ui-icon-circle-arrow-n" value="{$mod->Lang('import')}">
 {else}
            <input type="submit" id="exportbtn" name="{$actionid}export" data-ui-icon="ui-icon-circle-arrow-s" value="{$mod->Lang('export')}">
 {/if}
            </p>
        </div>
{/if}
{/if}{*$has_manage_right*}
{tab_end}

{form_end}
