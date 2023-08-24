tinymce.PluginManager.add('cmsms_filepicker', function(editor) {

    editor.settings.file_picker_type = 'file image media';
    editor.settings.file_picker_callback = _callback;

    function _callback(callback, value, meta) {

        var height, width, mywin;

        ( function(window) {
            height = 600;
            width = 900;
            if (window.innerHeight < 650) {
                height = Math.max(window.innerHeight * 0.8, 250);
            }
            if (window.innerWidth < 950) {
                width = Math.max(window.innerWidth * 0.8, 250);
            }
        } )(window);

        // generate a uniquie id for the active editor so we can access it later.
        var inst = 'i'+(new Date().getTime()).toString(16);
        tinymce.activeEditor.dom.setAttrib(tinyMCE.activeEditor.dom.select('html'),'data-cmsfp-instance',inst);

        if( !top.document.CMSFileBrowser ) top.document.CMSFileBrowser = {};
        top.document.CMSFileBrowser.onselect = function(inst,file) {
            file = cms_data.uploads_url + '/' + file;
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

            var opts = {};
            if( meta.filetype === 'file' ) {
                opts.text = basename(file);
            } else if( meta.filetype === 'image' ) {
                opts.alt = basename(file);
            }
            callback(file, opts);
            top.document.CMSFileBrowser.onselect = null;
            mywin.close();
        };


        // here we open the filepicker window.
        var url = cmsms_tiny.filepicker_url + '&inst=' + inst + '&type='+meta.filetype + '&useprefix=1';
        mywin = tinymce.activeEditor.windowManager.open({
            title : cmsms_tiny.filepicker_title,
            file : url,
            classes : 'filepicker',
            height : height,
            width : width,
            inline : 1,
            resizable : true,
            maximizable : true,
        }, {
            onFileSelected: function(filename) {
                console.debug('woot got callback with '+filename);
            }
        });


    }
    return false;
});
