{* checksums creation and verification template *}
<div class="pagecontainer">
{if !empty($error)}
  <div class="red message no-slide" style="margin:1em">
   <div class="pageoverflow">
    <p>{$error}</p>
   </div>
  </div>
{/if}
{if !empty($message)}
  <div class="green success message">
   <div class="pageoverflow">
    <p>{$message}</p>
   </div>
  </div>
{/if}

  <form action="{$smarty.server.PHP_SELF}" method="post" enctype="multipart/form-data">
    <div class="hidden">
      <input type="hidden" name="{$securename}" value="{$secureval}">
      <input type="hidden" name="action" value="download">
    </div>
    <fieldset>
      <legend>{lang('download_cksum_file')}</legend>
      <p class="information">{lang('info_generate_cksum_file')}</p>
      <br>
      <div class="pageoverflow">
        <p class="pageinput"><input type="submit" data-ui-icon="ui-icon-arrowreturnthick-1-s" name="submit" value="{lang('export')}"></p>
      </div>
    </fieldset>
  </form>

  <form action="{$smarty.server.PHP_SELF}" method="post" enctype="multipart/form-data">
    <div class="hidden">
      <input type="hidden" name="{$securename}" value="{$secureval}">
      <input type="hidden" name="action" value="upload">
    </div>
    <fieldset>
      <legend>{lang('perform_validation')}</legend>
      <p class="information">{lang('info_validation')}</p>
      <div class="pageoverflow">
        <p class="pagetext"><label for="filesel">{lang('select_file')}</label></p>
        <p class="pageinput"><input type="file" id="filesel" name="cksumdat" size="30" accept=".dat"></p>
      </div>
      <br>
      <div class="pageoverflow">
        <p class="pageinput"><input type="submit" data-ui-icon="ui-icon-arrowthickstop-1-n" name="submit" value="{lang('upload_cksum_file')}"></p>
      </div>
    </fieldset>
  </form>
</div>
