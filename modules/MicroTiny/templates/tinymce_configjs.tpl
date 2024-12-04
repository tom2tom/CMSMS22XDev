{*// runtime data *}
var cmsms_tiny = {
    filebrowser_title: "{$filebrowse_title|escape:'javascript'}",
    filepicker_url: "{$filepicker_url}",
    imagebrowser_title: "{$imagebrowse_title|escape:'javascript'}",
    linker_autocomplete_url: "{$getpages_url}",
    linker_heading: "{$MT->Lang('cmsms_linker2')|escape:'javascript'}",
    linker_text: "{$MT->Lang('cmsms_linker')|escape:'javascript'}",
    linker_title: "{$MT->Lang('title_cmsms_linker')|escape:'javascript'}",
    linker_url: "{$linker_url}",
    loading_info: "{$MT->Lang('loading_info')|escape:'javascript'}",
    mailto_heading: "{$MT->Lang('mailto_text2')|escape:'javascript'}",
    mailto_text: "{$MT->Lang('mailto_text')|escape:'javascript'}",
    mailto_title: "{$MT->Lang('title_mailto')|escape:'javascript'}",
    mediabrowser_title: "{$mediabrowse_title|escape:'javascript'}",
    prompt_alias_info: "{$MT->Lang('tooltip_selectedalias')|escape:'javascript'}",
    prompt_alias: "{$MT->Lang('prompt_selectedalias')|escape:'javascript'}",
    prompt_anchortext: "{$MT->Lang('prompt_anchortext')|escape:'javascript'}",
    prompt_class: "{$MT->Lang('prompt_class')|escape:'javascript'}",
    prompt_email: "{$MT->Lang('prompt_email')|escape:'javascript'}",
    prompt_linktext: "{$MT->Lang('prompt_linktext')|escape:'javascript'}",
    prompt_page_info: "{$MT->Lang('info_linker_autocomplete')|escape:'javascript'}",
    prompt_page_place: "{$MT->Lang('info_linker_autocomplete2')|escape:'javascript'}",
    prompt_page: "{$MT->Lang('prompt_linker')|escape:'javascript'}",
    prompt_rel: "{$MT->Lang('prompt_rel')|escape:'javascript'}",
    prompt_target: "{$MT->Lang('prompt_target')|escape:'javascript'}",
    prompt_text: "{$MT->Lang('prompt_texttodisplay')|escape:'javascript'}",
    root_url: "{$rooturl}",
    tab_advanced: "{$MT->Lang('tab_advanced_title')|escape:'javascript'}",
    tab_general: "{$MT->Lang('tab_general_title')|escape:'javascript'}",
    target_new_window: "{$MT->Lang('newwindow')|escape:'javascript'}",
    target_none: "{$MT->Lang('none')|escape:'javascript'}",
    uploads_url: "{$uploadsurl}"
};
{*// tinymce initialization
//branding disabled (TMCE can't impose that, due to LGPL licence)
//relative_urls: false,
//templates: TODO URL or objects-array
*}
tinymce.init({
    branding: false,
    browser_spellcheck: true,
{if !empty($mt_contentcss)}
    content_css: "{$mt_contentcss}",
{/if}
    contextmenu: false,
    convert_urls: false,
    document_base_url: "{$rooturl}/",
    element_format: "html",
    icons_url: "{$custombase}/CMSMSicons/icons.js",
    icons: "cmsms",
{if !$isfrontend}
    image_advtab: true,
{/if}
    image_title: true,
    language: "{$languageid}",
    menu: {
      insert: { title: "Insert", items: "image link mailto_CP{if !$isfrontend} pagelink_CP{/if} media{if $mt_profile.allowtables} inserttable{/if} | nonbreaking_CP charmap | hr anchor | insertdatetime" }{if $mt_profile.allowtables},
      table: { title: "Table", items: "inserttable | cell row column | advtablesort | tableprops deletetable" }
{else}
{*this clunky format generates correct js*}

{/if}
    },
    menubar: {if $mt_profile.menubar}true{else}false{/if},
    paste_text_use_dialog: true,
    removed_menuitems: "backcolor forecolor newdocument",
    resize: {if ($mt_profile.showstatusbar && $mt_profile.allowresize)}"both"{else}false{/if},
    schema: "html5",
    selector: "{if !empty($mt_selector)}{$mt_selector}{else}textarea.MicroTiny{/if}",
    statusbar: {if $mt_profile.showstatusbar}true{else}false{/if},
{if $isfrontend}
    external_plugins: {
      "mailto_CP": "{$custombase}/CMSMSplugins/mailto_CP/plugin.min.js",
      "nonbreaking_CP": "{$custombase}/CMSMSplugins/nonbreaking_CP/plugin.min.js"
    },
    plugins: "anchor autolink autoresize{if $langdir=='rtl'} directionality{/if} help hr{if $mt_profile.allowimages} image media{/if} link lists nonbreaking_CP paste tabfocus{if $mt_profile.allowtables} table{/if} wordcount",
    toolbar: "undo redo | bold italic underline | alignleft aligncenter alignright alignjustify indent outdent | bullist numlist{if $mt_profile.allowtables} | table{/if} | anchor link mailto_CP unlink{if $mt_profile.allowimages} | image{/if}",
{else}
    external_plugins: {
      "mailto_CP": "{$custombase}/CMSMSplugins/mailto_CP/plugin.min.js",
      "nonbreaking_CP": "{$custombase}/CMSMSplugins/nonbreaking_CP/plugin.min.js",
      "pagelink_CP": "{$custombase}/CMSMSplugins/pagelink_CP/plugin.min.js"{if $mt_profile.allowimages},
      "filepicker_CP": "{$custombase}/CMSMSplugins/filepicker_CP/plugin.min.js"
{/if}    },
    plugins: "anchor autolink autoresize charmap{if $mt_profile.allowimages} image media{/if} code{if $langdir=='rtl'} directionality{/if} fullscreen help hr insertdatetime link lists nonbreaking_CP paste searchreplace tabfocus{if $mt_profile.allowtables} table{/if} wordcount",
    toolbar: "undo redo | cut copy paste | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify indent outdent | bullist numlist{if $mt_profile.allowtables} | table{/if} | anchor link mailto_CP pagelink_CP unlink{if $mt_profile.allowimages} | image{/if}",
{/if}
{if !empty($mt_skin)}    skin: "{$mt_skin}",
{elseif !empty($mt_skinurl)}    skin_url: "{$mt_skinurl}",
{/if}
    setup: function(editor) {
        editor.on('change', function(e) {
            $(document).trigger('cmsms_formchange');
        });
    },
    urlconverter_callback: function(url, elm, onsave, name) {
        if (!this.settings.convert_urls || (elm && elm.nodeName === 'LINK') || url.indexOf('file:') === 0 || url.length === 0) {
            return url;
        }
        // fix entities in cms_selflink urls
        if (url.indexOf('cms_selflink') !== -1) {
            decodeURI(url);
            url = url.replace('%20', ' ');
            return url;
        }
        // convert to relative ?
        if (this.settings.relative_urls) {
            return this.documentBaseURI.toRelative(url);
        }
        // convert to absolute
        url = this.documentBaseURI.toAbsolute(url, this.settings.remove_script_host);
        return url;
    }
});
