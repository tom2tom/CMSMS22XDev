{$oformstart}
<div class="pageoverflow">
 <p class="pagetext"><label for="{$actionid}stopwords">{$prompt_stopwords}:</label></p>
 <p class="pageinput">{$input_stopwords|adjust:'html_entity_decode'}</p>
 <p class="pagetext"><label for="{$actionid}resettodefault">{$prompt_resetstopwords}:</label></p>
 <p class="pageinput">{$input_resetstopwords}</p>
</div>
<div class="pageoverflow">
 <p class="pagetext"><label for="chkstem">{$prompt_stemming}:</label></p>
 <p class="pageinput">{$input_stemming}</p>
</div>
<div class="pageoverflow">
 <p class="pagetext"><label for="{$actionid}searchtext">{$prompt_searchtext}:</label></p>
 <p class="pageinput">{$input_searchtext}</p>
</div>
<div class="pageoverflow">
 <p class="pagetext"><label for="chkphrased">{$prompt_savephrases}:</label></p>
 <p class="pageinput">{$input_savephrases}</p>
</div>
<div class="pageoverflow">
 <p class="pagetext"><label for="chkalpha">{$prompt_alpharesults}:</label></p>
 <p class="pageinput">{$input_alpharesults}</p>
</div>
<div class="pageoverflow">
 <p class="pagetext"><label for="cms_hierdropdown1_0">{$prompt_resultpage}:</label></p>
 <p class="pageinput">{page_selector id="selpage" name="{$actionid}resultpage" value=$mod->GetPreference('resultpage')}</p>
</div>
<br>
<div class="pageoverflow">
 <p class="pageinput">
  {$submit}
  <input type="submit" name="{$actionid}reindex" data-ui-icon="ui-icon-gear" value="{$mod->Lang('reindexallcontent')}" onclick="return confirm('{$mod->Lang("confirm_reindex")|escape:"javascript"}');">
 </p>
</div>
{$formend}
