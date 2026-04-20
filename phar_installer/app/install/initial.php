<?php
//global $admin_user;

//
// Design
//
$design = new CmsLayoutCollection();
$design->set_name('Default');
$design->set_description('Default design with just the default template');
$design->set_default();
$design->save();
$design->save();

//
// Template Types
//
$page_template_type = new CmsLayoutTemplateType();
$page_template_type->set_originator(CmsLayoutTemplateType::CORE);
$page_template_type->set_name('page');
$page_template_type->set_dflt_flag();
$page_template_type->set_lang_callback('CMSMS\internal\TemplateResource::page_type_lang_callback');
$page_template_type->set_content_callback('CMSMS\internal\TemplateResource::reset_page_type_defaults');
$page_template_type->reset_content_to_factory();
$page_template_type->set_content_block_flag();
$page_template_type->set_help_callback('CMSMS\internal\TemplateResource::template_help_callback');
$page_template_type->save();

$gcb_template_type = new CmsLayoutTemplateType();
$gcb_template_type->set_originator(CmsLayoutTemplateType::CORE);
$gcb_template_type->set_name('generic');
$gcb_template_type->set_lang_callback('CMSMS\internal\TemplateResource::generic_type_lang_callback');
$gcb_template_type->set_help_callback('CMSMS\internal\TemplateResource::template_help_callback');
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
$template->set_type_dflt();
$template->set_content($txt);
$template->add_design($design);
$template->set_owner(1);
$template->save();

