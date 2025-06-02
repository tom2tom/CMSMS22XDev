<h3>{$mod->Lang('copy_template')}</h3>

{form_start tpl=$actionparams.tpl __activetab='templates'}
<fieldset>
  <legend>{$mod->Lang('prompt_source_template')}</legend>
{if $tpl->get_id() > 0}
  <div class="startside">
{/if}
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_name">{$mod->Lang('prompt_name')}:</label></p>
      <p class="pageinput" id="tpl_name">{$tpl->get_name()}</p>
    </div>

{if !empty($type_list)}
    <div class="pageoverflow">
      <p class="pagetext"><label for="ttype">{$mod->Lang('prompt_type')}:</label></p>
      <p class="pageinput" id="ttype">
        {$type_list[$tpl->get_type_id()]}
      </p>
    </div>
{/if}

{if !empty($user_list)}
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_own">{$mod->Lang('prompt_owner')}:</p>
      <p class="pageinput" id="tpl_own">
        {$user_list[$tpl->get_owner_id()]}
      </p>
    </div>
{/if}

{if !empty($category_list)}
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_cats">{$mod->Lang('prompt_category')}:</p>
      <p class="pageinput" id="tpl_cats">
        {$category_list[$tpl->get_category_id()|default:0]}
      </p>
    </div>
{/if}
{if $tpl->get_id() > 0}
  </div>{* column *}
  <p class="startside" style="width:5%;min-width:1em"></p>
  <div class="startside last">
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_creatat">{$mod->Lang('prompt_created')}:</label></p>
      <p class="pageinput" id="tpl_creatat">{$tpl->get_created()|localedate_format:'%x %X'}</p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_modat">{$mod->Lang('prompt_modified')}:</label></p>
      <p class="pageinput" id="tpl_modat">{$tpl->get_modified()|localedate_format:'%x %X'}</p>
    </div>
  </div>{* column *}
{/if}
</fieldset>

<fieldset>
  <legend>{$mod->Lang('prompt_dest_template')}</legend>
  <div class="pageoverflow">
    <p class="pagetext"><label for="tpl_destname">*{$mod->Lang('prompt_name')}:</label></p>
    <p class="pageinput">
      <input type="text" id="tpl_destname" name="{$actionid}new_name" value="{$new_name|default:''}" size="50" maxlength="50">
    </p>
  </div>
</fieldset>
<br>
<div class="pageoverflow">
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}">
    <input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
    <input type="submit" name="{$actionid}apply" data-ui-icon="ui-icon-caret-1-n" value="{$mod->Lang('apply')}">
  </p>
</div>
{form_end}
