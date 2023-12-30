tinymce.util.Tools.resolve('tinymce.PluginManager').add('filepicker_CP', function(editor) {

  editor.settings.file_picker_types = 'file image media';
  editor.settings.file_picker_callback = _callback;

  function _callback(callback, value, meta) {

    var mywin;
    var sz = window.innerHeight;
    var height = (sz < 650) ? Math.max(sz * 0.8, 250) : 600;
    sz = window.innerWidth;
    var width = (sz < 950) ? Math.max(sz * 0.8, 250) : 900;
    // generate a unique id for the active editor so we can access it later.
    var inst = 'i' + (new Date().getTime()).toString(16);
    tinymce.activeEditor.dom.setAttrib(tinymce.activeEditor.dom.select('html'), 'data-cmsfp-instance', inst);

    if (!top.document.CMSFileBrowser) top.document.CMSFileBrowser = {
      type: meta.filetype
    };
    top.document.CMSFileBrowser.onselect = function(inst, file) {

      function basename(str) {
        var sw, pp, base,
          last = str.charAt(str.length - 1);
        if (!(last === '/' || last === '\\')) {
          sw = str;
        } else {
          sw = str.slice(0, -1);
        }
        base = sw.replace(/^.*[/\\]/g, '');
        pp = base.lastIndexOf('.');
        if (pp > 0) {
          return base.slice(0, pp);
        }
        return base;
      }

      if (file.charAt(0) === '/') {
        file = cmsms_tiny.root_url + file; //longform picker result
      } else {
        file = cmsms_tiny.uploads_url + '/' + file; //shortform picker result
      }
      var opts = {};
      switch (meta.filetype) {
        case 'image':
          opts.alt = basename(file);
          break;
        case 'media':
//       opts.?;
         break;
        default:
         opts.text = basename(file);
         break;
      }
      callback(file, opts);
      top.document.CMSFileBrowser.onselect = null;
      mywin.close();
    }; //onselect

    if (cmsms_tiny.filepicker_url) {
      // here we open the filepicker window. TODO relevance of former trailing '&m1_field=' parameter in filepicker_url?
      var url = cmsms_tiny.filepicker_url + '&m1_inst=' + encodeURIComponent(inst) + '&m1_type=' + meta.filetype + '&m1_useprefix=1';
      var key;
      switch (meta.filetype) {
        case 'image':
          key = 'imagebrowser_title';
          break;
        case 'media':
          key = 'mediabrowser_title';
          break;
        default:
          key = 'filebrowser_title';
          break;
      }
      mywin = tinymce.activeEditor.windowManager.openUrl({
        title: cmsms_tiny[key],
        url: url,
        height: height,
        width: width,
        //array of footerbuttons https://www.tiny.cloud/docs/ui-components/dialog/#footerbuttons
        buttons: [{
          type: 'cancel',
          text: 'Close' //translated if possible
        }]
      });
    }
  }
});
