<h3>{$mod->Lang('set_all_pages')}</h3>
<div class="pagewarning">{$mod->Lang('warning_set_all_pages')}</div>

{form_start extraparms=$extraparms}
<fieldset>
  <div class="startside">
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_name">{$mod->Lang('prompt_name')}:</label></p>
      <p class="pageinput" id="tpl_name">{$template->get_name()}</p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_type">{$mod->Lang('prompt_type')}:</label></p>
      <p class="pageinput" id="tpl_type">{$template_type->get_langified_display_value()}</p>
    </div>

{if !empty($user_list)}
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_own">{$mod->Lang('prompt_owner')}:</label></p>
      <p class="pageinput" id="tpl_own">{$user_list[$template->get_owner_id()]}</p>
    </div>
{/if}

{if !empty($category_list)}
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_cat">{$mod->Lang('prompt_category')}:</label></p>
      <p class="pageinput" id="tpl_cat">{$category_list[$template->get_category_id()|default:0]}</p>
    </div>
{/if}
  </div>

{if $template->get_id()}
  <p class="startside" style="width:5%;min-width:1em"></p>
  <div class="startside last">
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_creat">{$mod->Lang('prompt_created')}:</label></p>
      <p class="pageinput" id="tpl_creat">{$template->get_created()|localedate_format:'%x %X'}</p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_mod">{$mod->Lang('prompt_modified')}:</label></p>
      <p class="pageinput" id="tpl_mod">{$template->get_modified()|localedate_format:'%x %X'}</p>
    </div>
  </div>
{else}
  <div class="clearb"></div>
{/if}
</fieldset>

{if isset($noblocks)}
<div class="pagewarning">{$mod->Lang('warn_setall_nocontentblocks')}</div>
{elseif isset($template_error)}
<div class="pagewarning">{$template_error}</div>
{/if}

<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('confirm_setall_1')}:</p>
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}check1" value="1" id="check1">&nbsp;<label for="check1">{$mod->Lang('confirm_setall_2')}</label><br>
    <input type="checkbox" name="{$actionid}check2" value="1" id="check2">&nbsp;<label for="check2">{$mod->Lang('confirm_setall_3')}</label>
  </p>
</div>
<br>
<div class="pageoverflow">
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}">
    <input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
  </p>
</div>
{form_end}
