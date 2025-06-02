<h3>{$mod->Lang('copy_stylesheet')}</h3>
<div class="information">{$mod->Lang('info_copy_css')}</div>
{form_start css=$actionparams.css __activetab='stylesheets'}
<fieldset>
  <legend>{$mod->Lang('prompt_source_css')}</legend>
{if $css->get_id() > 0}
  <div class="startside">
{/if}
    <div class="pageoverflow">
      <p class="pagetext"><label for="css_name">{$mod->Lang('prompt_name')}:</label></p>
      <p class="pageinput" id="css_name">{$css->get_name()}</p>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="css_dsn">{$mod->Lang('prompt_designs')}:</label></p>
      <p class="pageinput" id="css_dsn" style="max-height:8em;overflow:auto">
      {foreach $css->get_designs() as $design_id}
        {$design_names[$design_id]}<br>
      {/foreach}
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="css_desc">{$mod->Lang('prompt_description')}:</label></p>
      <p class="pageinput" id="css_desc" style="max-height:5em;overflow:auto">{$css->get_description()|summarize}</p>
    </div>
{if $css->get_id() > 0}
  </div>{* column *}
  <p class="startside" style="width:5%;min-width:1em"></p>
  <div class="startside last">
    <div class="pageoverflow">
      <p class="pagetext"><label for="css_created">{$mod->Lang('prompt_created')}:</label></p>
      <p class="pageinput" id="css_created">{$css->get_created()|localedate_format:'%x %X'}</p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="css_modified">{$mod->Lang('prompt_modified')}:</label></p>
      <p class="pageinput" id="css_modified">{$css->get_modified()|localedate_format:'%x %X'}</p>
      </p>
    </div>
  </div>{* column *}
{/if}
</fieldset>

<fieldset>
  <legend>{$mod->Lang('prompt_dest_css')}</legend>
  <div class="pageoverflow">
    <p class="pagetext"><label for="css_destname">*{$mod->Lang('prompt_name')}:</label></p>
    <p class="pageinput">
      <input type="text" id="css_destname" name="{$actionid}new_name" value="{$new_name|default:''}" size="50" maxlength="50">
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
