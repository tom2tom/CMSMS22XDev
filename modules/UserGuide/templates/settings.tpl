{form_start action='save_settings'}
  <div class="pageoverflow">
    {$t=$mod->Lang('adminSection')}<label class="pagetext" for="section">{$t}:</label> {cms_help key2='help_menusection' title=$t}<br>
    <select id="section" class="pageinput fatinput" name="{$actionid}adminSection">
      {html_options options=$sectionChoices selected=$adminSection}
    </select>
  </div>
  <br>
  <div class="pageoverflow">
    {$t=$mod->Lang('customLabel')}<label class="pagetext" for="labelcustom">{$t}:</label> {cms_help key2='help_customlabel' title=$t}<br>
    <input type="text" id="labelcustom" class="pageinput fatinput" name="{$actionid}customLabel" value="{$customLabel}" size="30">
  </div>
  <br>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}customCSS" value="0">
    {$t=$mod->Lang('customCSS')}<label class="pagetext" for="csscustom">{$t}:</label> {cms_help key2='help_customCSS' title=$t}<br>
    <input type="checkbox" id="csscustom" class="pageinput" name="{$actionid}customCSS" value="1"{if $customCSS} checked{/if}>
  </div>
  <br>
  <div class="pageoverflow">
    {$t=$mod->Lang('guideStyles')}<label class="pagetext" for="dfltguide">{$t}:</label> {cms_help key2='help_guideStyles' title=$t}<br>
    <select id="dfltguide" class="pageinput fatinput" name="{$actionid}guideStyles">
     {html_options options=$guideChoices selected=$guideStyles}
    </select>
  </div>
  <br>
  <div class="pageoverflow">
    {$t=$mod->Lang('listStyles')}<label class="pagetext" for="dfltlist">{$t}:</label> {cms_help key2='help_listStyles' title=$t}<br>
    <select id="dfltlist" class="pageinput fatinput" name="{$actionid}listStyles">
     {html_options options=$listChoices selected=$listStyles}
    </select>
  </div>
  <p class="information" style="margin:1.5em 0">{$mod->Lang('info_tpldefault')}</p>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}useSmarty" value="0">
    {$t=$mod->Lang('useSmartydefault')}<label class="pagetext" for="smartyuse">{$t}:</label> {cms_help key2='help_defaultSmarty' title=$t}<br>
    <input type="checkbox" id="smartyuse" class="pageinput" name="{$actionid}useSmarty" value="1"{if $useSmarty} checked{/if}>
  </div>
  <br>
  <div class="pageoverflow">
    {$t=$mod->Lang('filesFolder')}<label class="pagetext" for="folder">{$t}:</label> {cms_help key2='help_filesFolder' title=$t}<br>
    <input type="text" id="folder" class="pageinput fatinput" name="{$actionid}filesFolder" value="{$filesFolder}" size="30">
  </div>
  <br>
  <div class="pageoverflow">
    <p class="pageinput">
      <input type="submit" name="{$actionid}submit" value="{$mod->Lang('save')}">
      <input type="submit" name="{$actionid}cancel" value="{lang('cancel')}">
    </p>
  </div>
</form>