//
// Stylesheets
//
$css = new CmsLayoutStylesheet();
$css->set_name('Boilerplate');
$css->set_description('Resets: normalise and html5 default');
$css->set_media_types('screen');
$css->set_content(<<<'EOT'
/*! normalise.css v11.0.1 | CC0-1.0 License | https://github.com/csstools/normalize.css */

/* Document
 * ========================================================================== */

/**
 * 1. Correct the line height in all browsers.
 * 2. Prevent adjustments of font size after orientation changes in
 *    IE on Windows Phone and in iOS.
 */

html {
	line-height: 1.15; /* 1 */
	-ms-text-size-adjust: 100%; /* 2 */
	-webkit-text-size-adjust: 100%; /* 2 */
}

/* Sections
 * ========================================================================== */

/**
 * Correct the font size and margin on `h1` elements within `section` and
 * `article` contexts in Chrome, Edge, Firefox, and Safari.
 */

h1 {
	font-size: 2em;
	margin: 0.67em 0;
}

/* Grouping content
 * ========================================================================== */

/**
 * Remove the margin on nested lists in Chrome, Edge, IE, and Safari.
 */

dl dl,
dl ol,
dl ul,
ol dl,
ul dl {
	margin: 0;
}

/**
 * Remove the margin on nested lists in Edge 18- and IE.
 */

ol ol,
ol ul,
ul ol,
ul ul {
	margin: 0;
}

/**
 * 1. Add the correct box sizing in Firefox.
 * 2. Correct the inheritance of border color in Firefox.
 * 3. Show the overflow in Edge 18- and IE.
 */

hr {
	box-sizing: content-box; /* 1 */
	color: inherit; /* 2 */
	height: 0; /* 1 */
	overflow: visible; /* 3 */
}

/**
 * Add the correct display in IE.
 */

main {
	display: block;
}

/**
 * 1. Correct the inheritance and scaling of font size in all browsers.
 * 2. Correct the odd `em` font sizing in all browsers.
 */

pre {
	font-family: monospace, monospace; /* 1 */
	font-size: 1em; /* 2 */
}

/* Text-level semantics
 * ========================================================================== */

/**
 * Remove the gray background on active links in IE 10.
 */

a {
	background-color: transparent;
}

/**
 * Add the correct text decoration in Edge 18-, IE, and Safari.
 */

abbr[title] {
	text-decoration: underline;
	text-decoration: underline dotted;
}

/**
 * Add the correct font weight in Chrome, Edge, and Safari.
 */

b,
strong {
	font-weight: bolder;
}

/**
 * 1. Correct the inheritance and scaling of font size in all browsers.
 * 2. Correct the odd `em` font sizing in all browsers.
 */

code,
kbd,
samp {
	font-family: monospace, monospace; /* 1 */
	font-size: 1em; /* 2 */
}

/**
 * Add the correct font size in all browsers.
 */

small {
	font-size: 80%;
}

/* Embedded content
 * ========================================================================== */

/**
 * Add the correct display in IE 9-.
 */

audio,
video {
	display: inline-block;
}

/**
 * Add the correct display in iOS 4-7.
 */

audio:not([controls]) {
	display: none;
	height: 0;
}

/**
 * Remove the border on images within links in IE 10-.
 */

img {
	border-style: none;
}

/**
 * Hide the overflow in IE.
 */

svg:not(:root) {
	overflow: hidden;
}

/* Tabular data
 * ========================================================================== */

/**
 * 1. Correct table border color inheritance in all Chrome, Edge, and Safari.
 * 2. Remove text indentation from table contents in Chrome, Edge, and Safari.
 */

table {
	border-color: inherit; /* 1 */
	text-indent: 0; /* 2 */
}

/* Forms
 * ========================================================================== */

/**
 * Remove the margin on controls in Safari.
 */

button,
input,
select {
	margin: 0;
}

/**
 * 1. Show the overflow in IE.
 * 2. Remove the inheritance of text transform in Edge 18-, Firefox, and IE.
 */

button {
	overflow: visible; /* 1 */
	text-transform: none; /* 2 */
}

/**
 * Correct the inability to style buttons in iOS and Safari.
 */

button,
[type="button"],
[type="reset"],
[type="submit"] {
	-webkit-appearance: button;
}

/**
 * Correct the padding in Firefox.
 */

fieldset {
	padding: 0.35em 0.75em 0.625em;
}

/**
 * Show the overflow in Edge 18- and IE.
 */

input {
	overflow: visible;
}

/**
 * 1. Correct the text wrapping in Edge 18- and IE.
 * 2. Correct the color inheritance from `fieldset` elements in IE.
 */

legend {
	box-sizing: border-box; /* 1 */
	color: inherit; /* 2 */
	display: table; /* 1 */
	max-width: 100%; /* 1 */
	white-space: normal; /* 1 */
}

/**
 * 1. Add the correct display in Edge 18- and IE.
 * 2. Add the correct vertical alignment in Chrome, Edge, and Firefox.
 */

progress {
	display: inline-block; /* 1 */
	vertical-align: baseline; /* 2 */
}

/**
 * Remove the inheritance of text transform in Firefox.
 */

select {
	text-transform: none;
}

/**
 * 1. Remove the margin in Firefox and Safari.
 * 2. Remove the default vertical scrollbar in IE.
 */

textarea {
	margin: 0; /* 1 */
	overflow: auto; /* 2 */
}

/**
 * 1. Add the correct box sizing in IE 10-.
 * 2. Remove the padding in IE 10-.
 */

[type="checkbox"],
[type="radio"] {
	box-sizing: border-box; /* 1 */
	padding: 0; /* 2 */
}

/**
 * 1. Correct the odd appearance in Chrome, Edge, and Safari.
 * 2. Correct the outline style in Safari.
 */

[type="search"] {
	-webkit-appearance: textfield; /* 1 */
	outline-offset: -2px; /* 2 */
}

/**
 * Correct the cursor style of increment and decrement buttons in Safari.
 */

::-webkit-inner-spin-button,
::-webkit-outer-spin-button {
	height: auto;
}

/**
 * Correct the text style of placeholders in Chrome, Edge, and Safari.
 */

::-webkit-input-placeholder {
	color: inherit;
	opacity: 0.54;
}

/**
 * Remove the inner padding in Chrome, Edge, and Safari on macOS.
 */

::-webkit-search-decoration {
	-webkit-appearance: none;
}

/**
 * 1. Correct the inability to style upload buttons in iOS and Safari.
 * 2. Change font properties to `inherit` in Safari.
 */

::-webkit-file-upload-button {
	-webkit-appearance: button; /* 1 */
	font: inherit; /* 2 */
}

/**
 * Remove the inner border and padding of focus outlines in Firefox.
 */

::-moz-focus-inner {
	border-style: none;
	padding: 0;
}

/**
 * Restore the focus outline styles unset by the previous rule in Firefox.
 */

:-moz-focusring {
	outline: 1px dotted ButtonText;
}

/**
 * Remove the additional :invalid styles in Firefox.
 */

:-moz-ui-invalid {
	box-shadow: none;
}

/* Interactive
 * ========================================================================== */

/*
 * Add the correct display in Edge 18- and IE.
 */

details {
	display: block;
}

/*
 * Add the correct styles in Edge 18-, IE, and Safari.
 */

dialog {
	background-color: white;
	border: solid;
	color: black;
	display: block;
	height: -moz-fit-content;
	height: -webkit-fit-content;
	height: fit-content;
	left: 0;
	margin: auto;
	padding: 1em;
	position: absolute;
	right: 0;
	width: -moz-fit-content;
	width: -webkit-fit-content;
	width: fit-content;
}

dialog:not([open]) {
	display: none;
}

/*
 * Add the correct display in all browsers.
 */

summary {
	display: list-item;
}

/* Scripting
 * ========================================================================== */

/**
 * Add the correct display in IE 9-.
 */

canvas {
	display: inline-block;
}

/**
 * Add the correct display in IE.
 */

template {
	display: none;
}

/* User interaction
 * ========================================================================== */

/**
 * Add the correct display in IE 10-.
 */

[hidden] {
	display: none;
}

/*! HTML5 Boilerplate v8.0.0 | MIT License | https://html5boilerplate.com/ */
/* with supplementary clip-path styles and .cf replicating .clearfix and omitting all print classes */
/*
 * What follows is the result of much research on cross-browser styling.
 * Credit left inline and big thanks to Nicolas Gallagher, Jonathan Neal,
 * Kroc Camen, and the H5BP dev community and team.
 */

/* ==========================================================================
   Base styles: opinionated defaults
   ========================================================================== */

html {
	color: #222;
	font-size: 1em;
	line-height: 1.4;
}

/*
 * Remove text-shadow in selection highlight:
 * https://twitter.com/miketaylr/status/12228805301
 *
 * Vendor-prefixed and regular ::selection selectors cannot be combined:
 * https://stackoverflow.com/a/16982510/7133471
 *
 * Customize the background color to match your design.
 */

::-moz-selection {
	background: #b3d4fc;
	text-shadow: none;
}

::selection {
	background: #b3d4fc;
	text-shadow: none;
}

/*
 * A better looking default horizontal rule
 */

hr {
	display: block;
	height: 1px;
	border: 0;
	border-top: 1px solid #ccc;
	margin: 1em 0;
	padding: 0;
}

/*
 * Remove the gap between audio, canvas, iframes,
 * images, videos and the bottom of their containers:
 * https://github.com/h5bp/html5-boilerplate/issues/440
 */

audio,
canvas,
iframe,
img,
svg,
video {
	vertical-align: middle;
}

/*
 * Remove default fieldset styles.
 */

fieldset {
	border: 0;
	margin: 0;
	padding: 0;
}

/*
 * Allow only vertical resizing of textareas.
 */

textarea {
	resize: vertical;
}

/* ==========================================================================
   Helper classes
   ========================================================================== */

/*
 * Hide visually and from screen readers
 */

.hidden,
[hidden] {
	display: none !important;
}

/*
 * Hide only visually, but have it available for screen readers:
 * https://snook.ca/archives/html_and_css/hiding-content-for-accessibility
 *
 * 1. For long content, line feeds are not interpreted as spaces and small width
 *    causes content to wrap 1 word per line:
 *    https://medium.com/@jessebeach/beware-smushed-off-screen-accessible-text-5952a4c2cbfe
 */

.sr-only {
	border: 0;
	clip: rect(0 0 0 0);
	clip: rect(0, 0, 0, 0);
	clip-path: inset(50%);
	height: 1px;
	margin: -1px;
	overflow: hidden;
	padding: 0;
	position: absolute;
	white-space: nowrap;
	width: 1px;
	/* 1 */
}

/*
 * Extends the .sr-only class to allow the element
 * to be focusable when navigated to via the keyboard:
 * https://www.drupal.org/node/897638
 */

.sr-only.focusable:active,
.sr-only.focusable:focus {
	clip: auto;
	clip-path: none;
	height: auto;
	margin: 0;
	overflow: visible;
	position: static;
	white-space: inherit;
	width: auto;
}

/*
 * Hide visually and from screen readers, but maintain layout
 */

.invisible {
	visibility: hidden;
}

/*
 * Clearfix: contain floats
 *
 * For modern browsers
 * 1. The space content is one way to avoid an Opera bug when the
 *    `contenteditable` attribute is included anywhere else in the document.
 *    Otherwise it causes space to appear at the top and bottom of elements
 *    that receive the `clearfix` class.
 * 2. The use of `table` rather than `block` is only necessary if using
 *    `:before` to contain the top-margins of child elements.
 */

.cf::before,
.clearfix::before {
	content: "" "";
	display: table;
}

.cf::after,
.clearfix::after {
	content: " ";
	display: table;
	clear: both;
}
EOT
);
$css->save();

