{literal}
<script>
function showWYSIWYG(state) {
 var org = $('#editarea'),
  edbox, cntnt;
 if (state) {
  cntnt = org.val();
  org.css({'display':'none','aria-hidden':true});
  if (typeof tinymce !== 'undefined') {
   tinymce.activeEditor.setContent(cntnt);
   edbox = tinymce.activeEditor.container;
   $(edbox).css({'display':'flex','aria-hidden':false});
  } else { // TODO abstract from TMCE
  //migrate cntnt to editor
  }
 } else {
  if (typeof tinymce !== 'undefined') {
   tinymce.activeEditor.save();
   edbox = tinymce.activeEditor.container;
   $(edbox).css({'display':'none','aria-hidden':true});
  } else { // TODO abstract from TMCE
  //migrate editor content to #content
  }
  org.css({'display':'block','aria-hidden':false});
 }
}
$(function() {
{/literal}{if !$item->wysiwyg}{literal}
 var org = $('#editarea'),
  cntnt = org.val(),
  tid = setInterval(function() {
  var edbox = tinymce.activeEditor.container; //TODO with other editor, tinymce never defined
  if (edbox) {
   clearInterval(tid);
   showWYSIWYG(false);
   org.val(cntnt);
  }
 }, 250);
{/literal}{/if}{literal}
 $('#wysiwyg').on('change', function() {
  showWYSIWYG(this.checked);
 });
 $('.sortable-list').sortable({
  update: function(ev, ui) {
   $(ui.item).parent().children().each(function(index) {
    $(this).removeClass('row1 row2').addClass('row'+(index%2+1));
   });
  }
 }).css('cursor','move');
 $('[name={/literal}"{$actionid}apply"],[name="{$actionid}submit"]{literal}').on('click', function(e) {
  var val = $('#wysiwyg')[0].checked;
  if (!val) {
    //even so, the editor's content prevails in the submission, with
    //extra surrounding <p></p> for a 'root block' BAH
    var org = $('#editarea'),
     cntnt = org.val();
    tinymce.activeEditor.setContent(cntnt, {format:'raw',no_events:1});
  }
 });
});
</script>
{/literal}
<h3>{if $newitem}{$mod->Lang('add_item')}{else}{$mod->Lang('edit_item')}{/if}</h3>
{form_start gid=$item->id}
{tab_header name='props' label=$mod->Lang('tab_properties') active=$tab}
{tab_header name='content' label=$mod->Lang('tab_content') active=$tab}
  {tab_start name='props'}
  <div class="pageoverflow">
    <label class="pagetext" for="itemname">* {lang('name')}:</label><br>
    <input type="text" id="itemname" class="pageinput fatinput" name="{$actionid}name" value="{$item->name}" size="50">
  </div>
  <br>
  <div class="pageoverflow">
    {$t=$mod->Lang('revision')}<label class="pagetext" for="revision">{$t}:</label> {cms_help key2='help_proprevision' title=$t}<br>
    <input type="text" id="revision" class="pageinput fatinput" name="{$actionid}revision" value="{$item->revision}" size="32">
  </div>
  <br>
  <div class="pageoverflow">
    {$t=lang('author')}<label class="pagetext" for="author">{$t}:</label> {cms_help key2='help_propauthor' title=$t}<br>
    <input type="text" id="author" class="pageinput fatinput" name="{$actionid}author" value="{$item->author}" size="32" maxlength="64">
  </div>
  <br>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}active" value="0">
    <label class="pagetext" for="active">{lang('active')}:</label><br>
    <input type="checkbox" id="active" class="pageinput" name="{$actionid}active" value="1"{if $item->active} checked{/if}>
  </div>
  <br>
  <div class="pageoverflow">
    {$t=$mod->Lang('restricted')}<label class="pagetext" for="restrict">{$t}:</label> {cms_help key2='help_proprestrict' title=$t}<br>
    <select id="restrict" class="pageinput fatinput" name="{$actionid}restricted">
      {html_options options=$restrictions_list selected=$item->restricted}
    </select>
  </div>
  <br>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}search" value="0">
    <label class="pagetext" for="search">{$mod->Lang('searchable')}:</label><br>
    <input type="checkbox" id="search" class="pageinput" name="{$actionid}search" value="1"{if $item->search} checked{/if}>
  </div>
  <br>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}admin" value="0">
    {$t=$mod->Lang('admin')}<label class="pagetext" for="admin">{$t}:</label> {cms_help key2='help_propadmin' title=$t}<br>
    <input type="checkbox" class="pageinput" name="{$actionid}admin" id="admin" value="1"{if $item->admin} checked{/if}>
  </div>
  <br>
  <div class="pageoverflow">
{if $sheets_list}
    {$t=$mod->Lang('frontend_styles')}<label class="pagetext" for="allsheets">{$t}:</label> {cms_help key2='help_propstyles' title=$t}<br>
    <table id="allsheets" class="pagetable" style="width:max-content">
      <thead>
      <tr>
        <th>{lang('name')}</th>
        <th class="pageicon"></th>
      </tr>
      </thead>
      <tbody class="sortable-list">
{foreach $sheets_list as $one}       <tr class="{cycle values='row1,row2'}">
        <td>{$one.name}</td>
        <td class="pagepos"><input type="checkbox" name="{$actionid}sheets[]" value="{$one.id}"{if $one.checked} checked{/if}></td>
       </tr>
{/foreach}
      </tbody>
    </table>
{else}
    <p class="information">{$mod->Lang('no_sheet')}</p>
    <input type="hidden" name="{$actionid}sheets[]" value="">
{/if}
  </div>
  <br>
  <div class="pageoverflow">
{if $templates_list}
    {$t=$mod->Lang('frontend_template')}<label class="pagetext" for="template">{$t}:</label> {cms_help key2='help_proptemplate' title=$t}<br>
    <select id="template" class="pageinput fatinput" name="{$actionid}template_id">
      {html_options options=$templates_list selected=$item->template_id}
    </select>
{else}
    <p class="information">{$mod->Lang('no_template')}</p>
    <input type="hidden" name="{$actionid}template_id" value="0">
{/if}
  </div>
  <br>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}smarty" value="0">
    {$t=$mod->Lang('useSmarty')}<label class="pagetext" for="smarty">{$t}:</label> {cms_help key2='help_propsmarty' title=$t}<br>
    <input type="checkbox" id="smarty" class="pageinput" name="{$actionid}smarty" value="1"{if $item->smarty} checked{/if}>
  </div>
  <br>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}wysiwyg" value="0">
    <label class="pagetext" for="wysiwyg">{$mod->Lang('useWysiwyg')}:</label><br>
    <input type="checkbox" id="wysiwyg" class="pageinput" name="{$actionid}wysiwyg" value="1"{if $item->wysiwyg} checked{/if}>
  </div>
  {tab_start name='content'}
  <div class="pageoverflow">
    <div class="pageinput">
      {$input_content}
    </div>
  </div>
  {tab_end}
  <div class="pageoverflow">
    <div class="pageinput">
      <input type="submit" name="{$actionid}submit" value="{lang('submit')}">
      <input type="submit" name="{$actionid}cancel" value="{lang('cancel')}">
      <input type="submit" name="{$actionid}apply" data-ui-icon="ui-icon-disk" value="{lang('apply')}">
    </div>
  </div>
</form>
