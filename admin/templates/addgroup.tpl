<div class="pagecontainer">
{if $error}
  <div class="pageerrorcontainer">
    <ul class="pageerror">
      {$error}
    </ul>
  </div>
  <br>
{/if}
  <p class="pagewarning">{lang('warn_addgroup')}</p>
  <br>
  <form action="addgroup.php" method="post">
    <input type="hidden" name="{$securename}" value="{$secureval}">
    <div class="pageoverflow">
      <p class="pagetext"><label for="txtgroup">{lang('name')}:</label></p>
      <p class="pageinput"><input type="text" id="txtgroup" name="group" maxlength="75" size="25" value="{$group}"></p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="txtdesc">{lang('description')}:</label></p>
      <p class="pageinput"><input type="text" id="txtdesc" name="description" maxlength="255" size="80" value="{$description}"></p>
    </div>
    <div class="pageoverflow">
      <input type="hidden" name="active" value="0">
      <p class="pagetext"><label for="chkactive">{lang('active')}:</label></p>
      <p class="pageinput"><input type="checkbox" id="chkactive" name="active" class="pagecheckbox" value="1"{if $active} checked{/if}></p>
    </div>
    <br>
    <div class="pageinput">
      <input type="submit" name="addgroup" value="{lang('submit')}">
      <input type="submit" name="cancel" value="{lang('cancel')}">
    </div>
  </form>
</div>
