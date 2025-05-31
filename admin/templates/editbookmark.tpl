<div class="pagecontainer">
  {$header}
{if !empty($error)}
  <br>
  <p class="pageerror">{$error}</p>
  <br>
{/if}
  <form action="editbookmark.php" method="post">
    <input type="hidden" name="{$hiddenname}" value="{$hiddenval}">
    <input type="hidden" name="bookmark_id" value="{$bookmark_id}">
    <input type="hidden" name="userid" value="{$userid}">
   
    <div class="pageoverflow">
      <p class="pagetext"><label for="marktitl">{lang('title')}:</label></p>
      <p class="pageinput"><input type="text" id="marktitl" name="title" size="50" maxlength="255" value="{$title}"></p>
    </div>
    <div class="pageoverflow">
      {$t=lang('url')}<p class="pagetext"><label for="markurl">{$t}:</label>&nbsp;{cms_help key2='help_bookmark_url' title=$t}</p>
      <p class="pageinput"><input type="text" id="markurl" class="standard" name="url" size="70" maxlength="255" value="{$url}"></p>
    </div>
    <br>
    <div class="pageoverflow">
      <p class="pageinput">
        <input type="submit" class="pagebutton" name="editbookmark" value="{lang('submit')}">
        <input type="submit" class="pagebutton" name="cancel" value="{lang('cancel')}">
      </p>
    </div>
  </form>
</div>
