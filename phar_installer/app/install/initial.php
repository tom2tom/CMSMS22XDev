<?php
//global $admin_user;

//
// Stylesheets
//
// no stylesheets when no sample content

//
// Designs
//
$design = new CmsLayoutCollection();
$design->set_name('Default');
$design->set_description('Default design with just the default template');
$design->set_default(TRUE);
$design->save();
$design->save();

//
// Types
//
$page_template_type = new CmsLayoutTemplateType();
$page_template_type->set_originator(CmsLayoutTemplateType::CORE);
$page_template_type->set_name('page');
$page_template_type->set_dflt_flag(TRUE);
$page_template_type->set_lang_callback('CmsTemplateResource::page_type_lang_callback');
$page_template_type->set_content_callback('CmsTemplateResource::reset_page_type_defaults');
$page_template_type->set_help_callback('CmsTemplateResource::template_help_callback');
$page_template_type->reset_content_to_factory();
$page_template_type->set_content_block_flag(TRUE);
$page_template_type->save();

$gcb_template_type = new CmsLayoutTemplateType();
$gcb_template_type->set_originator(CmsLayoutTemplateType::CORE);
$gcb_template_type->set_name('generic');
$gcb_template_type->set_lang_callback('CmsTemplateResource::generic_type_lang_callback');
$gcb_template_type->set_help_callback('CmsTemplateResource::template_help_callback');
$gcb_template_type->save();

//
// Template Categories
//

//
// Template
//
$app = \__appbase\get_app();

$fn = $app->get_destdir()
    . DIRECTORY_SEPARATOR . 'admin'
    . DIRECTORY_SEPARATOR . 'templates'
    . DIRECTORY_SEPARATOR . 'orig_page_template.tpl';

$txt = file_get_contents($fn);
$template = new CmsLayoutTemplate();
$template->set_name('Default');
$template->set_description('This is the default minimal template. A simple starting point to build templates from.');
$template->set_type($page_template_type);
$template->set_content($txt);
$template->set_type($page_template_type);
$template->set_type_dflt(TRUE);
$template->add_design($design);
$template->set_owner(1);
$template->save();

//
// Extra global templates
//

// no templates when no sample content

