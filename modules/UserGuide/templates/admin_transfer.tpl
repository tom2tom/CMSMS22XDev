{if $have_xml}
<script>
$(function() {
 $('#importxml').on('change', function(e) {
  var sel = e.target.files[0];
  var showsize = (sel.size / 1024).toFixed(2);
  var info = sel.name + ' - ' + showsize + 'kB';
  $('#filesel').text(info);
  $('#filesubmit').css('visibility','visible');
 });
});
</script>
{/if}
{if !empty($guides)}
{if $have_xml}
<div class="pageoverflow">
  <label class="pagetext" for="export">{$mod->Lang('exportxml')}</label><br>
  <a id="export" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" href="{cms_action_url action=xml_export}">
    <span class="ui-button-icon ui-icon ui-icon-arrowreturnthick-1-s"></span> {lang('export')}
  </a>
</div>
{else}
<p class="error">{$mod->Lang('no_xml')}</p>
{/if}
<br>
{/if}
{if $have_xml}
<p class="pagetext">{$mod->Lang('importxml')}</p>
<label id="selectorlabel" class="ui-button ui-corner-all ui-widget" for="importxml">
 <span class="ui-button-icon ui-icon ui-icon-search"></span>
 <span class="ui-button-icon-space"></span>
 {$mod->Lang('selectfile')}...</label><br>
{form_start action='xml_import'}
  <input type="file" id="importxml" name="{$actionid}xmlfile" accept=".xml">
  <div class="pageinput">
    <p id="filesel">&nbsp;</p>
    <input type="submit" id="filesubmit" style="visibility:hidden" name="{$actionid}submit" data-ui-icon="ui-icon-arrowreturnthick-1-n" value="{$mod->Lang('import')}">
  </div>
</form>
{elseif empty($guides)}
<p class="error">{$mod->Lang('no_xml')}</p>
{/if}
{if $have_UserGuide2}
<br>
<div class="pageoverflow">
  <label class="pagetext" for="importold">{$mod->Lang('import_UserGuide2')}</label><br>
  <a id="importold" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" href="{cms_action_url action='import_module' source='UserGuide2'}">
    <span class="ui-button-icon ui-icon ui-icon-newwin"></span> {$mod->Lang('import')}
  </a>
</div>
{/if}
{if $have_UsersGuide}
<br>
<div class="pageoverflow">
  <label class="pagetext" for="importvold">{$mod->Lang('import_UsersGuide')}</label><br>
  <a id="importvold" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" href="{cms_action_url action='import_module' source='UsersGuide'}">
    <span class="ui-button-icon ui-icon ui-icon-newwin"></span> {$mod->Lang('import')}
  </a>
</div>
{/if}
