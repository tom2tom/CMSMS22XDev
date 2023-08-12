<h3>{$ModuleManager->Lang('title_uninstall_module')}</h3>
<h4>{$ModuleManager->Lang('lbl_module')}: {$module_name}</h4>
<h4>{$ModuleManager->Lang('lbl_version')}: {$module_version}</h4>
<div class="red">{$msg}</div>
{form_start mod=$module_name}
<div class="pageoverflow">
  <p class="pageinput">
    <label>
      <input type="checkbox" name="{$actionid}confirm" value="1"> {$ModuleManager->Lang('confirm_action')}
    </label>
  </p>
</div>
<div class="pageoverflow">
  <p class="pageinput">
    <input type="submit" name="{$actionid}submit" value="{$ModuleManager->Lang('uninstall')}">
    <input type="submit" name="{$actionid}cancel" value="{$ModuleManager->Lang('cancel')}">
  </p>
</div>
{form_end}
