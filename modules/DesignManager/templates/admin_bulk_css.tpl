<h3>{$mod->Lang($bulk_op)}</h3>

{if !empty($templates)}
<table class="pagetable">
  <thead>
   <tr>
     <th>{$mod->Lang('prompt_id')}</th>
     <th>{$mod->Lang('prompt_name')}</th>
     <th>{$mod->Lang('prompt_modified')}</th>
   </tr>
  </thead>
  <tbody>
  {foreach $templates as $tpl}
    <tr>
      <td>{$tpl->get_id()}</td>
      <td>{$tpl->get_name()}</td>
      <td>{$tpl->get_modified()|localedate_format:'%x %X'}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
<br>
{/if}

{form_start allparms=$allparms}
{if $bulk_op == 'bulk_action_delete_css'}
  <p class="pagewarning">{$mod->Lang('warn_bulk_delete_sheets')}</p>
  <br>
  <div class="pageoverflow">
    <p class="pageinput">
      <input id="check1" type="checkbox" name="{$actionid}check1" value="1">&nbsp;<label for="check1">{$mod->Lang('confirm_bulk_css_1')}</label><br>
      <input id="check2" type="checkbox" name="{$actionid}check2" value="1">&nbsp;<label for="check2">{$mod->Lang('confirm_bulk_css_2')}</label>
    </p>
  </div>
  <br>
{/if}
<div class="pageoverflow">
  <p class="pageinput">
{if $bulk_op == 'bulk_action_delete_css'}
    <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-minusthick" value="{$mod->Lang('remove')}">
{else}
    <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}">
{/if}
    <input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
  </p>
</div>
{form_end}
