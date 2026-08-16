<script>
$(function() {
  $('#runbtn').button({
    icon: 'ui-icon-gear'
  })
  .on('click', function(ev) {
    // get the data
    ev.preventDefault();
    cms_confirm('{lang('confirm_runusertag')|strip|escape:'quotes'}').done(function() {
      var code = $('#udtcode').val();
      if( code.length == 0 ) {
        var d = '{lang("noudtcode")}',
         txt = '<div class="pageerrorcontainer"><ul class="pageerror">' + d + '<\/ul><\/div>';
        $('#edit_userplugin_result').html(txt);
        return false;
      }
      var data = $('#edit_userplugin').find('input:not([type="submit"]), select, textarea').serializeArray();
      data.push(
       { 'name': 'run', 'value': 1 },
       { 'name': 'apply', 'value': 1 },
       { 'name': 'ajax', 'value': 1 });
      $.post('{$smarty.server.REQUEST_URI}',data,function(resultdata,text) {
        var r,d,e;
        try {
          var x = JSON.parse(resultdata);
          if( typeof x.response !== 'undefined' ) {
            r = x.response;
            d = x.details;
          } else {
            d = resultdata;
          }
        } catch( e ) {
          r = '_error';
          d = resultdata;
        }

        e = $('<div></div>').text(d).html(); // quick tip for entity encoding.
        if( r === '_error' ) e = d;
        $('#edit_userplugin_runout').html(e);
        $('#edit_userplugin_runout').dialog({ modal: true, width: 'auto' });
      });
      return false;
    }).fail(function() {
      return false;
    });
  });

  $('#applybtn').on('click', function() {
    var data = $('#edit_userplugin').find('input:not([type="submit"]), select, textarea').serializeArray();
    data.push(
      { 'name': 'ajax', 'value': 1 },
      { 'name': 'apply', 'value': 1 });

    $.post('{$smarty.server.REQUEST_URI}',data,function(resultdata,text) {
      var x = JSON.parse(resultdata);
      var r = x.response;
      var d = x.details;
      var txt;
      if( r == 'Success' ) {
        txt = '<div class="pagemcontainer"><span class="close-warning"></span><p class="pagemessage">' + d + '<\/p><\/div>';
        var b = $('[name="cancel"]');
        b.fadeOut();
        b.val('{lang("close")}');
        b.button('option','label','{lang("close")}');
        b.fadeIn();
      }
      else {
        txt = '<div class="pageerrorcontainer"><ul class="pageerror">' + d + '<\/ul><\/div>';
      }
      $('#edit_userplugin_result').html( txt );
    });
    return false;
  });
});
</script>

<div class="pagecontainer">
	{if $record.userplugin_id == ''}
		<h3>{lang('addusertag')}</h3>
	{else}
		<h3>{lang('editusertag')}</h3>
	{/if}

	<div id="edit_userplugin_runout" title="{lang('output')}" style="display:none"></div>
	<div id="edit_userplugin_result"></div>

{form_start url='editusertag.php' id='edit_userplugin' userplugin_id=$record.userplugin_id}
	<fieldset>
		<div class="startside">
			<br>
			<div class="pageoverflow">
				<p class="pageinput">
					<input type="submit" id="submitme" name="submit" value="{lang('submit')}">
{if $record.userplugin_id}
					<input type="submit" id="applybtn" name="apply" value="{lang('apply')}" data-ui-icon="ui-icon-caret-1-n" title="{lang('title_applyusertag')}">
{/if}
					<input type="submit" name="cancel" value="{lang('cancel')}">
{if $record.userplugin_id}
					<input type="submit" id="runbtn" name="run" value="{lang('run')}" data-ui-icon="ui-icon-gear" title="{lang('runuserplugin')}">
{/if}
				</p>
			</div>
			<div class="pageoverflow">
				<p class="pagetext">
					<label for="tagname">{lang('name')}:</label>&nbsp;{cms_help key1=h_udtname title=lang('name')}
				</p>
				<p class="pageinput">
					<input type="text" id="tagname" name="userplugin_name" value="{$record.userplugin_name}" size="50" maxlength="50">
				</p>
			</div>
		</div>
		<p class="startside" style="width:5%;min-width:1em"></p>
		<div class="startside last">
{if $record.create_date}
			<div class="pageoverflow">
				<p class="pagetext">
					<label>{lang('created_at')}:</label>
				</p>
				<p class="pageinput">
					{$record.create_date|cms_date_format}
				</p>
			</div>
{/if}
{if $record.modified_date}
			<div class="pageoverflow">
				<p class="pagetext">
					<label>{lang('last_modified_at')}:</label>
				</p>
				<p class="pageinput">
					{$record.modified_date|cms_date_format}
				</p>
			</div>
{/if}
		</div>
	</fieldset>

	{tab_header name='code' label=lang('code')}
	{tab_header name='description' label=lang('description')}

	{tab_start name='code'}
		<div class="pageoverflow">
			<p class="pagetext">
				<label for="udtcode"><strong>{lang('code')}:</strong></label>&nbsp;{cms_help key1=h_udtcode title=lang('code')}
			</p>
			<p class="pageinput">
				{cms_textarea id='udtcode' name='code' value=$record.code wantedsyntax=php rows=10 cols=80}
			</p>
		</div>

	{tab_start name='description'}
		<div class="pageoverflow">
			<p class="pagetext">
				<label for="udtdesc">{lang('description')}:</label>&nbsp;{cms_help key1=h_udtdesc title=lang('description')}
			</p>
			<p class="pageinput">
				<textarea id="udtdesc" name="description" rows="3" cols="80">{$record.description}</textarea>
			</p>
		</div>
	{tab_end}

{form_end}

</div>
