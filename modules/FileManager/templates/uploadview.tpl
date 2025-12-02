<script>{literal}
$(function() {
  var _jqXHR = []; // jqXHR array
  var _files = []; // filenames
  var refurl = {/literal}'{$refresh_url}&showtemplate=false';{literal}
  // prevent browser default drag/drop handling
  $(document).on('drop dragover', function(e) {
    //prevent default drag/drop stuff.
    e.preventDefault();
  });

  $(document).on('dragover', function(e) {
    var dropZone = $('#dropzone'),
    timeout = window.dropZoneTimeout;
    if (timeout) {
      clearTimeout(timeout);
    } else {
      dropZone.addClass('in');
    }
    var hoveredDropZone = $(e.target).closest(dropZone);
    dropZone.toggleClass('hover', hoveredDropZone.length);
    window.dropZoneTimeout = setTimeout(function () {
      window.dropZoneTimeout = null;
      dropZone.removeClass('in hover');
    }, 100);
  });

  $('#cancel').on('click', function(e) {
    e.preventDefault();
    aborting = true;
    var ul = $('#fileupload').data('fileupload');
    if( typeof ul !== 'undefined' ) {
      var data = {};
      data.errorThrown = 'abort';
      ul._trigger('fail', e, data);
    }
  });

  // create our file upload area.
  $('#fileupload').fileupload({
    add: function(e, data) {
      _files.push(data.files[0].name);
      _jqXHR.push(data.submit());
    },

    dataType: 'json',
    dropZone: $('#dropzone'),
    maxChunkSize: {/literal}{$max_chunksize},{literal}

    start: function(e,data) {
      $('#cancel').show();
      $('#progressarea').show();
    },

    done: function(e,data) {
//      $('#filesarea').load(refurl);
//      $('#cancel').fadeOut();
//      $('#progressarea').fadeOut();
//      _files = [];
//      _jqXHR = [];
    },

    fail: function(e, data) {
      $.each(_jqXHR, function(index,obj)  {
        if( typeof obj === 'object' )
        {
          obj.abort();
          if( index < _files.length && typeof data.url !== 'undefined' ) {
            // now delete the file.
            var turl = '{/literal}{$action_url}{literal}' + '&' + $.param({ file: _files[index] });
            $.ajax({
              url: turl,
              type: 'DELETE'
            });
          }
        }
      });
      _jqXHR = [];
      _files = [];
    },

    progressall: function(e, data) {
      // overall progress callback
      var perc = (data.loaded / data.total * 100).toFixed(2);
      var total = null;
      total = (data.loaded / data.total * 100).toFixed(0);
      var str = perc + ' %';
      //console.log(total);
      barValue(total);

      function barValue(total) {
        $('#progressarea').progressbar({
          value: parseInt(total)
        });
        $('.ui-progressbar-value').html(str);
      }
    },

    stop: function(e, data) {
      $('#filesarea').load(refurl);
      $('#cancel').fadeOut();
      $('#progressarea').fadeOut();
      _jqXHR = [];
      _files = [];
    }
  });
});{/literal}
</script>
{$uformstart}
  <input type="hidden" name="disable_buffer" value="1">
  <fieldset>
    {if isset($is_ie)}
      <div class="pageerrorcontainer message">
        <p class="pageerror">{$ie_upload_message}</p>
      </div>
    {/if}
    <div class="upload-wrapper">
      <div class="startside last">
{*      <input type="hidden" name="MAX_FILE_SIZE" value="{$maxfilesize}">*}{* recommendation for browser *}
        <input id="fileupload" type="file" name="{$actionid}files[]" size="50" title="{$mod->Lang('title_filefield')}" multiple>
        <br>
        <div id="pageoverflow">
          <p class="pageinput">
            <input id="cancel" type="submit" value="{$mod->Lang('cancel')}" style="display:none">
          </p>
        </div>
      </div>
{if !isset($is_ie)}
      <div id="dropcol" class="endside last">
        <div id="dropzone" class="vcentered hcentered fade" title="{$mod->Lang('title_dropzone')}">
          <p id="dropzonetext">
            {$mod->Lang('prompt_dropfiles')}
          </p>
        </div>
      </div>
{/if}
      <div id="progressarea"></div>
    </div>
  </fieldset>
{$formend}
