tinymce.PluginManager.add('mailto', function(editor, url) {

  function mailto_showDialog() {
    selectedNode = editor.selection.getNode();
    anchorElm = false;

    var isMailtoLink = selectedNode.tagName == 'A' && editor.dom.getAttrib(selectedNode, 'href').startsWith('mailto:');

    email_val = '';
    text_val = '';

    if (isMailtoLink) {
      email_val = editor.dom.getAttrib(selectedNode, 'href').replace('mailto:','');

      anchorElm = editor.dom.getParent(selectedNode, 'a[href*="mailto:"]');
      text_val = anchorElm.innerText;
      console.log(selectedNode);
    }
    else {
      text_val = editor.selection.getContent({format: 'text'});
    }


    editor.windowManager.open({
      title: cmsms_tiny.mailto_heading,
      body: [
      {
        type: 'textbox',
        name: 'email',
        size: 40,
        label: cmsms_tiny.prompt_email,
        value: email_val
      },
      {
        type: 'textbox',
        name: 'text',
        size: 40,
        label: cmsms_tiny.prompt_linktext,
        value: text_val
      }],
      onsubmit: function(e) {

        if (e.data.text != '') {
          link_text = e.data.text;
        } else {
          link_text = e.data.email;
        }
        var linkAttrs = {
          href: 'mailto'+e.data.email,
        };

        // Select the tag if any
        if (anchorElm) {
          editor.selection.select(anchorElm);
        }

        // And put the <a tag
        editor.execCommand('mceInsertContent', false, editor.dom.createHTML('a', {
          href: "mailto:"+e.data.email
        }, link_text));
      }
    });
  }

  // and a menu item
  editor.addMenuItem('mailto', {
      text: cmsms_tiny.mailto_text,
      title: cmsms_tiny.mailto_title,
      image: cmsms_tiny.mailto_image,
      stateSelector: 'a[href*="mailto:"]',
      context: 'insert',
      prependToContext: true,
      onclick: mailto_showDialog
  });

  editor.addButton('mailto', {
    text: '@',
    tooltip: cmsms_tiny.mailto_title,
    onclick: mailto_showDialog,
    stateSelector: 'a[href*="mailto:"]'
  });

});