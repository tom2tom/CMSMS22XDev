<script>
$(function() {
  $('.helpicon').on('click', function(){
    var x = $(this).attr('name');
    $('#'+x).dialog();
  });
});
</script>

<h3>{$mod->Lang('import_design_step1')}</h3>

{form_start}
<div class="pageinfo">{$mod->Lang('info_import_xml_step1')}</div>

<div class="pageoverflow">
  <p class="pagetext"><label for="import_xml_file">{$mod->Lang('prompt_import_xml_file')}:</label> {cms_help key2='help_import_xml_file' title=$mod->Lang('prompt_import_xml_file')}</p>
  <p class="pageinput">
    <input type="file" id="import_xml_file" name="{$actionid}import_xml_file" size="50">
  </p>
</div>

<div class="pageoverflow">
  <p class="pagetext"></p>
  <p class="pageinput">
    <input type="submit" name="{$actionid}next1" data-ui-icon="ui-icon-triangle-2-e-w" value="{$mod->Lang('next')}">
    <input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
  </p>
</div>
{form_end}

<div style="display: none;">{strip}
  <div id="help_import_xml_file" title="{$mod->Lang('prompt_help')}">{$mod->Lang('help_import_xml_file')}</div>
{/strip}</div>
