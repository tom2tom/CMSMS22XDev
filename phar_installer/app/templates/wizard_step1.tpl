{* wizard step 1 *}
{extends file='wizard_step.tpl'}

{block name='logic'}
    {capture assign='browser_title'}CMS Made Simple&trade; {$version|default:''} ({$version_name|default:''}) {tr('apptitle')}{/capture}
    {capture assign='title'}{tr('title_welcome_full',{$version|default:''})} <em>({$version_name|default:''})</em><br>{tr('apptitle')}{/capture}
    {$current_step = '1'}
{/block}

{block name='contents'}
<script>
function redirect_langchange() {
  var e = document.getElementById('lang_selector');
  var v = e.options[e.selectedIndex].value;
  var url = window.location.origin + window.location.pathname + '?curlang=' + v;
  window.location.href = url;
  return false;
}
</script>

<p>{tr('welcome_message')}</p>

<div class="installer-form">
{wizard_form_start}
    {if empty($custom_destdir) && !empty($dirlist)}
      <h3>{tr('step1_destdir')}</h3>

      <p class="message yellow">{tr('step1_info_destdir')}</p>

      <div class="row message yellow">
        <label for="dirsel">{tr('destination_directory')}:</label>
        <select id="dirsel" class="form-field" name="destdir">
          {html_options options=$dirlist selected=$destdir|default:''}
        </select>
      </div>
      <hr>
    {/if}

    <h3>{tr('step1_language')}</h3>
    <p class="info">{tr('select_language')}</p>
    <div class="row">
        <label for="lang_selector">{tr('available_languages')}:</label>
        <select id="lang_selector" class="form-field" name="lang" onchange="redirect_langchange();">
            {html_options options=$languages selected=$curlang}
        </select>
    </div>

    <hr>

    <h3>{tr('step1_advanced')}</h3>
    <p class="info">{tr('info_advanced')}</p>

    <div class="row">
        <label for="advsel">{tr('advanced_mode')}:</label>
        <select id="advsel" class="form-field" name="verbose">
            {html_options options=$yesno selected=$verbose}
        </select>
    </div>

    <div id="bottom_nav">
      <input type="submit" class="action-button positive" name="next" value="{tr('next')} &rarr;">
    </div>
{wizard_form_end}
</div>
{/block}
