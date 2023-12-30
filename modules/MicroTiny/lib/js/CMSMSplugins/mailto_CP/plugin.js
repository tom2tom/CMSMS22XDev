tinymce.util.Tools.resolve('tinymce.PluginManager').add('mailto_CP', function(editor) {

  function mailto_showDialog() {

    var anchorElm;
    var email_val;
    var text_val;
    var selectedNode = editor.selection.getNode();
    var isMailtoLink = selectedNode.tagName == 'A' && editor.dom.getAttrib(selectedNode, 'href').startsWith('mailto:');

    if (isMailtoLink) {
      email_val = editor.dom.getAttrib(selectedNode, 'href').replace('mailto:', '');
      anchorElm = editor.dom.getParent(selectedNode, 'a[href^="mailto:"]');
      text_val = anchorElm.innerText;
      console.log(selectedNode);
    } else {
      email_val = '';
      anchorElm = false;
      text_val = editor.selection.getContent({
        format: 'text'
      });
    }

    editor.windowManager.open({
      title: cmsms_tiny.mailto_heading,
      body: {
        type: 'panel',
        items: [{
            type: 'input',
            name: 'email',
            label: cmsms_tiny.prompt_email,
            size: 40
          },
          {
            type: 'input',
            name: 'text',
            label: cmsms_tiny.prompt_linktext,
            size: 40
          }
        ]
      },
      initialData: {
        email: email_val,
        text: text_val
      },
      //array of footerbuttons https://www.tiny.cloud/docs/ui-components/dialog/#footerbuttons
      buttons: [{
          type: 'cancel',
          text: 'Cancel' //translated if possible
        },
        {
          type: 'submit',
          text: 'Save',
          primary: true
        }
      ],
      //refer to https://www.tiny.cloud/docs/ui-components/dialog/#dialoginstanceapi
      onSubmit: function(dialogApi) {
        var link_text, data = dialogApi.getData(); //value and/or state of the dialog’s panel components
        if (data.text) {
          link_text = data.text;
        } else {
          link_text = data.email;
        }
        // select the tag if any
        if (anchorElm) {
          editor.selection.select(anchorElm);
        }
        // inject an <a/> tag
        editor.execCommand('mceInsertContent', false, editor.dom.createHTML('a', {
          href: 'mailto:' + data.email
        }, link_text));

        dialogApi.close();
      }
    });
  }

  function getAnchorElement(editor, selectedElm) {
    selectedElm = selectedElm || editor.selection.getNode();
    if (selectedElm) {//TODO && some relevant test applied to selectedElm
      return editor.dom.select('a[href^="mailto:"]', selectedElm)[0]; //? 'a[href^="mailto:"]' OR just 'a[href]'
    } else {
      return editor.dom.getParent(selectedElm, 'a[href^="mailto:"'); //? 'a[href^="mailto:"]' OR just 'a[href]'
    }
  }

  function toggleState(editor, toggler) {
    editor.on('NodeChange', toggler);
    return function() {
      return editor.off('NodeChange', toggler);
    };
  }

  function onSetupMailer(api) {
    return function(api) {
//  stateSelector: 'a[href^="mailto:"]', TODO V5 workaround needed
      var updateState = function() {
        return api.setDisabled(getAnchorElement(editor, editor.selection.getNode()) === null);
      };
      updateState();
      return toggleState(editor, updateState);
    };
  }

  // add a menu item
  editor.ui.registry.addMenuItem('mailto_CP', {
    icon: 'mailto',
    onAction: mailto_showDialog,
    onSetup: onSetupMailer,
    text: cmsms_tiny.mailto_text + '...'
  });
  // and a button
  editor.ui.registry.addButton('mailto_CP', {
    icon: 'mailto',
    onAction: mailto_showDialog,
    onSetup: onSetupMailer,
    tooltip: cmsms_tiny.mailto_title
  });
});
