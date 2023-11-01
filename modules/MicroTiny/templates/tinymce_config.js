// data
//filepicker_title: "{$MT->Lang('filepickertitle')|escape:'javascript'}", usage replaced by a specific *browser_title
var cmsms_tiny = {
    filebrowser_title: "{$MT->Lang('title_cmsms_filebrowser')|escape:'javascript'}",
    filepicker_url: "{$filepicker_url}&field=",
    imagebrowser_title: "{$MT->Lang('title_cmsms_imagebrowser')|escape:'javascript'}",
    linker_autocomplete_url: "{$getpages_url}",
    linker_heading: "{$MT->Lang('cmsms_linker2')|escape:'javascript'}",
    linker_image: "{$MT->GetModuleURLPath()}/lib/images/cmsmslink.gif",
    linker_text: "{$MT->Lang('cmsms_linker')|escape:'javascript'}",
    linker_title: "{$MT->Lang('title_cmsms_linker')|escape:'javascript'}",
    linker_url: "{$linker_url}",
    loading_info: "{$MT->Lang('loading_info')|escape:'javascript'}",
    mailto_heading: "{$MT->Lang('mailto_text2')|escape:'javascript'}",
    mailto_image: "{$MT->GetModuleURLPath()}/lib/images/mailto.gif",
    mailto_text: "{$MT->Lang('mailto_text')|escape:'javascript'}",
    mailto_title: "{$MT->Lang('title_mailto')|escape:'javascript'}",
    prompt_alias_info: "{$MT->Lang('tooltip_selectedalias')|escape:'javascript'}",
    prompt_alias: "{$MT->Lang('prompt_selectedalias')|escape:'javascript'}",
    prompt_anchortext: "{$MT->Lang('prompt_anchortext')|escape:'javascript'}",
    prompt_class: "{$MT->Lang('prompt_class')|escape:'javascript'}",
    prompt_email: "{$MT->Lang('prompt_email')|escape:'javascript'}",
    prompt_linktext: "{$MT->Lang('prompt_linktext')|escape:'javascript'}",
    prompt_page_info: "{$MT->Lang('info_linker_autocomplete')|escape:'javascript'}",
    prompt_page: "{$MT->Lang('prompt_linker')|escape:'javascript'}",
    prompt_rel: "{$MT->Lang('prompt_rel')|escape:'javascript'}",
    prompt_target: "{$MT->Lang('prompt_target')|escape:'javascript'}",
    prompt_text: "{$MT->Lang('prompt_texttodisplay')|escape:'javascript'}",
    tab_advanced: "{$MT->Lang('tab_advanced_title')|escape:'javascript'}",
    tab_general: "{$MT->Lang('tab_general_title')|escape:'javascript'}",
    target_new_window: "{$MT->Lang('newwindow')|escape:'javascript'}",
    target_none: "{$MT->Lang('none')|escape:'javascript'}",
};

// tinymce initialization
tinymce.init({
    browser_spellcheck: true,
    document_base_url: "{root_url}/",
    element_format: "html",
    image_title: true,
    language: "{$languageid}",
    menubar: {if $mt_profile.menubar}true{else}false{/if},
    mysamplesetting: "foobar",
    relative_urls: true,
    removed_menuitems: "newdocument",
    resize: {if ($mt_profile.showstatusbar && $mt_profile.allowresize)}"both"{else}false{/if},
    schema: "html5",
    selector: "{if !empty($mt_selector)}{$mt_selector}{else}textarea.MicroTiny{/if}",
    statusbar: {if $mt_profile.showstatusbar}true{else}false{/if},
{if !empty($mt_cssname)}
    content_css: "{cms_stylesheet name=$mt_cssname nolinks=1}",
{/if}
{if $isfrontend}
    toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify indent outdent | bullist numlist | link mailto{if $mt_profile.allowimages} | image{/if}',
    plugins: ['anchor','autolink','autoresize'{if $langdir=='rtl'},'directionality'{/if},'help','hr'{if $mt_profile.allowimages},'image','media'{/if},'link','lists','mailto','nonbreaking','paste','tabfocus'{if $mt_profile.allowtables},'table'{/if},'wordcount'],
{else}
    image_advtab: true,
    toolbar: 'undo redo | cut copy paste | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify indent outdent | bullist numlist | anchor link mailto unlink cmsms_linker{if $mt_profile.allowimages} | image{/if}',
    plugins: ['anchor','autolink','autoresize','charmap'{if $mt_profile.allowimages},'cmsms_filepicker','image','media'{/if},'cmsms_linker','code'{if $langdir=='rtl'},'directionality'{/if},'fullscreen','help','hr','insertdatetime','link','lists','mailto','nonbreaking','paste','searchreplace','tabfocus'{if $mt_profile.allowtables},'table'{/if},'wordcount'],
{/if}
    // callback functions
    urlconverter_callback: function(url, elm, onsave, name) {
        var self = this;
        var settings = self.settings;

        if (!settings.convert_urls || ( elm && elm.nodeName == 'LINK' ) || url.indexOf('file:') === 0 || url.length === 0) {
            return url;
        }

        // fix entities in cms_selflink urls
        if (url.indexOf('cms_selflink') != -1) {
            decodeURI(url);
            url = url.replace('%20', ' ');
            return url;
        }
        // Convert to relative
        if (settings.relative_urls) {
            return self.documentBaseURI.toRelative(url);
        }
        // Convert to absolute
        url = self.documentBaseURI.toAbsolute(url, settings.remove_script_host);

        return url;
    },
    setup: function(editor) {
        editor.addMenuItem('mailto', {
            text: cmsms_tiny.mailto_text,
            cmd: 'mailto',
            context: 'insert',
        });
        editor.on('change', function(e) {
            $(document).trigger('cmsms_formchange');
        });
    },
    paste_as_text: true
});
