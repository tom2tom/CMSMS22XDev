{form_start action='savesettings'}
  <label for="chkadvanced" class="pagetext">{$mod->Lang('enableadvanced')}:</label>&nbsp;
  {cms_help key2='help_advancedmode' title=$mod->Lang('enableadvanced')}<br>
  <input type="checkbox" id="chkadvanced" class="optioninput" name="{$actionid}advancedmode" value="1"{if $advancedmode} checked{/if}>
  <br>
  <label for="chkhidden" class="pagetext">{$mod->Lang('showhiddenfiles')}:</label>&nbsp;
  {cms_help key2='help_showhiddenfiles' title=$mod->Lang('showhiddenfiles')}<br>
  <input type="checkbox" id="chkhidden" class="optioninput" name="{$actionid}showhiddenfiles" value="1"{if $showhiddenfiles} checked{/if}>
  <br>
  <label for="chkshow" class="pagetext">{$mod->Lang('showthumbnails')}:</label>&nbsp;
  {cms_help key2='help_showthumbnails' title=$mod->Lang('showthumbnails')}<br>
  <input type="checkbox" id="chkshow" class="optioninput" name="{$actionid}showthumbnails" value="1"{if $showthumbnails} checked{/if}>
  <br>
  <label for="chkcreate" class="pagetext">{$mod->Lang('create_thumbnails')}:</label>&nbsp;
  {cms_help key2='help_create_thumbnails' title=$mod->Lang('create_thumbnails')}<br>
  <input type="checkbox" id="chkcreate" class="optioninput" name="{$actionid}create_thumbnails" value="1"{if $create_thumbnails} checked{/if}>
  <br>
  <label for="selsize" class="pagetext">{$mod->Lang('iconsize')}:</label>&nbsp;
  {cms_help key2='help_iconsize' title=$mod->Lang('iconsize')}<br>
  <select id="selsize" class="optioninput" name="{$actionid}iconsize">
   {html_options options=$iconsizes selected=$iconsize}
  </select>
  <br>
  <label for="selstyle" class="pagetext">{$mod->Lang('permissionstyle')}:</label>&nbsp;
  {cms_help key2='help_permissionstyle' title=$mod->Lang('permissionstyle')}<br>
  <select id="selstyle" class="optioninput" name="{$actionid}permissionstyle">
   {html_options options=$permstyles selected=$permissionstyle}
  </select>
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}">
  </p>
</form>
