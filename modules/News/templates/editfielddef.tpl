{if $showtypechooser}
<script>
$(function() {
  var typer = $('#fld_type');
  var val = typer.val();
  if (val == 'dropdown') {
   $('#area_options').show(100);
  } else if (val == 'textbox') {
   $('#area_maxlen').show(100);
  }
  typer.on('change', function() {
    switch (typer.val()) {
      case 'dropdown':
      $('#area_maxlen').hide(100);
      $('#area_options').show(200);
      break;
    case 'textbox':
      $('#area_options').hide(100);
      $('#area_maxlen').show(200);
      break;
    default:
      $('#area_maxlen,#area_options').hide(100);
    }
  });
  $('#{$actionid}cancel').on('click', function() {
    $(this).closest('form').attr('novalidate','novalidate');
  });
});
</script>
{/if}
<h3>{$title}</h3>
{if !$showtypechooser}<h4 style="margin:1.25em 0 0">{$mod->Lang($type)}</h4>{/if}
{$startform}
{if !$showtypechooser}
	<input type="hidden" name="{$actionid}type" value="{$type}">
{/if}
	<div class="pageoverflow">
		<p class="pagetext"><label for="fld_name">*{$nametext}:</label> {cms_help key='help_fielddef_name' title=$nametext}</p>
		<p class="pageinput">
			<input type="text" id="fld_name" name="{$actionid}name" value="{$name|cms_escape}" size="30" maxlength="255" required>
		</p>
	</div>
{if $showtypechooser}
	<div class="pageoverflow">
		<p class="pagetext"><label for="fld_type">*{$typetext}:</label> {cms_help key='help_fielddef_type' title=$typetext}</p>
		<p class="pageinput">
			<select id="fld_type" name="{$actionid}type">
				{html_options options=$fieldtypes selected=$type}
			</select>
		</p>
	</div>
{/if}
{if $showtypechooser || $type == 'dropdown'}
	<div class="pageoverflow" id="area_options"{if $showtypechooser} style="display:none"{/if}>
		<p class="pagetext"><label for="fld_options">{$mod->Lang('options')}:</label> {cms_help key='help_fielddef_options' title=$mod->Lang('options')}</p>
		<p class="pageinput">
			<textarea id="fld_options" name="{$actionid}options" rows="5" cols="80">{$options}</textarea>
		</p>
	</div>
{/if}
{if $showtypechooser || $type == 'textbox'}
	<div class="pageoverflow" id="area_maxlen"{if $showtypechooser} style="display:none"{/if}>
		<p class="pagetext"><label for="fld_maxlen">{$maxlengthtext}:</label> {cms_help key='help_fielddef_maxlen' title=$maxlengthtext}</p>
		<p class="pageinput">
			<input type="text" id="fld_maxlen" name="{$actionid}max_length" value="{$max_length|default:255}" size="5" maxlength="5">
		</p>
	</div>
{/if}
	<div class="pageoverflow">
		<p class="pagetext"><label for="fld_public">{$userviewtext}:</label> {cms_help key='help_fielddef_public' title=$userviewtext}</p>
		<div class="pageinput">
			<input type="hidden" name="{$actionid}public" value="0">
			<input type="checkbox" id="fld_public" name="{$actionid}public" value="1"{if $public} checked{/if}>
		</div>
	</div>
	<br>
	<div class="pageoverflow">
		<div class="pageinput">
			<input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}">
			<input type="submit" id="{$actionid}cancel" name="{$actionid}cancel" value="{$mod->Lang('cancel')}">
		</div>
	</div>
{$endform}
