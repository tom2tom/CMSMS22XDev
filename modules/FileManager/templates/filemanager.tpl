{if !isset($noform)}{*TODO <style/> invalid here - migrate to <head/> or external .css*}
<style>
a.filelink:visited {
  color: #000;
}
</style>
<script>
function enable_button(idlist) {
    $(idlist).prop('disabled',false).removeClass('ui-state-disabled ui-button-disabled');
}
function disable_button(idlist) {
    $(idlist).prop('disabled',true).addClass('ui-state-disabled ui-button-disabled');
}
function enable_action_buttons() {
    var files = $('#filesarea input[type="checkbox"].fileselect').filter(':checked').length,
        dirs = $('#filesarea input[type="checkbox"].dir').filter(':checked').length,
        arch = $('#filesarea input[type="checkbox"].archive').filter(':checked').length,
        text = $('#filesarea input[type="checkbox"].text').filter(':checked').length,
        imgs = $('#filesarea input[type="checkbox"].image').filter(':checked').length;

    disable_button('button.filebtn');
    $('button.filebtn').prop('disabled',true);
    if (files === 0 && dirs === 0) {
        // nothing selected, enable anything with select_none
        enable_button('#btn_newdir');
    } else if (files === 1) {
        // 1 selected, enable anything with select_one
        enable_button('#btn_rename');
        enable_button('#btn_move');
        enable_button('#btn_delete');

        if (dirs === 0) enable_button('#btn_copy');
        if (arch === 1) enable_button('#btn_unpack');
        if (imgs === 1) enable_button('#btn_view,#btn_thumb,#btn_resizecrop,#btn_rotate');
        if (text === 1) enable_button('#btn_view');
    } else if (files > 1 && dirs == 0) {
        // multiple files selected
        enable_button('#btn_delete,#btn_copy,#btn_move');
    } else if (files > 1 && dirs > 0) {
        // multiple selected, at least one dir.
        enable_button('#btn_delete,#btn_move');
    }
}

$(function () {
    enable_action_buttons();

    $('#refresh').off('click').on('click', function(e) {
        // ajaxy reload for the files area.
//        var url = '{$refresh_url}'+'&showtemplate=false'; needed?
//        $('#filesarea').load(url);
        $('#filesarea').load('{$refresh_url}');
        return false;
    });

    $(document).on('dropzone_chdir', $(this), function (e, data) {
        // if change dir via the dropzone, make sure filemanager refreshes.
        location.reload();
    });
    $(document).on('dropzone_stop', $(this), function (e, data) {
        // if change dir via the dropzone, make sure filemanager refreshes.
        location.reload();
    });

    $(document).on('change', '#filesarea input[type="checkbox"].fileselect', function (e) {
        e.stopPropagation();
        var t = $(this).prop('checked');
        // adjust the parent row
        if (t) {
            $(this).closest('tr').addClass('selected');
        } else {
            $(this).closest('tr').removeClass('selected');
        }
        enable_action_buttons();
    });

    $(document).on('change', '#tagall', function () {
        if ($(this).is(':checked')) {
            $('#filesarea input:checkbox.fileselect').prop('checked', true).trigger('change');
        } else {
            $('#filesarea input:checkbox.fileselect').prop('checked', false).trigger('change');
        }
    });

    $(document).on('click', '#btn_view', function () {
        // find the selected item.
        var tmp = $('#filesarea input[type="checkbox"]').filter(':checked').val();
        var url = '{$viewfile_url}&showtemplate=false&{$actionid}viewfile=' + tmp;
        $('#popup_contents').load(url);
        $('#popup').dialog({
          minWidth: 380,
          maxHeight: 600
        });
        return false;
    });

    $(document).on('click', 'td.clickable', function () {
        var t = $(this).parent().find(':checkbox').prop('checked');
        if (!t) {
            $(this).parent().find(':checkbox').prop('checked', true).trigger('change');
        } else {
            $(this).parent().find(':checkbox').prop('checked', false).trigger('change');
        }
    });
});
</script>

{function filebtn icon='ui-icon-check'}
{if !empty($text)}
  {$addclass='ui-button-text-icon-primary'}
  {if empty($title)}{$title=$text}{/if}
{else}
  {$addclass='ui-button-icon-primary'}
{/if}
<button type="submit" name="{$iname}" id="{$id}" title="{$title|default:''}" class="filebtn ui-button ui-widget ui-state-default ui-corner-all {$addclass}">
  <span class="ui-icon ui-button-icon-primary {$icon}"></span>
  {if !empty($text)}<span class="ui-button-text">{$text}</span>{/if}
</button>
{/function}

<div id="popup" style="display: none;">
	<div id="popup_contents" style="min-width: 500px; max-height: 600px;"></div>
</div>

