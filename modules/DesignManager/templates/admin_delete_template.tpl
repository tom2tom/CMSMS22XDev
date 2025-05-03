<h3>{$mod->Lang('delete_template')}</h3>

{if $page_usage > 0 || count($tpl->get_designs()) > 0}
<div class="pagewarning">{$mod->Lang('warn_template_used')}</div>
{/if}

<fieldset>
  <div class="startside">
  <div class="pageoverflow">
    <p class="pagetext"><label for="tpl_name">{$mod->Lang('prompt_name')}:</label> {cms_help key2='help_copytemplate_name' title=$mod->Lang('prompt_name')}</p>
    <p class="pageinput" id="tpl_name">{$tpl->get_name()}</p>
  </div>
{if !empty($type_list)}
  <div class="pageoverflow">
    <p class="pagetext"><label for="tpl_types">{$mod->Lang('prompt_type')}:</label></p>
    <p class="pageinput" id="tpl_types">
      {$type_list[$tpl->get_type_id()]}
    </p>
  </div>
{/if}
{if !empty($category_list)}
  <div class="pageoverflow">
    <p class="pagetext"><label for="tpl_cats">{$mod->Lang('prompt_category')}:</label></p>
    <p class="pageinput" id="tpl_cats">
      {$category_list[$tpl->get_category_id()|default:0]}
    </p>
  </div>
{/if}
{if !empty($design_list)}
  <div class="pageoverflow">
    <p class="pagetext"><label for="tpl_des">{$mod->Lang('prompt_designs')}:</label></p>
    <p class="pageinput" id="tpl_des">
      {foreach $tpl->get_designs() as $dsn}
        {$design_list[$dsn]}
        {if !$dsn@last}<br>{/if}
      {/foreach}
    </p>
  </div>
{/if}
  </div>{* column *}
  <p class="startside" style="width:5%;min-width:1em"></p>
  <div class="startside last">
{if $tpl->get_id()}
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_created">{$mod->Lang('prompt_created')}:</label></p>
      <p class="pageinput" id="tpl_created">{$tpl->get_created()|localedate_format:'%x %X'}</p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_modified">{$mod->Lang('prompt_modified')}:</label></p>
      <p class="pageinput" id="tpl_modified">{$tpl->get_modified()|localedate_format:'%x %X'}</p>
    </div>
{/if}
{if !empty($user_list)}
    <div class="pageoverflow">
      <p class="pagetext"><label for="tpl_users">{$mod->Lang('prompt_owner')}:</label></p>
      <p class="pageinput" id="tpl_users">
        {$user_list[$tpl->get_owner_id()]}
      </p>
    </div>
{/if}
  </div>{* column *}
</fieldset>

<div class="pagewarning">{$mod->Lang('info_template_delete')}</div>
<br>
{form_start tpl=$actionparams.tpl __activetab='templates'}
<div class="pageoverflow">
  <p class="pageinput">
    <input id="check1" type="checkbox" name="{$actionid}check1" value="1">&nbsp;<label for="check1">{$mod->Lang('confirm_delete_template_1')}</label><br>
    <input id="check2" type="checkbox" name="{$actionid}check2" value="1">&nbsp;<label for="check2">{$mod->Lang('confirm_delete_template_2')}</label>
  </p>
</div>
<br>
<div class="pageoverflow">
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-minusthick" value="{$mod->Lang('remove')}">
    <input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
  </p>
</div>
{form_end}
