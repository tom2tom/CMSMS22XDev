{if !empty($slaves)}
<script>
var ajax_url = '{$ajax_url}'; // used in external script
{if isset($saved_search.slaves) && in_array(-1,$saved_search.slaves)}
var sel_all = 1; // used in external script
{/if}
function active_slave() {
 var l = $('#filter_box :checkbox.filter_toggle:checked').length;
 return l > 0;
}
$(function() {
 $('#filter_box :checkbox.filter_toggle').on('click',function() {
  $('#searchbtn').prop('disabled',!active_slave());
 });
 $('#filter_all').on('click',function() {
  $('#filter_box :checkbox.filter_toggle').prop('checked',this.checked);
  $('#searchbtn').prop('disabled',!this.checked);
 });
 $('#searchbtn').prop('disabled',!active_slave()).on('click',function() {
  if (active_slave()) {
   $('#searchresults').html('');
  } else {
   cms_alert("{$mod->Lang('error_select_slave')|escape:'javascript'}");
  }
 });
});
</script>
<script src="{$js_url}"></script>

<div id="adminsearchform">
 {$formstart}

 <table class="pagetable">
  <tbody>
   <tr style="vertical-align:top">
    <td style="width:50%">
     <div class="pageoverflow">
      <p class="pagetext"><label for="searchtext">{$mod->Lang('search_text')}:</label></p>
      <p class="pageinput">
       <input id="searchtext" type="text" name="{$actionid}search_text" value="{$saved_search.search_text|default:''|cms_escape}" size="80" maxlength="80" placeholder="{$mod->Lang('placeholder_search_text')}">
      </p>
     </div>
     <br>
     <div class="pageoverflow">
      <p class="pageinput">
       <input id="searchbtn" type="submit" name="{$actionid}submit" data-ui-icon="ui-icon-search" value="{$mod->Lang('search')}">
      </p>
     </div>
    </td>
    <td style="width:50%">
     <div class="pageoverflow" id="filter_box">
      <p class="pagetext">{$mod->Lang('filter')}:</p>
      <p class="pageinput">
       <input id="filter_all" type="checkbox" name="{$actionid}slaves[]" value="-1">&nbsp;<label for="filter_all" title="{$mod->Lang('desc_filter_all')}">{$mod->Lang('all')}</label><br>
{foreach $slaves as $slave}
       <input class="filter_toggle" id="{$slave.class}" type="checkbox" name="{$actionid}slaves[]" value="{$slave.class}"{if isset($saved_search.slaves) && in_array($slave.class,$saved_search.slaves)} checked{/if}>&nbsp;<label for="{$slave.class}" title="{$slave.description}">{$slave.name}</label>{if !$slave@last}<br>{/if}
{/foreach}
       <br><br>
       <input type="checkbox" id="include_inactive_items" name="{$actionid}include_inactive_items" value="1" checked>&nbsp;<label for="include_inactive_items">{$mod->lang('lbl_include_inactive_items')}</label><br>
       <input type="checkbox" id="search_desc" name="{$actionid}search_descriptions" value="1" checked>&nbsp;<label for="search_desc">{$mod->lang('lbl_search_desc')}</label><br>
       <input type="checkbox" id="search_case" name="{$actionid}search_casesensitive" value="1">&nbsp;<label for="search_case">{$mod->lang('lbl_search_casesensitive')}</label><br>
       <input type="checkbox" id="search_snippets" name="{$actionid}show_snippets" value="1">&nbsp;<label for="search_snippets">{$mod->lang('lbl_show_snippets')}</label>
      </p>
      <br>
     </div>
    </td>
   </tr>
  </tbody>
 </table>

 <div class="pageoverflow" id="progress_area"></div>
 <div class="pageoverflow" id="status_area"></div>
 <fieldset id="searchresults_cont">
  <legend>{$mod->Lang('search_results')}</legend>
  <div id="searchresults_cont2">
   <ul id="searchresults">
   </ul>
  </div>
 </fieldset>
 {$formend}
</div>

<iframe id="workarea" name="workarea"></iframe>
{else}
<div class="pageoverflow">
 <p class="pagetext">{$mod->Lang('empty_list')}</p>
</div>
{/if}
