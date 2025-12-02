<div class="pagecontainer">
  {$header}
{if !empty($error)}
  <br>
  <p class="pageerror">{$error}</p>
  <br>
{/if}
  <form action="addbookmark.php" method="post">
    <input type="hidden" name="{$hiddenname}" value="{$hiddenval}">

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
      <div class="pageinput">
        <input type="submit" name="addbookmark" value="{lang('submit')}">
        <input type="submit" name="cancel" value="{lang('cancel')}">
      </div>
    </div>
  </form>
</div>
