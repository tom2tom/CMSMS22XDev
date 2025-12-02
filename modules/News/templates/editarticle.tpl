<script>
  function escapeHtml(text) {
    //'&' ignored - no double-escaping
    var subs = {
      '"': '&quot;',
      "'": '&#039;',
      '<': '&lt;',
      '>': '&gt;',
      '\\': ''
    };
    return text.replace(/["'<>\\]/g, function(m) { return subs[m]; });
  }
{if isset($tab_preview)}
  function news_dopreview() {
    if (typeof tinymce !== 'undefined') {
      tinymce.triggerSave();
    }

    var fm = $('form'),
     url = fm.attr('action'),
     fdata = new FormData(fm[0]);
       fdata.append("{$actionid}preview", 1);
       fdata.append("{$actionid}ajax", 1);
       fdata.append('showtemplate', 'false');

    $.ajax(url, {
      method: 'POST',
      data:  fdata,
      contentType: false,
      processData: false,
      dataType: 'json'
    }).done(function(resultdata) {
      var details = resultdata.details;
      if (resultdata.response === 'Success' && details) {
        // preview worked... details should contain the url
        details = details.replace(/amp;/g,'');
        $('#previewframe').attr('src',details);
      } else {
        // preview save did not work
        var list, out, tid = 0;
        if (details) {
          list = details.constructor === Array;
          if (list) {
            out = '';
            for (var i = 0; i < details.length; ++i) {
              out += '<li>' + escapeHtml(details[i]) + '</li>';
            }
          } else {
            details = escapeHtml(details);
          }
        } else {
          list = false;
          details = "{$mod->Lang('error_unknown')|adjust:'htmlspecialchars':(ENT_QUOTES+ENT_SUBSTITUTE):'UTF-8':false}";
        }
        //TODO do not hardcode OneEleven-theme style notification
        var tip = "{$mod->Lang('close')|adjust:'htmlspecialchars':(ENT_QUOTES+ENT_SUBSTITUTE):'UTF-8':false}";
        var htmlShow = '<div class="pageerrorcontainer">' +
         '<span id="resultcloser" class="close-warning" title="' + tip + ' "></span>';
        if (list) {
          htmlShow += '<ul class="pageerror">' + out + '</ul></div>';
        } else {
          htmlShow += '<p class="pageerror">' + details + '</p></div>';
        }
        $('#editarticle_result').html(htmlShow).slideDown(600);
        $('#resultcloser').on('click', function(e) {
          if (tid > 0) {
            clearTimeout(tid);
          }
          e.preventDefault();
          $('#editarticle_result').slideUp(600, function() {
            $(this).empty();
          });
        });
        tid = setTimeout(function() {
          tid = 0;
          $('#editarticle_result').slideUp(1500, function() {
            $(this).empty();
          });
        }, 4000);
      }
    }).fail(function(jqXHR, textStatus, error) {
      console.debug(error);
    });
  }
{/if}
  $(function() {
    $("[name='{$actionid}submit'],[name='{$actionid}apply']").hide().on('click', function() {
      $('#edit_news').dirtyForm('option', 'disabled', true);
    });
    $("[name='{$actionid}cancel']").on('click', function() {
      $('#edit_news').dirtyForm('option', 'disabled', true);
      $(this).closest('form').attr('novalidate', 'novalidate');
    });
    $('#edit_news').dirtyForm({
      onDirty : function() {
        $("[name='{$actionid}apply'],[name='{$actionid}submit']").show('slow');
      }
    });
    $(document).on('cmsms_textchange', function() {
      // editor text change, set the form dirty.
      $('#edit_news').dirtyForm('option', 'dirty', true);
    });
    $('#fld4').on('click', function() {
      $('#expiryinfo').toggle('slow');
    });
{if isset($articleid)}
    $("[name='{$actionid}apply']").on('click', function(e) {
      e.preventDefault();
      if (typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
      }
      var fm =  $('form'),
       url = fm.attr('action'),
       fdata = new FormData(fm[0]);
       fdata.set("{$actionid}apply", "{$mod->Lang('apply')}");
       fdata.append("{$actionid}ajax", 1);
       fdata.append('showtemplate', 'false');

      $.ajax(url, {
        method: 'POST',
        data: fdata,
        contentType: false,
        processData: false,
        dataType: 'json'
      }).done(function(resultdata) {
        var htmlShow, details, list, tip, tid = 0;
        if (resultdata) {
          tip = "{$mod->Lang('close')|adjust:'htmlspecialchars':(ENT_QUOTES+ENT_SUBSTITUTE):'UTF-8':false}";
          details = resultdata.details;
          if (resultdata.response === 'Success') {
            if (details) {
              details = escapeHtml(details);
            } else {
              details = "{$mod->Lang('articleupdated')|adjust:'htmlspecialchars':(ENT_QUOTES+ENT_SUBSTITUTE):'UTF-8':false}";
            }
            htmlShow = '<div class="pagemcontainer">' +
            '<span id="resultcloser" class="close-warning" title="' + tip + '"></span>' +
            '<p class="pagemessage">' + details + '</p></div>';
          } else {
            var out;
            if (details) {
              list = details.constructor === Array;
              if (list) {
                out = '';
                for (var i = 0; i < details.length; ++i) {
                  out += '<li>' + escapeHtml(details[i]) + '</li>';
                }
              } else {
                details = escapeHtml(details);
              }
            } else {
              list = false;
              details = "{$mod->Lang('error_unknown')|adjust:'htmlspecialchars':(ENT_QUOTES+ENT_SUBSTITUTE):'UTF-8':false}";
            }
            htmlShow = '<div class="pageerrorcontainer">' +
            '<span id="resultcloser" class="close-warning" title="' + tip + '"></span>';
            if (list) {
              htmlShow += '<ul class="pageerror">' + out + '</ul></div>';
            } else {
              htmlShow += '<p class="pageerror">' + details + '</p></div>';
            }
          }
        } else {
          details = "{lang('error_internal')|adjust:'htmlspecialchars':(ENT_QUOTES+ENT_SUBSTITUTE):'UTF-8':false}";
          htmlShow = '<div class="pageerrorcontainer">' +
          '<span id="resultcloser" class="close-warning" title="' + tip + '"></span>' +
          '<p class="pageerror">' + details + '</p></div>';
        }
        $('#editarticle_result').html(htmlShow).slideDown(600);
        $('#resultcloser').on('click', function(e) {
          if (tid > 0) {
            clearTimeout(tid);
          }
          e.preventDefault();
          $('#editarticle_result').slideUp(600, function() {
            $(this).empty();
          });
        });
        tid = setTimeout(function() {
          tid = 0;
          $('#editarticle_result').slideUp(1500, function() {
            $(this).empty();
          });
        }, 4000);
      }).fail(function(jqXHR, textStatus, errorThrown) {
        console.debug('AJAX error: ' + errorThrown);
      });
    });
    $('input[name="preview_returnid"],#preview_template').on('change', function(e) {
      e.preventDefault();
      news_dopreview();
    });
    $('#preview').on('click', function(e) {
      e.preventDefault();
      news_dopreview();
    });
{/if}
  });
</script>
<h3>{if isset($articleid)}{$mod->Lang('editarticle')}{else}{$mod->Lang('addarticle')}{/if}</h3>

<div id="editarticle_result" style="display:none"></div>

<div id="edit_news">
  {$startform}
  <div class="pageoverflow">
    <div class="pageinput">
      <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}">
      &nbsp;<input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
{if isset($articleid)}
      &nbsp;<input type="submit" name="{$actionid}apply" data-ui-icon="ui-icon-caret-1-n" value="{$mod->Lang('apply')}">
{/if}
    </div>
  </div>
  {strip}
{if isset($tab_preview)}
  {tab_header name='article' label=$mod->Lang('article')}
  {tab_header name='preview' label=$mod->Lang('preview')}
  {tab_start name='article'}
{/if}
  <div id="edit_article">
    {if $inputauthor}
    <div class="pageoverflow">
      <p class="pagetext">
        <label>{$authortext}:</label>
      </p>
      <div class="pageinput">
        {$inputauthor}
      </div>
    </div>
    {/if}
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="fld1">*{$titletext}:</label> {cms_help key='help_article_title' title=$titletext}
      </p>
      <div class="pageinput">
        <input type="text" id="fld1" name="{$actionid}title" value="{$title|escape:htmlall}" size="80" maxlength="255" required>
      </div>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="fld2">*{$categorytext}:</label> {cms_help key='help_article_category' title=$categorytext}
      </p>
      <div class="pageinput">
        <select name="{$actionid}category" id="fld2">
          {html_options options=$categorylist selected=$category}
        </select>
      </div>
    </div>
    {if empty($hide_summary_field)}
    <div class="pageoverflow">
      <p class="pagetext">
        <label>{$summarytext}:</label> {cms_help key='help_article_summary' title=$summarytext}
      </p>
      <div class="pageinput">
        {$inputsummary}{*no id attr for TMCE-related elements*}
      </div>
    </div>
    {/if}
    <div class="pageoverflow">
      <p class="pagetext">
        <label>*{$contenttext}:</label> {cms_help key='help_article_content' title=$contenttext}
      </p>
      <div class="pageinput">
        {$inputcontent}{*no id attr for TMCE-related elements*}
      </div>
    </div>
    {if !empty($statustext)}
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="fld3">*{$statustext}:</label> {cms_help key='help_article_status' title=$statustext}
      </p>
      <div class="pageinput">
        <select id="fld3" name="{$actionid}status">
          {html_options options=$statuses selected=$status}
        </select>
      </div>
    </div>
    {else}
    <input type="hidden" name="{$actionid}status" value="{$status}">
    {/if}
    <div class="pageoverflow">
      <p class="pagetext">
        <label>{$postdatetext}:</label> {cms_help key='help_article_postdate' title=$postdatetext}
      </p>
      <div class="pageinput">{*no id attr for datetime selector elements*}
        {html_select_date prefix=$postdateprefix time=$postdate start_year='1980' end_year='+15'} {html_select_time prefix=$postdateprefix time=$postdate}
      </div>
    </div>
    <div class="pageoverflow">
      <input type="hidden" name="{$actionid}useexp" value="0">
      <p class="pagetext">
        <label for="fld4">{$useexpirationtext}:</label> {cms_help key='help_article_useexpiry' title=$useexpirationtext}
      </p>
      <div class="pageinput">
        <input id="fld4" type="checkbox" name="{$actionid}useexp"{if $useexp} checked{/if} class="pagecheckbox">
      </div>
    </div>
    <div id="expiryinfo"{if $useexp != 1} style="display:none"{/if}>
      <div class="pageoverflow">
        <p class="pagetext">
          <label>{$startdatetext}:</label> {cms_help key='help_article_startdate' title=$startdatetext}
        </p>
        <div class="pageinput">{*no id attr for datetime selector elements*}
          {html_select_date prefix=$startdateprefix time=$startdate start_year="-10" end_year="+15"} {html_select_time prefix=$startdateprefix time=$startdate}
        </div>
      </div>
      <div class="pageoverflow">
        <p class="pagetext">
          <label>{$enddatetext}:</label> {cms_help key='help_article_enddate' title=$enddatetext}
        </p>
        <div class="pageinput">{*no id attr for datetime selector elements*}
          {html_select_date prefix=$enddateprefix time=$enddate start_year="-10" end_year="+15"} {html_select_time prefix=$enddateprefix time=$enddate}
        </div>
      </div>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="{$imageinputid}">{$imagetext}:</label> {cms_help key='help_article_image' title=$imagetext}
      </p>
      <div class="pageinput">
        {$imageinput}
      </div>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="fld7">{$urltext}:</label> {cms_help key='help_article_url' title=$urltext}
      </p>
      <div class="pageinput">
        <input type="text" id="fld7" name="{$actionid}news_url" value="{$news_url}" size="50" maxlength="255">
      </div>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="fld9">{$extratext}:</label> {cms_help key='help_article_extra' title=$extratext}
      </p>
      <div class="pageinput">
        <input type="text" id="fld9" name="{$actionid}extra" value="{$extra|cms_escape}" size="50" maxlength="255">
      </div>
    </div>
    <div class="pageoverflow">
      <input type="hidden" name="{$actionid}searchable" value="0">
      <p class="pagetext">
        <label for="searchable">{$mod->Lang('searchable')}:</label> {cms_help key='help_article_searchable' title=$mod->Lang('searchable')}
      </p>
      <div class="pageinput">
        <input id="searchable" type="checkbox" name="{$actionid}searchable"{if $searchable} checked{/if} class="pagecheckbox">
      </div>
    </div>
    {if !empty($custom_fields)}
    <fieldset>
    <legend>{$mod->Lang('customfields')}</legend>
    {foreach $custom_fields as $field}
      <div class="pageoverflow">
        <p class="pagetext">
          <label for="{$field->idattr}">{$field->prompt|cms_escape}:</label>
        </p>
        <div class="pageinput">{$usit = $field->value != false}
        {if $field->type == 'textbox'}
          <input type="text" id="{$field->idattr}" name="{$field->nameattr}" value="{$field->value}" size="{$field->size}" maxlength="{$field->max_len}">
        {elseif $field->type == 'checkbox'}
          {$usit=false}{*no sensible default for this type*}
          <input type="hidden" name="{$field->nameattr}" value="0">
          <input type="checkbox" id="{$field->idattr}" name="{$field->nameattr}" value="1"{if $field->value} checked{/if}>
        {elseif $field->type == 'textarea'}
          {cms_textarea id=$field->idattr name=$field->nameattr enablewysiwyg=1 rows=5 cols=50 value=$field->value}
        {elseif $field->type == 'file'}
          {if !empty($field->value)}
          <input type="hidden" name="{$actionid}currentfile[{$field->id}]" value="{$field->value}">
          {$fn=rawurlencode(basename($field->value))}{$mod->Lang('current')}: {$fn}<br>
          {/if}
          <input type="file" id="{$field->idattr}" name="{$field->nameattr}" accept="image/*">
        {elseif $field->type == 'dropdown'}
          {$usit = !($field->value == '' || $field->value == -1)}
          <select id="{$field->idattr}" name="{$field->nameattr}">
            <option value="-1">{$select_option}</option>
            {html_options options=$field->options selected=$field->value}
          </select>
        {elseif $field->type == 'linkedfile'}
          {cms_filepicker name="{$field->nameattr}" value=$field->value}
        {/if}
         <br>
         <label for="uf{$field->id}">{$mod->Lang('usefield')}</label>&nbsp;
         <input type="checkbox" id="uf{$field->id}" name="{$actionid}usefield[{$field->id}]" value="1"{if $usit} checked{/if}>
        </div>
      </div>
    {/foreach}
    </fieldset>
    {/if}
  </div>
{/strip}
{if isset($tab_preview)}
  {tab_start name='preview'}
  {strip}
  {* display a warning *}
  <div class="pagewarning">
    {$warning_preview}
  </div>
  <fieldset>
    <label for="preview_template">{$mod->Lang('detail_template')}:</label>&nbsp;
    <select id="preview_template" name="{$actionid}detailtemplate">
      {html_options options=$detail_templates selected=$cur_detail_template}
    </select>
    <label for="cms_hierdropdown1_0">{$mod->Lang('detail_page')}:</label>&nbsp;{$preview_page_selector}
  </fieldset>
  <br>
  <iframe id="previewframe" class="preview" style="height:50em;width:100%;overflow:auto"></iframe>
{/strip}
  {tab_end}
{/if}
  <div class="pageoverflow">
    <div class="pageinput">
      <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}">
      &nbsp;<input type="submit" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
{if isset($articleid)}
      &nbsp;<input type="submit" name="{$actionid}apply" data-ui-icon="ui-icon-caret-1-n" value="{$mod->Lang('apply')}">
{/if}
    </div>
  </div>
  {$endform}
</div>