<div>
	{$formstart}
	{*$hiddenpath*}
	<div>
	<fieldset>
		{filebtn id='btn_newdir' iname="{$actionid}fileactionnewdir" icon='ui-icon-circle-plus' text=$mod->Lang('newdir') title=$mod->Lang('title_newdir')}
		{filebtn id='btn_view' iname="{$actionid}fileactionview" icon='ui-icon-circle-zoomin' text=$mod->Lang('view') title=$mod->Lang('title_view')}
		{filebtn id='btn_rename' iname="{$actionid}fileactionrename" text=$mod->Lang('rename') title=$mod->Lang('title_rename')}
		{filebtn id='btn_delete' iname="{$actionid}fileactiondelete" icon='ui-icon-trash' text=$mod->Lang('delete') title=$mod->Lang('title_delete')}
		{filebtn id='btn_move' iname="{$actionid}fileactionmove" icon='ui-icon-arrow-4-diag' text=$mod->Lang('move') title=$mod->Lang('title_move')}
		{filebtn id='btn_copy' iname="{$actionid}fileactioncopy" icon='ui-icon-copy' text=$mod->Lang('copy') title=$mod->Lang('title_copy')}
		{filebtn id='btn_unpack' iname="{$actionid}fileactionunpack" icon='ui-icon-suitcase' text=$mod->Lang('unpack') title=$mod->Lang('title_unpack')}
		{filebtn id='btn_thumb' iname="{$actionid}fileactionthumb" icon='ui-icon-star' text=$mod->Lang('thumbnail') title=$mod->Lang('title_thumbnail')}
		{filebtn id='btn_resizecrop' iname="{$actionid}fileactionresizecrop" icon='ui-icon-image' text=$mod->Lang('resizecrop') title=$mod->Lang('title_resizecrop')}
		{filebtn id='btn_rotate' iname="{$actionid}fileactionrotate" icon='ui-icon-image' text=$mod->Lang('rotate') title=$mod->Lang('title_rotate')}
	</fieldset>
	</div>
{/if}{* NOT noform *}
{if !empty($files)}
{if !isset($noform)}<div id="filesarea">{/if}
	<table style="width:100%" class="pagetable scrollable">
		<thead>
			<tr>
				<th class="pageicon">&nbsp;</th>
				<th>{$filenametext}</th>
				<th class="pageicon">{$filetypetext}</th>
				<th class="pageicon">{$fileinfotext}</th>
				<th class="pageicon" title="{$mod->Lang('title_col_fileowner')}">{$fileownertext}</th>
				<th class="pageicon" title="{$mod->Lang('title_col_fileperms')}">{$filepermstext}</th>
				<th class="pageicon" title="{$mod->Lang('title_col_filesize')}" style="text-align:right;">{$filesizetext}</th>
				<th></th>
				<th class="pageicon" title="{$mod->Lang('title_col_filedate')}">{$filedatetext}</th>
				<th class="pageicon">
					<input type="checkbox" name="tagall" value="tagall" id="tagall" title="{$mod->Lang('title_tagall')}">
				</th>
			</tr>
		</thead>
		<tbody>
		{foreach $files as $file}
{strip}		{cycle values="row1,row2" assign=rowclass}
			{if $file->filedate !== ''}
			{$thedate=$file->filedate|cms_date_format}{$thedate=str_replace([' ','-'],['&nbsp;','&minus;'],$thedate)}
			{else}
			{$thedate='&nbsp;'}
			{/if}
			{/strip}<tr class="{$rowclass}">
				<td style="vertical-align:middle">{if isset($file->thumbnail) && $file->thumbnail!=''}{$file->thumbnail}{else}{$file->iconlink}{/if}</td>
				<td class="clickable" style="vertical-align:middle">{$file->txtlink}</td>
				<td class="clickable" style="vertical-align:middle">{$file->mime}</td>{*TODO migrate these styles to external css for rtl etc *}
				<td class="clickable" style="padding-right:8px;white-space:pre;vertical-align:middle">{$file->fileinfo}</td>
				<td class="clickable" style="padding-right:8px;white-space:pre;vertical-align:middle">{if isset($file->fileowner)}{$file->fileowner}{else}&nbsp;{/if}</td>
				<td class="clickable" style="padding-right:8px;vertical-align:middle">{$file->filepermissions}</td>
				<td class="clickable" style="padding-right:4px;white-space:pre;text-align:right;vertical-align:middle">{$file->filesize}</td>
				<td class="clickable" style="padding-left:0;vertical-align:middle">{if isset($file->filesizeunit)}{$file->filesizeunit}{else}&nbsp;{/if}</td>
				<td class="clickable" style="padding-right:8px;white-space:pre;vertical-align:middle">{$thedate}</td>
				<td>
				{if !isset($file->noCheckbox)}
					<label for="x_{$file->urlname}" style="display: none;">{$mod->Lang('toggle')}</label>
					<input type="checkbox" title="{$mod->Lang('toggle')}" id="x_{$file->urlname}" name="{$actionid}selall[]" value="{$file->urlname}" class="fileselect {' '|adjust:'implode':$file->type}"{if isset($file->checked)} checked{/if}>
				{/if}
				</td>
			</tr>
		{/foreach}
		</tbody>
		<tfoot>
			<tr>
				<td>&nbsp;</td>
				<td colspan="7">{$countstext}</td>
			</tr>
		</tfoot>
	</table>
{if !isset($noform)}</div>{/if}
{else}{$countstext}{/if}{*files*}
{if !isset($noform)}
	{*{$actiondropdown}{$targetdir}{$okinput}*}
	{$formend}
</div>
{/if}
