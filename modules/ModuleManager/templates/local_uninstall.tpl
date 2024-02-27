<h3>{$ModuleManager->Lang('title_uninstall_module')}</h3>
<h4>{$ModuleManager->Lang('lbl_module')}: {$module_name}</h4>
<h4>{$ModuleManager->Lang('lbl_version')}: {$module_version}</h4>
<div class="warning">{$msg}</div>
{form_start mod=$module_name}
<input type="hidden" name="{$actionid}confirm" value="0">
<div class="pageoverflow">
  <p class="pageinput">
   <input type="checkbox" id="confirm" name="{$actionid}confirm" value="1">
   <label for="confirm">{$ModuleManager->Lang('confirm_action')}</label>
  </p>
</div>
<div class="pageoverflow">
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-circle-minus" value="{$ModuleManager->Lang('uninstall')}">
    <input type="submit" name="{$actionid}cancel" value="{$ModuleManager->Lang('cancel')}">
  </p>
</div>
{form_end}
