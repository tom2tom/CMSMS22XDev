{if ($havexml || $havegz)}
<script>
$(function() {
 $('#infile').on('change', function(e) {
  var sel = e.target.files[0];
  var showsize = (sel.size / 1024).toFixed(2);
  var info = sel.name + ' - ' + showsize + 'kB';
  $('#filesel').text(info);
  $('#inform').css('display','block');
 });
});
</script>
{/if}
{if !empty($guides)}
{if ($havexml || $havegz)}
<div class="pageoverflow">
  <label class="pagetext" for="{if $havegz}export{else}export2{/if}">{$mod->Lang('exportdata')}</label><br>
{if $havegz}
  <a id="export" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" href="{cms_action_url action=export type=gzip}">
    <span class="ui-button-icon ui-icon ui-icon-arrowreturnthick-1-s"></span> {$mod->Lang('export_archive')}
  </a>
{if $havexml}<span>&nbsp;</span>{/if}
{/if}
{if $havexml}
  <a id="export2" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" href="{cms_action_url action=export type=xml}">
    <span class="ui-button-icon ui-icon ui-icon-arrowreturnthick-1-s"></span> {$mod->Lang('export_xml')}
  </a>
{/if}
</div>
{else}
<p class="error">{$mod->Lang('no_importing')}</p>
{/if}
<br>
{/if}
{if ($havexml || $havegz)}
<p class="pagetext" style="margin:0;cursor:default">{$mod->Lang('importdata')}</p>
<label id="selectorlabel" class="ui-button ui-corner-all ui-widget" for="infile">
 <span class="ui-button-icon ui-icon ui-icon-search"></span>
 <span class="ui-button-icon-space"></span>
 {$mod->Lang('selectfile')}...</label>
{form_start action='import' id='inform'}
  <input type="file" id="infile" name="{$actionid}imported" accept="{$seltypes}">
  <div class="pageinput">
    <p id="filesel">&nbsp;</p>
    <input type="submit" id="filesubmit" name="{$actionid}submit" data-ui-icon="ui-icon-arrowreturnthick-1-n" value="{$mod->Lang('import')}">
  </div>
</form>
{elseif empty($guides)}
<p class="error">{$mod->Lang('no_importing')}</p>
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
<div class="pageoverflow">
  <label class="pagetext" for="importvold">{$mod->Lang('import_UsersGuide')}</label><br>
  <a id="importvold" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" href="{cms_action_url action='import_module' source='UsersGuide'}">
    <span class="ui-button-icon ui-icon ui-icon-newwin"></span> {$mod->Lang('import')}
  </a>
</div>
{/if}