//
// Stylesheets
//
$css = new CmsLayoutStylesheet();
$css->set_name('Boilerplate');
$css->set_description('Resets: normalise and html5 default');
$css->set_media_types('all'); //TODO except print
$css->set_content(<<<'EOT'
/*! normalise.css v11.0.1 | CC0-1.0 License | https://github.com/csstools/normalize.css */\n\n/* Document\n * ========================================================================== */\n\n/**\n * 1. Correct the line height in all browsers.\n * 2. Prevent adjustments of font size after orientation changes in\n *    IE on Windows Phone and in iOS.\n */\n\nhtml {\n	line-height: 1.15; /* 1 */\n	-ms-text-size-adjust: 100%; /* 2 */\n	-webkit-text-size-adjust: 100%; /* 2 */\n}\n\n/* Sections\n * ========================================================================== */\n\n/**\n * Correct the font size and margin on `h1` elements within `section` and\n * `article` contexts in Chrome, Edge, Firefox, and Safari.\n */\n\nh1 {\n	font-size: 2em;\n	margin: 0.67em 0;\n}\n\n/* Grouping content\n * ========================================================================== */\n\n/**\n * Remove the margin on nested lists in Chrome, Edge, IE, and Safari.\n */\n\ndl dl,\ndl ol,\ndl ul,\nol dl,\nul dl {\n	margin: 0;\n}\n\n/**\n * Remove the margin on nested lists in Edge 18- and IE.\n */\n\nol ol,\nol ul,\nul ol,\nul ul {\n	margin: 0;\n}\n\n/**\n * 1. Add the correct box sizing in Firefox.\n * 2. Correct the inheritance of border color in Firefox.\n * 3. Show the overflow in Edge 18- and IE.\n */\n\nhr {\n	box-sizing: content-box; /* 1 */\n	color: inherit; /* 2 */\n	height: 0; /* 1 */\n	overflow: visible; /* 3 */\n}\n\n/**\n * Add the correct display in IE.\n */\n\nmain {\n	display: block;\n}\n\n/**\n * 1. Correct the inheritance and scaling of font size in all browsers.\n * 2. Correct the odd `em` font sizing in all browsers.\n */\n\npre {\n	font-family: monospace, monospace; /* 1 */\n	font-size: 1em; /* 2 */\n}\n\n/* Text-level semantics\n * ========================================================================== */\n\n/**\n * Remove the gray background on active links in IE 10.\n */\n\na {\n	background-color: transparent;\n}\n\n/**\n * Add the correct text decoration in Edge 18-, IE, and Safari.\n */\n\nabbr[title] {\n	text-decoration: underline;\n	text-decoration: underline dotted;\n}\n\n/**\n * Add the correct font weight in Chrome, Edge, and Safari.\n */\n\nb,\nstrong {\n	font-weight: bolder;\n}\n\n/**\n * 1. Correct the inheritance and scaling of font size in all browsers.\n * 2. Correct the odd `em` font sizing in all browsers.\n */\n\ncode,\nkbd,\nsamp {\n	font-family: monospace, monospace; /* 1 */\n	font-size: 1em; /* 2 */\n}\n\n/**\n * Add the correct font size in all browsers.\n */\n\nsmall {\n	font-size: 80%;\n}\n\n/* Embedded content\n * ========================================================================== */\n\n/**\n * Add the correct display in IE 9-.\n */\n\naudio,\nvideo {\n	display: inline-block;\n}\n\n/**\n * Add the correct display in iOS 4-7.\n */\n\naudio:not([controls]) {\n	display: none;\n	height: 0;\n}\n\n/**\n * Remove the border on images within links in IE 10-.\n */\n\nimg {\n	border-style: none;\n}\n\n/**\n * Hide the overflow in IE.\n */\n\nsvg:not(:root) {\n	overflow: hidden;\n}\n\n/* Tabular data\n * ========================================================================== */\n\n/**\n * 1. Correct table border color inheritance in all Chrome, Edge, and Safari.\n * 2. Remove text indentation from table contents in Chrome, Edge, and Safari.\n */\n\ntable {\n	border-color: inherit; /* 1 */\n	text-indent: 0; /* 2 */\n}\n\n/* Forms\n * ========================================================================== */\n\n/**\n * Remove the margin on controls in Safari.\n */\n\nbutton,\ninput,\nselect {\n	margin: 0;\n}\n\n/**\n * 1. Show the overflow in IE.\n * 2. Remove the inheritance of text transform in Edge 18-, Firefox, and IE.\n */\n\nbutton {\n	overflow: visible; /* 1 */\n	text-transform: none; /* 2 */\n}\n\n/**\n * Correct the inability to style buttons in iOS and Safari.\n */\n\nbutton,\n[type=""button""],\n[type=""reset""],\n[type=""submit""] {\n	-webkit-appearance: button;\n}\n\n/**\n * Correct the padding in Firefox.\n */\n\nfieldset {\n	padding: 0.35em 0.75em 0.625em;\n}\n\n/**\n * Show the overflow in Edge 18- and IE.\n */\n\ninput {\n	overflow: visible;\n}\n\n/**\n * 1. Correct the text wrapping in Edge 18- and IE.\n * 2. Correct the color inheritance from `fieldset` elements in IE.\n */\n\nlegend {\n	box-sizing: border-box; /* 1 */\n	color: inherit; /* 2 */\n	display: table; /* 1 */\n	max-width: 100%; /* 1 */\n	white-space: normal; /* 1 */\n}\n\n/**\n * 1. Add the correct display in Edge 18- and IE.\n * 2. Add the correct vertical alignment in Chrome, Edge, and Firefox.\n */\n\nprogress {\n	display: inline-block; /* 1 */\n	vertical-align: baseline; /* 2 */\n}\n\n/**\n * Remove the inheritance of text transform in Firefox.\n */\n\nselect {\n	text-transform: none;\n}\n\n/**\n * 1. Remove the margin in Firefox and Safari.\n * 2. Remove the default vertical scrollbar in IE.\n */\n\ntextarea {\n	margin: 0; /* 1 */\n	overflow: auto; /* 2 */\n}\n\n/**\n * 1. Add the correct box sizing in IE 10-.\n * 2. Remove the padding in IE 10-.\n */\n\n[type=""checkbox""],\n[type=""radio""] {\n	box-sizing: border-box; /* 1 */\n	padding: 0; /* 2 */\n}\n\n/**\n * 1. Correct the odd appearance in Chrome, Edge, and Safari.\n * 2. Correct the outline style in Safari.\n */\n\n[type=""search""] {\n	-webkit-appearance: textfield; /* 1 */\n	outline-offset: -2px; /* 2 */\n}\n\n/**\n * Correct the cursor style of increment and decrement buttons in Safari.\n */\n\n::-webkit-inner-spin-button,\n::-webkit-outer-spin-button {\n	height: auto;\n}\n\n/**\n * Correct the text style of placeholders in Chrome, Edge, and Safari.\n */\n\n::-webkit-input-placeholder {\n	color: inherit;\n	opacity: 0.54;\n}\n\n/**\n * Remove the inner padding in Chrome, Edge, and Safari on macOS.\n */\n\n::-webkit-search-decoration {\n	-webkit-appearance: none;\n}\n\n/**\n * 1. Correct the inability to style upload buttons in iOS and Safari.\n * 2. Change font properties to `inherit` in Safari.\n */\n\n::-webkit-file-upload-button {\n	-webkit-appearance: button; /* 1 */\n	font: inherit; /* 2 */\n}\n\n/**\n * Remove the inner border and padding of focus outlines in Firefox.\n */\n\n::-moz-focus-inner {\n	border-style: none;\n	padding: 0;\n}\n\n/**\n * Restore the focus outline styles unset by the previous rule in Firefox.\n */\n\n:-moz-focusring {\n	outline: 1px dotted ButtonText;\n}\n\n/**\n * Remove the additional :invalid styles in Firefox.\n */\n\n:-moz-ui-invalid {\n	box-shadow: none;\n}\n\n/* Interactive\n * ========================================================================== */\n\n/*\n * Add the correct display in Edge 18- and IE.\n */\n\ndetails {\n	display: block;\n}\n\n/*\n * Add the correct styles in Edge 18-, IE, and Safari.\n */\n\ndialog {\n	background-color: white;\n	border: solid;\n	color: black;\n	display: block;\n	height: -moz-fit-content;\n	height: -webkit-fit-content;\n	height: fit-content;\n	left: 0;\n	margin: auto;\n	padding: 1em;\n	position: absolute;\n	right: 0;\n	width: -moz-fit-content;\n	width: -webkit-fit-content;\n	width: fit-content;\n}\n\ndialog:not([open]) {\n	display: none;\n}\n\n/*\n * Add the correct display in all browsers.\n */\n\nsummary {\n	display: list-item;\n}\n\n/* Scripting\n * ========================================================================== */\n\n/**\n * Add the correct display in IE 9-.\n */\n\ncanvas {\n	display: inline-block;\n}\n\n/**\n * Add the correct display in IE.\n */\n\ntemplate {\n	display: none;\n}\n\n/* User interaction\n * ========================================================================== */\n\n/**\n * Add the correct display in IE 10-.\n */\n\n[hidden] {\n	display: none;\n}\n\n/*! HTML5 Boilerplate v8.0.0 | MIT License | https://html5boilerplate.com/ */\n/* with supplementary clip-path styles and .cf replicating .clearfix and omitting all print classes */\n/*\n * What follows is the result of much research on cross-browser styling.\n * Credit left inline and big thanks to Nicolas Gallagher, Jonathan Neal,\n * Kroc Camen, and the H5BP dev community and team.\n */\n\n/* ==========================================================================\n   Base styles: opinionated defaults\n   ========================================================================== */\n\nhtml {\n	color: #222;\n	font-size: 1em;\n	line-height: 1.4;\n}\n\n/*\n * Remove text-shadow in selection highlight:\n * https://twitter.com/miketaylr/status/12228805301\n *\n * Vendor-prefixed and regular ::selection selectors cannot be combined:\n * https://stackoverflow.com/a/16982510/7133471\n *\n * Customize the background color to match your design.\n */\n\n::-moz-selection {\n	background: #b3d4fc;\n	text-shadow: none;\n}\n\n::selection {\n	background: #b3d4fc;\n	text-shadow: none;\n}\n\n/*\n * A better looking default horizontal rule\n */\n\nhr {\n	display: block;\n	height: 1px;\n	border: 0;\n	border-top: 1px solid #ccc;\n	margin: 1em 0;\n	padding: 0;\n}\n\n/*\n * Remove the gap between audio, canvas, iframes,\n * images, videos and the bottom of their containers:\n * https://github.com/h5bp/html5-boilerplate/issues/440\n */\n\naudio,\ncanvas,\niframe,\nimg,\nsvg,\nvideo {\n	vertical-align: middle;\n}\n\n/*\n * Remove default fieldset styles.\n */\n\nfieldset {\n	border: 0;\n	margin: 0;\n	padding: 0;\n}\n\n/*\n * Allow only vertical resizing of textareas.\n */\n\ntextarea {\n	resize: vertical;\n}\n\n/* ==========================================================================\n   Helper classes\n   ========================================================================== */\n\n/*\n * Hide visually and from screen readers\n */\n\n.hidden,\n[hidden] {\n	display: none !important;\n}\n\n/*\n * Hide only visually, but have it available for screen readers:\n * https://snook.ca/archives/html_and_css/hiding-content-for-accessibility\n *\n * 1. For long content, line feeds are not interpreted as spaces and small width\n *    causes content to wrap 1 word per line:\n *    https://medium.com/@jessebeach/beware-smushed-off-screen-accessible-text-5952a4c2cbfe\n */\n\n.sr-only {\n	border: 0;\n	clip: rect(0 0 0 0);\n	clip: rect(0, 0, 0, 0);\n	clip-path: inset(50%);\n	height: 1px;\n	margin: -1px;\n	overflow: hidden;\n	padding: 0;\n	position: absolute;\n	white-space: nowrap;\n	width: 1px;\n	/* 1 */\n}\n\n/*\n * Extends the .sr-only class to allow the element\n * to be focusable when navigated to via the keyboard:\n * https://www.drupal.org/node/897638\n */\n\n.sr-only.focusable:active,\n.sr-only.focusable:focus {\n	clip: auto;\n	clip-path: none;\n	height: auto;\n	margin: 0;\n	overflow: visible;\n	position: static;\n	white-space: inherit;\n	width: auto;\n}\n\n/*\n * Hide visually and from screen readers, but maintain layout\n */\n\n.invisible {\n	visibility: hidden;\n}\n\n/*\n * Clearfix: contain floats\n *\n * For modern browsers\n * 1. The space content is one way to avoid an Opera bug when the\n *    `contenteditable` attribute is included anywhere else in the document.\n *    Otherwise it causes space to appear at the top and bottom of elements\n *    that receive the `clearfix` class.\n * 2. The use of `table` rather than `block` is only necessary if using\n *    `:before` to contain the top-margins of child elements.\n */\n\n.cf::before,\n.clearfix::before {\n	content: "" "";\n	display: table;\n}\n\n.cf::after,\n.clearfix::after {\n	content: " ";\n	display: table;\n	clear: both;\n}
EOT
);
$css->save();

$css = new CmsLayoutStylesheet();
$css->set_name('Print boilerplate');
$css->set_description('Default stylesheet for print devices');
$css->set_media_types('print');
$css->set_content(<<<'EOT'
/*! extract of HTML5 Boilerplate v8.0.0 | MIT License | https://html5boilerplate.com/ */\n\n@media print,\n	(-webkit-min-device-pixel-ratio: 1.25),\n	(min-resolution: 1.25dppx),\n	(min-resolution: 120dpi) {\n	/* Style adjustments for high resolution devices */\n}\n\n/* ==========================================================================\n   Print styles.\n   Inlined to avoid the additional HTTP request:\n   https://www.phpied.com/delay-loading-your-print-css/\n   ========================================================================== */\n\n@media print {\n	*,\n	*::before,\n	*::after {\n	background: #fff !important;\n	color: #000 !important;\n	/* Black prints faster */\n	box-shadow: none !important;\n	text-shadow: none !important;\n	}\n\n	a,\n	a:visited {\n	text-decoration: underline;\n	}\n\n	a[href]::after {\n	content: " (" attr(href) ")";\n	}\n\n	abbr[title]::after {\n	content: " (" attr(title) ")";\n	}\n\n	/*\n	* Don't show links that are fragment identifiers,\n	* or use the `javascript:` pseudo protocol\n	*/\n	a[href^="#"]::after,\n	a[href^="javascript:"]::after {\n	content: "";\n	}\n\n	pre {\n	white-space: pre-wrap !important;\n	}\n\n	pre,\n	blockquote {\n	border: 1px solid #999;\n	page-break-inside: avoid;\n	}\n\n	/*\n	* Printing Tables:\n	* https://web.archive.org/web/20180815150934/http://css-discuss.incutio.com/wiki/Printing_Tables\n	*/\n	thead {\n	display: table-header-group;\n	}\n\n	tr,\n	img {\n	page-break-inside: avoid;\n	}\n\n	p,\n	h2,\n	h3 {\n	orphans: 3;\n	widows: 3;\n	}\n\n	h2,\n	h3 {\n	page-break-after: avoid;\n	}\n}\n\nbody {\n	color: #000 !important; /* we want everything in black */\n	background-color: #fff !important; /* on white background */\n	font-family: arial; /* arial is nice to read ;) */\n	border: 0 !important; /* no borders thanks */\n}\n\n/* This affects every tag */\n* {\n	border: 0 !important; /* again no borders on printouts */\n}\n\n/*\nno need for accessibility on printout.\nMark all your elements in content you\ndon't want to get printed with class="noprint"\n*/\n.accessibility,\n.noprint {\n	display: none !important;\n}\n\nimg {\n	float: none; /* this makes image cause a pagebreak if it doesn't fit on the page */\n}
EOT
);
$css->save();

//
// Default Content Object
//
ContentOperations::get_instance()->LoadContentType('content');
$content = new Content();
$content->SetName('Home');
$content->SetAlias();
$content->SetOwner(1);
$content->SetMenuText('Home Page');
$content->SetTemplateId($template->get_id());
$content->SetParentId(-1);
$content->SetActive(TRUE);
$content->SetShowInMenu(TRUE);
$content->SetCachable(TRUE);
$content->SetDefaultContent(TRUE);
$content->SetPropertyValue('searchable',1);
$content->SetPropertyValue('design_id',$design->get_id());
$content->SetPropertyValue('content_en',
'<p>Congratulations! The installation worked. You now have a fully functional installation of CMS Made Simple and you are <em>almost</em> ready to start building your site.</p><p>If you chose to install the default content, you will see numerous pages available to read. You should read them thoroughly  as these default pages are devoted to showing you the basics of how to begin working with CMS Made Simple.  On these example pages, templates, and stylesheets many of the features of the default installation of CMS Made Simple are described and demonstrated. You can learn much about the power of CMS Made Simple by absorbing this information.</p><p>To get to the Administration Console you have to log in as the administrator (with the username/password you specified during the installation process) on your site at http://yourwebsite.com/cmsmspath/admin. If this is your site click <a title="CMSMS Demo Admin Panel" href="admin">here</a> to login.</p><p>Read about how to use CMS Made Simple in the <a class="external" href="https://docs.cmsmadesimple.org/" title="CMS Made Simple Documentation" target="_blank">documentation</a>. If you need any help the community is always at your service, in the  <a class="external" href="http://forum.cmsmadesimple.org" title="CMS Made Simple Forum" target="_blank">forum</a> or on the <a class="external" href="https://cms-made-simple.slack.com" target="_blank">CMSMS Slack channel</a> (after <a href="https://www.cmsmadesimple.org/support/documentation/chat" target="_blank">joining that channel</a>).</p>
<h3>License</h3>
<p>CMS Made Simple is released under the <a class="external" href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html" title="General Public License" target="_blank">GNU GPL</a>. Some independently-developed add-on modules might have different or additional license restrictions. Rarely, payment might be required to enable some or all of the capability of such a module.</p><p>The built site doesn\'t need to publicly acknowledge CMSMS but it would be friendly to do so.</p>
'
);
$content->Save();
?>
