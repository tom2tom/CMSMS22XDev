{form_start id="admin_edit_design"}{$did=$design->get_id()}
<input type="hidden" name="{$actionid}design" value="{$did}">
<input type="hidden" name="{$actionid}ajax" id="ajax">

<div class="pageoverflow">
  <p class="pageinput">
    <input id="submitme" type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}">
    <input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
    <input id="applyme" type="submit" name="{$actionid}apply" data-ui-icon="ui-icon-disk" value="{$mod->Lang('apply')}">
  </p>
</div>

{if $did > 0}
<fieldset>
  <div style="width: 49%; float: left;">
{/if}
    <div class="pageoverflow">
      <p class="pagetext"><label for="design_name">{$mod->Lang('prompt_name')}</label>:&nbsp;{cms_help key2='help_design_name' title=$mod->Lang('prompt_name')}</p>
      <p class="pageinput">
        <input type="text" id="design_name" name="{$actionid}name" value="{$design->get_name()}" size="50" maxlength="90">
      </p>
    </div>
{if $did > 0}
  </div>
  <div style="width: 49%; float: right;">
    <div class="pageoverflow">
      <p class="pagetext"><label for="created">{$mod->Lang('prompt_created')}:</label>&nbsp;{cms_help key2='help_design_created' title=$mod->Lang('prompt_created')}</p>
      <p class="pageinput" id="created">
      {$design->get_created()|localedate_format:'%x %X'}
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="modified">{$mod->Lang('prompt_modified')}:</label>&nbsp;{cms_help key2='help_design_modified' title=$mod->Lang('prompt_modified')}</p>
      <p class="pageinput" id="modified">
      {$design->get_modified()|localedate_format:'%x %X'}
      </p>
    </div>
  </div>
</fieldset>
{/if}

{tab_header name='description' label=$mod->Lang('prompt_description')}
{tab_header name='templates' label=$mod->Lang('prompt_templates')}
{tab_header name='stylesheets' label=$mod->Lang('prompt_stylesheets')}
{tab_start name='description'}
  <div class="pageoverflow">
    <p class="pagetext"><label for="description">{$mod->Lang('prompt_description')}:</label>&nbsp;{cms_help key2=help_design_description title=$mod->Lang('prompt_description')}</p>
    <p class="pageinput">
      <textarea id="description" name="{$actionid}description" rows="5">{$design->get_description()}</textarea>
    </p>
  </div>
{tab_start name='templates'}
 {include file='module_file_tpl:DesignManager;admin_edit_design_templates.tpl' scope='root'}
{tab_start name='stylesheets'}
 {include file='module_file_tpl:DesignManager;admin_edit_design_stylesheets.tpl' scope='root'}
{tab_end}
{form_end}

<script>
var __changed=0;
function set_changed() {
   __changed=1;
   console.debug('design is changed');
}
function save_design() {
   var form = $('#admin_edit_design');
   var action = form.attr('action');

   $('#ajax').val(1);
   return $.ajax({
      url: action,
      data: form.serialize()
   });
}
$(function() {
    $('.sortable-list input[type="checkbox"]').hide();
    $('ul.available-items').on('click', 'li', function () {
        $(this).toggleClass('selected ui-state-hover');
    });
    $(document).on('click', '#submitme,#applyme', function() {
        $('select.selall').attr('multiple','multiple');
        $('select.selall option').prop('selected',true);
    });
    $(document).on('change',':input',function() {
        set_changed();
    });
});
</script>
