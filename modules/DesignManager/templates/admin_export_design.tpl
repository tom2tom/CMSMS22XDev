<script>
$(function() {
 $('#expbtn').on('click',function() {
  $(this).fadeOut();
  $('#cancelbtn').fadeOut().attr('name','{$actionid}close').html('{lang('close')}').fadeIn();
 });
});
</script>
<h3>{$pagetitle}</h3>
<p class="pagetext"><label>{$mod->Lang('prompt_created')}:</label></p>
<p class="pageinput">{$created|localedate_format:'%x %X'}</p>
{if !empty($modified)}
<p class="pagetext"><label>{$mod->Lang('prompt_modified')}:</label></p>
<p class="pageinput">{$modified|localedate_format:'%x %X'}</p>
{/if}
<p class="information">{$mod->Lang('info_export_changes')}</p>
{form_start action=admin_export_design design=$did}
 <p class="pagetext">
  <label for="txtversion">{lang('version')}:</label>&nbsp;{cms_help key2='help_design_version' title=lang('version')}
 </p>
 <p class="pageinput">
  <input type="text" id="txtversion" name="{$actionid}version" value="{$version}" size="20" maxlength="20" placeholder="{$mod->Lang('version_place')}">
 </p>
 <p class="pagetext">
  <label for="tadesc">{$mod->Lang('prompt_description')}:</label>&nbsp;{cms_help key2='help_design_description' title=$mod->Lang('prompt_description')}
 </p>
 <p class="pageinput">
  <textarea id="tareq" name="{$actionid}description" style="width:40em;max-width:90%;height:5em" cols="40" rows="5">{$description}</textarea>
 </p>
 <p class="pagetext">
  <label for="tareq">{$mod->Lang('prompt_requires')}:</label>&nbsp;{cms_help key2='help_design_requires' title=$mod->Lang('prompt_requires')}
 </p>
 <p class="pageinput">
  <textarea id="tareq" name="{$actionid}requires" style="width:40em;max-width:90%;height:5em" cols="40" rows="5">{$requires}</textarea>
 </p>
 <p class="pagetext">
  <label for="tanotes">{$mod->Lang('prompt_notes')}:</label>&nbsp;{cms_help key2=help_design_notes title=$mod->Lang('prompt_notes')}
 </p>
 <p class="pageinput">
  <textarea id="tanotes" name="{$actionid}notes" style="width:40em;max-width:90%;height:5em" rows="5"></textarea>
 </p>
 <br>
 <div class="pageoptions">
  <input type="submit" id="expbtn" name="{$actionid}next1" data-ui-icon="ui-icon-arrowreturnthick-1-s" value="{$mod->Lang('export')}">
  <input type="submit" id="cancelbtn" name="{$actionid}cancel" value="{lang('cancel')}">
 </div>
</form>
