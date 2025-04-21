{tab_header name='statistics' label=$mod->Lang('statistics') active=$tab}
{tab_header name='settings' label=$mod->Lang('settings') active=$tab}
{tab_start name='statistics'}
{if !empty($topwords)}
{$sformstart}
<div class="pageoverflow">
  <table class="pagetable">
    <thead>
      <tr>
        <th style="width:75%">{$wordtext}</th>
        <th style="width:25%">{$counttext}</th>
      </tr>
    </thead>
    <tbody>
{foreach $topwords as $entry}{cycle values='row1,row2' assign='rowclass'}
      <tr class="{$rowclass}">
        <td>{$entry.word}</td>
        <td>{$entry.count}</td>
      </tr>
{/foreach}
    </tbody>
  </table>
</div>
<br>
<div class="pageoverflow">
  <p class="pageinput">
    {$clearwordcount}&nbsp;{$exportcsv}
  </p>
</div>
{$formend}
{else}
<div class="information">{$mod->Lang('nostatistics')}</div>
{/if}
{tab_start name='settings'}
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
 <p class="pageinput">{page_selector id=selpage name="{$actionid}resultpage" value=$mod->GetPreference('resultpage',-1)}</p>
</div>
<br>
<div class="pageoverflow">
 <p class="pageinput startside last">
  <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-check" value="{lang('submit')}">
  <input type="submit" name="{$actionid}cancel" data-ui-icon="ui-icon-cancel" value="{lang('cancel')}">
 </p>
 <p class="pageinput endside last">
  <input type="submit" name="{$actionid}reindex" data-ui-icon="ui-icon-gear" value="{$mod->Lang('reindexallcontent')}" onclick="return confirm('{$mod->Lang("confirm_reindex")|escape:"javascript"}');">
 </p>
</div>
{$formend}
{tab_end}
