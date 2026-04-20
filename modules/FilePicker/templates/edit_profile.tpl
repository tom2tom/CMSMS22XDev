{if empty($doclone)}
{form_start pid=$profile->id}
  <h3>{if $profile->id > 0}{$mod->Lang('hdr_edit_profile')} <em>({$profile->id})</em>{else}{$mod->Lang('hdr_add_profile')}{/if}</h3>
{else}
{form_start pid=$profile->id clone=1}
  <h3>{$mod->Lang('hdr_copy_profile')}</h3>
{/if}
  <label for="txtname" class="pagetext required">* {$mod->Lang('name')}:</label>
  {cms_help key2='help_name' title=$mod->Lang('helptitle_name')}<br>
  <input type="text" id="txtname" class="optioninput" name="{$actionid}name" value="{$profile->name|cms_escape}" size="40" maxlength="100" required>
  <br>
  <label for="chkdefault" class="pagetext">{$mod->Lang('default_profile')}:</label>
  {cms_help key2='help_defaultprofile' title=$mod->Lang('helptitle_defaultprofile')}<br>
  <input type="checkbox" id="chkdefault" class="optioninput" name="{$actionid}default" value="1"{if $default} checked{/if}>
  <br>
  <p class="information">{$mod->Lang('info_runtime')}</p>
  <br>
  <label for="txttop" class="pagetext">{$mod->Lang('topdir')}:</label>
  {cms_help key2='help_topdir' title=$mod->Lang('helptitle_topdir')}<br>
  <input type="text" id="txttop" class="optioninput" name="{$actionid}top" value="{$profile->reltop}" size="60">
  <br>
  <label for="chkthumbs" class="pagetext">{$mod->Lang('show_thumbs')}:</label>
  {cms_help key2='help_showthumbs' title=$mod->Lang('helptitle_showthumbs')}<br>
  <input type="checkbox" id="chkthumbs" class="optioninput" name="{$actionid}show_thumbs" value="1"{if $profile->show_thumbs} checked{/if}>
  <br>
  <label for="chkupload" class="pagetext">{$mod->Lang('can_upload')}:</label>
  {cms_help key2='help_canupload' title=$mod->Lang('helptitle_canupload')}<br>
  <input type="checkbox" id="chkupload" class="optioninput" name="{$actionid}can_upload" value="1"{if $profile->can_upload} checked{/if}>
  <br>
  <label for="chkdelete" class="pagetext">{$mod->Lang('can_delete')}:</label>
  {cms_help key2='help_candelete' title=$mod->Lang('helptitle_candelete')}<br>
  <input type="checkbox" id="chkdelete" class="optioninput" name="{$actionid}can_delete" value="1"{if $profile->can_delete} checked{/if}>
  <br>
  <label for="chkmkdir" class="pagetext">{$mod->Lang('can_mkdir')}:</label>
  {cms_help key2='help_canmkdir' title=$mod->Lang('helptitle_canmkdir')}<br>
  <input type="checkbox" id="chkmkdir" class="optioninput" name="{$actionid}can_mkdir" value="1"{if $profile->can_mkdir} checked{/if}>
  <br>
  <label class="pagetext" for="txtexclude">{$mod->Lang('exclude_prefix')}:</label>
  {cms_help key2='help_excludeprefix' title=$mod->Lang('helptitle_excludeprefix')}<br>
  <input type="text" id="txtexclude" class="optioninput" name="{$actionid}exclude_prefix" value="{$profile->exclude_prefix}">
  <br>
  <label class="pagetext" for="txtinclude">{$mod->Lang('match_prefix')}:</label>
  {cms_help key2='help_matchprefix' title=$mod->Lang('helptitle_matchprefix')}<br>
  <input type="text" id="txtinclude" class="optioninput" name="{$actionid}match_prefix" value="{$profile->match_prefix}">
  <br>
  <label class="pagetext" for="chkhidden">{$mod->Lang('show_hidden')}:</label>
  {cms_help key2='help_showhidden' title=$mod->Lang('helptitle_showhidden')}<br>
  <input type="checkbox" id="chkhidden" class="optioninput" name="{$actionid}show_hidden" value="1"{if $profile->show_hidden} checked{/if}>
  <br>
<label class="pagetext" for="chksort">{$mod->Lang('sort')}:</label>
  {cms_help key2='help_sort' title=$mod->Lang('sort')}<br>
  <input type="checkbox" id="chksort" class="optioninput" name="{$actionid}sort" value="1"{if $profile->sort} checked{/if}>
  <br>
  <label class="pagetext" for="seltype">{$mod->Lang('allowed_type')}:</label>
  {cms_help key2='help_type' title=$mod->Lang('type')}<br>
  <select id="seltype" class="optioninput" name="{$actionid}type">
    {html_options options=$filetype_opts selected=$profile->type}
  </select>
  <br>
  <label class="pagetext" for="txtexts">{$mod->Lang('file_extensions')}:</label>
  {cms_help key2='help_fileextensions' title=$mod->Lang('helptitle_fileextensions')}<br>
  <input type="text" id="txtexts" class="optioninput" name="{$actionid}file_extensions" value="{$profile->file_extensions}">
  <br>
  <div class="pageoverflow">
    <div class="pageinput">
      <input type="submit" name="{$actionid}submit" value="{lang('submit')}">
      <input type="submit" name="{$actionid}cancel" value="{lang('cancel')}" formnovalidate>
    </div>
  </div>
</form>