$css = new CmsLayoutStylesheet();
$css->set_name('Print boilerplate');
$css->set_description('Default stylesheet for print devices');
$css->set_media_types('print');
$css->set_content(<<<'EOT'
/*! extract of HTML5 Boilerplate v8.0.0 | MIT License | https://html5boilerplate.com/ */

@media print,
	(-webkit-min-device-pixel-ratio: 1.25),
	(min-resolution: 1.25dppx),
	(min-resolution: 120dpi) {
	/* Style adjustments for high resolution devices */
}

/* ==========================================================================
   Print styles.
   Inlined to avoid the additional HTTP request:
   https://www.phpied.com/delay-loading-your-print-css/
   ========================================================================== */

@media print {
	*,
	*::before,
	*::after {
	background: #fff !important;
	color: #000 !important;
	/* Black prints faster */
	box-shadow: none !important;
	text-shadow: none !important;
	}

	a,
	a:visited {
	text-decoration: underline;
	}

	a[href]::after {
	content: " (" attr(href) ")";
	}

	abbr[title]::after {
	content: " (" attr(title) ")";
	}

	/*
	* Don't show links that are fragment identifiers,
	* or use the `javascript:` pseudo protocol
	*/
	a[href^="#"]::after,
	a[href^="javascript:"]::after {
	content: "";
	}

	pre {
	white-space: pre-wrap !important;
	}

	pre,
	blockquote {
	border: 1px solid #999;
	page-break-inside: avoid;
	}

	/*
	* Printing Tables:
	* https://web.archive.org/web/20180815150934/http://css-discuss.incutio.com/wiki/Printing_Tables
	*/
	thead {
	display: table-header-group;
	}

	tr,
	img {
	page-break-inside: avoid;
	}

	p,
	h2,
	h3 {
	orphans: 3;
	widows: 3;
	}

	h2,
	h3 {
	page-break-after: avoid;
	}
}

body {
	color: #000 !important; /* we want everything in black */
	background-color: #fff !important; /* on white background */
	font-family: arial; /* arial is nice to read ;) */
	border: 0 !important; /* no borders thanks */
}

/* This affects every tag */
* {
	border: 0 !important; /* again no borders on printouts */
}

/*
no need for accessibility on printout.
Mark all your elements in content you
don't want to get printed with class="noprint"
*/
.accessibility,
.noprint {
	display: none !important;
}

img {
	float: none; /* this makes image cause a pagebreak if it doesn't fit on the page */
}
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
'<p>Congratulations! The installation worked. You now have a fully functional installation of CMS Made Simple and you are <em>almost</em> ready to start building your site.</p>
<p>To get to the Administration Console you have to log in as the administrator (with the username/password you specified during the installation process) on your site at http://yourwebsite.com/cmsmspath/admin. If this is your site click <a title="CMSMS Demo Admin Panel" href="admin">here</a> to log in.</p>
<p>Read about how to use CMS Made Simple in the <a class="external" href="https://docs.cmsmadesimple.org/" title="CMS Made Simple Documentation" target="_blank">documentation</a>. If you need any help the community is always at your service, in the  <a class="external" href="https://forum.cmsmadesimple.org" title="CMS Made Simple Forum" target="_blank">forum</a> or on the <a class="external" href="https://cms-made-simple.slack.com" target="_blank">CMSMS Slack channel</a> (after <a href="https://www.cmsmadesimple.org/support/documentation/chat" target="_blank">joining that channel</a>).</p>
<h3>License</h3>
<p>CMS Made Simple is released under the <a class="external" href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html" title="General Public License" target="_blank">GNU GPL</a>. Some independently-developed add-on modules might have different or additional license restrictions. Rarely, payment might be required to enable some or all of the capability of such a module.</p>
<p>The built site doesn\'t need to publicly acknowledge CMSMS but it would be friendly to do so.</p>
'
);
$content->Save();
?>
