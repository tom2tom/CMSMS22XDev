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
            <th style="width:25%">{$mod->Lang('count')}</th>
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
      <div class="pageinput">
        <input type="submit" name="{$actionid}clearwordcount" data-ui-icon="ui-icon-minusthick" value="{lang('clear')}" onclick="return confirm('{$mod->Lang('confirm_clearstats')|escape:'javascript'}');">
        <input type="submit" name="{$actionid}exportcsv" data-ui-icon="ui-icon-arrowreturnthick-1-s" value="{$mod->Lang('export_to_csv')}">
      </div>
    </div>
  {$formend}
{else}
  <div class="information">{$mod->Lang('nostatistics')}</div>
{/if}
{tab_start name='settings'}
  {$oformstart}
    <div class="pageoverflow">
      <p class="pagetext"><label for="stopwords">{$mod->Lang('stopwords')}:</label></p>
      <p class="pageinput"><textarea id="stopwords" name="{$actionid}stopwords" cols="50" rows="6">{$current_stopwords|adjust:'html_entity_decode'}</textarea></p>
      <p class="pagetext"><label for="resettodefault">{$mod->Lang('prompt_resetstopwords')}:</label></p>
      <p class="pageinput"><input type="submit" id="resettodefault" name="{$actionid}resettodefault" data-ui-icon="ui-icon-refresh" value="{$mod->Lang('input_resetstopwords')}"></p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="chkstem">{$mod->Lang('usestemming')}:</label></p>
      <p class="pageinput"><input type="checkbox" id="chkstem" name="{$actionid}usestemming" value="1"{if $use_stemming} checked{/if}></p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="searchtext">{$mod->Lang('prompt_searchtext')}:</label></p>
      <p class="pageinput"><input type="text" id="searchtext" name="{$actionid}searchtext" value="{$search_text}" size="15" maxlength="64"></p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="chkphrased">{$mod->Lang('prompt_savephrases')}:</label></p>
      <p class="pageinput"><input type="checkbox" id="chkphrased" name="{$actionid}savephrases" value="1"{if $save_phrases} checked{/if}></p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="chkalpha">{$mod->Lang('prompt_alpharesults')}:</label></p>
      <p class="pageinput"><input type="checkbox" id="chkalpha" name="{$actionid}alpharesults" value="1"{if $alpha_results} checked{/if}></p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="cms_hierdropdown1_0">{$mod->Lang('prompt_resultpage')}:</label></p>
      <p class="pageinput">{page_selector id=selpage name="{$actionid}resultpage" value=$mod->GetPreference('resultpage',-1)}</p>
    </div>
    <br>
    <div class="pageoverflow">
      <div class="pageinput startside last">
        <input type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-check" value="{lang('submit')}">
        <input type="submit" name="{$actionid}cancel" data-ui-icon="ui-icon-cancel" value="{lang('cancel')}">
      </div>
      <p class="pageinput endside last">
        <input type="submit" name="{$actionid}reindex" data-ui-icon="ui-icon-gear" value="{$mod->Lang('reindexallcontent')}" onclick="return confirm('{$mod->Lang('confirm_reindex')|escape:'javascript'}');">
      </p>
    </div>
  {$formend}
{tab_end}
