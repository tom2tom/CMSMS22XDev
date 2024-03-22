<?php
/*
This file is part of CMS Made Simple module: UserGuide
Copyright (C) 2024 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
Refer to license and other details at the top of file UserGuide.module.php
*/
$lang['add_item'] = 'Add New Guide';
$lang['admin'] = 'Admin Only';
$lang['adminSection'] = 'Admin-Menu Section';
$lang['admin_only_visible'] = 'viewable only in the Admin Console';
$lang['admindescription'] = 'For displaying and editing a customisable CMSMS User Guide';
$lang['adminsearch_desc'] = 'Search all guides regardless of status';
$lang['adminsearch_lbl'] = 'Search User Guides';
$lang['ask_uninstall'] = 'Are you sure you want to uninstall the UserGuide module? All guidance information will be permanently deleted.';
$lang['confirm_delete'] = 'Are you sure that you want to delete User Guide "%s"?';
$lang['customCSS'] = 'Use Custom Admin Styling';
$lang['customLabel'] = 'Custom Admin-Menu Label';
$lang['delete'] = 'Delete this guide';
$lang['display'] = 'Display this guide';
$lang['edit'] = 'Edit this guide';
$lang['edit_item'] = 'Edit User Guide'; //page heading
$lang['err_export'] = 'Export error';
$lang['err_import'] = 'Import error - file did not import correctly';
$lang['err_nofile'] = 'Filename error - not selected';
$lang['err_noname'] = 'A name is required';
$lang['err_smarty'] = 'This guide might include a Smarty tag that is not displaying correctly. Please either add {literal}{/literal} tags, remove, or turn off Smarty processing.';
$lang['export_archive'] = 'Gzip Archive';
$lang['export_completed'] = 'User Guides have been exported';
$lang['export_xml'] = 'XML File';
$lang['exportdata'] = 'Export Guides Data';
$lang['filesFolder'] = 'Media Files Folder';
$lang['friendlyname'] = 'User Guidance';
$lang['frontend_styles'] = 'Frontend Display Stylesheet(s)';
$lang['frontend_template'] = 'Frontend Display Template';
$lang['guideStyles'] = 'Default Stylesheet for Displayed Guide';
$lang['help_customCSS'] = 'Whether to use custom styling for guides displayed in the Admin Console. If enabled, you must also record the styles in file \'&lt;site-root&gt;/assets/module_custom/UserGuide/custom.css\'. You can use the .guide class to target the User Guide content.';
$lang['help_customlabel'] = 'Enter a label for the admin menu item which activates this module. Or if empty, the module-default label will be used. A custom label might be most appropriate in \'non-display-related\' sections e.g. \'My Preferences\'';
$lang['help_defaultSmarty'] = 'The default setting for whether to process a guide through Smarty before display. Each guide may override this setting.';
$lang['help_filesFolder'] = 'Enter the name of a folder (relative to the configured site-image-uploads folder) in which to store UserGuide images and other media files e.g. "UserGuide". Or if empty, media files will not be exported or imported. Imported files would normally be wanted, in which case create that folder if it does not exist before the import.';
$lang['help_guideStyles'] = 'Enter the name of a recorded stylesheet to be applied to frontend guides if no sheet is specified';
$lang['help_listStyles'] = 'Enter the name of a recorded stylesheet to be applied to frontend guide-lists if no sheet is specified';
$lang['help_menusection'] = 'Choose the section of the Admin Console menu in which the UserGuide item will be displayed. This choice might be related to the type of guidance which is available. The choice might affect users\' ability to use this module, depending on site permissions.';
$lang['help_propadmin'] = 'Whether to restrict display of this guide to the site Admin Console i.e. no frontend display';
$lang['help_propauthor'] = 'This optional property is mainly for recording the name of the original creator of this guide. Especially so if that creator is/was not a registered Admin user (e.g. the site developer). Subsequent editors might consider it appropriate to replace this name. If so, emptying this field will automatically display the most recent editor\'s recorded name to other editors, and display an empty value (representing self) to that same editor.';
$lang['help_proprestrict'] = 'Whether to restrict display of this guide to suitably authorised users in the site Admin Console. Particular types of restriction are not yet supported. Until then, any choice other than \'No Restriction\' will restrict access.';
$lang['help_proprevision'] = 'Optionally enter here something to help distinguish different versions of the same guide, for document control';
$lang['help_propsmarty'] = 'Whether to process this guide through Smarty before display. If so, any curly bracket { } without surrounding whitespace in the content must be escaped by surrounding with {literal}{/literal} tags.';
$lang['help_propstyles'] = 'Each guide which can be displayed in the frontend may have one or more stylesheets for that. Select one or more of these stylesheets. Drag and drop rows to change their order. The selected item(s) will be applied to the guide in the same order as they are displayed here.';
$lang['help_proptemplate'] = 'Each guide which can be displayed in the frontend may have a non-default template for that. Do not select one that is intended for a guides-list display.';
$lang['id'] = 'ID';
$lang['import'] = 'Import';
$lang['import_UserGuide2'] = 'Import Guide(s) and Settings from UserGuide2 Module';
$lang['import_UsersGuide'] = 'Import Guide(s) and Settings from Legacy UsersGuide Module';
$lang['import_completed'] = 'User guides have been imported. Almost certainly, their frontend templates and stylesheets will need to be adjusted. Use DesignManager to set the type of each imported template, if any.';
$lang['importdata'] = 'Import Guides Data';
$lang['info_tpldefault'] = '<strong>Default templates</strong> for diplayed guides and lists are determined by the \'default\' property of recorded templates in the corresponding UserGuide-related template-types.';
$lang['item_deleted'] = 'The User Guide has been deleted';
$lang['item_notsaved'] = 'The User Guide was not saved'; // displayed after redirection
$lang['item_notsaved2'] = 'This User Guide was not saved'; // displayed after error without redirection
$lang['item_saved'] = 'The User Guide has been saved';
$lang['listStyles'] = 'Default Stylesheet for Displayed List';
$lang['modified'] = 'Latest Modification';
$lang['msg_cancelled'] = 'Operation canceled';
$lang['no_guide'] = 'No guide is recorded';
$lang['no_sheet'] = 'No suitable stylesheet is recorded';
$lang['no_template'] = 'No suitable template is recorded';
$lang['no_importing'] = 'PHP\'s import-related capabilities are not available';
$lang['param_guideid'] = 'The numeric id (&gt; 0) of a single guide to display';
$lang['param_list'] = 'A comma-separated series of numeric id\'s of guides to list, or empty or \'*\' to list everything. If neither \'gid\' or \'list\' is specified, list all is assumed.';
$lang['param_stylesheet_name'] = 'The name of a stylesheet to use for formatting the displayed list. (Guide stylesheets are recorded with respective guides.)';
$lang['param_stylesheetid'] = 'The numeric id of a stylesheet to use for formatting the displayed list. (Guide stylesheets are recorded with respective guides.)';
$lang['param_template_name'] = 'The name of a template to use for layout of the displayed list. (A single-guide template is recorded with each guide.)';
$lang['param_templateid'] = 'The numeric id of a template to use for layout of the displayed list. (A single-guide template is recorded with each guide.)';
$lang['restrict_none'] = 'No Restriction';
$lang['restrict_perm'] = 'Any RestrictView-Permitted User';
$lang['restrict_typed'] = 'Any %s User';
$lang['restricted'] = 'Restricted Access';
$lang['revision'] = 'Revision';
$lang['save'] = 'Save';
$lang['searchable'] = 'Searchable';
$lang['selectfile'] = 'Select File';
$lang['settings_saved'] = 'The settings have been saved.';
$lang['tab_content'] = 'Content';
$lang['tab_list'] = 'Guides';
$lang['tab_properties'] = 'Properties';
$lang['tab_settings'] = 'Settings';
$lang['tab_transfers'] = 'Import/Export';
$lang['toggle_active'] = 'Click to toggle this guide\'s \'active\' status'; // link title attribute
$lang['toggle_admin'] = 'Click to toggle this guide\'s \'admin-only\' status'; // link title attribute
$lang['toggle_search'] = 'Click to toggle this guide\'s \'searchable\' status'; // link title attribute
$lang['type_listguides'] = 'List';
$lang['type_oneguide'] = 'Guide';
$lang['type_UserGuide'] = 'UserGuide';
$lang['useSmarty'] = 'Use Smarty';//guide settings label
$lang['useSmartydefault'] = 'Default Use Smarty';//module settings label
$lang['useWysiwyg'] = 'Use the HTML Editor for Content Editing';

$lang['help'] = <<<'EOD'
<h3>What does this do?</h3>
<p>It provides a capable and flexible platform for displaying guidance and help, in the website frontend and/or Admin Console. It is like a customised FAQ or news module.</p>
<p>Features include:</p>
<ul>
  <li>Unlimited number of distinct guides</li>
  <li>Easily add, edit, delete, rename guides</li>
  <li>Flag guides for 'admin-only' visibility</li>
  <li>Frontend display initiated by {UserGuide} tags</li>
  <li>Flag guides as disabled/inactive</li>
  <li>Guide contents can be processed by Smarty before display. If that's enabled, it may be necessary to use {literal}{/literal} or {ldelim}{rdelim} tags in the guide content.</li>
  <li>Support (media) files can be uploaded locally</li>
  <li>Choice of admin menu section</li>
  <li>Custom admin menu label</li>
  <li>Custom styling</li>
  <li>Content import/export</li>
</ul>
<p>Inspired by Chris Taylor's <a href="http://dev.cmsmadesimple.org/projects/userguide2" target="_blank">Userguide2 module</a> and Jean-Christophe Ghio's elderly <a href="http://dev.cmsmadesimple.org/projects/usersguide" target="_blank">UsersGuide module</a>.</p>
<h3>Getting Started</h3>
<p>1. Install the module</p>
<p>2. Grant 'UserGuide - Manage' permission to relevant users.</p>
<p>3. Grant 'UserGuide - Settings' permission to relevant users.</p>
<p>4. Grant 'UserGuide - RestrictView' permission to relevant users.</p>
<h3>Printing a Guide</h3>
<p>Select Print in the browser menu or press 'Ctrl + P'.</p>
<p>Tip: Select the option to print 'Headers & Footers' (if available)</p>
<h3>Frontend Display</h3>
<p>To display frontend help, include among the content of relevant pages a tag</p>
<pre>{UserGuide}</pre> or like<br>
<pre>{UserGuide params}</pre>
<p>Refer to the Parameters section below, for details about tag parameters.</p>
<h3>Frontend Styling</h3>
<p>Each guide may have one or more stylesheets associated with it. Otherwise, a default will be applied.</p>
<p>The name of each frontend stylesheet to be used with this module must <strong>start with UserGuide_</strong>.</p>
<h3>Custom Styling in Admin Console</h3>
<p>To add custom styles for user guides, create the file '&lt;site-root&gt;/assets/module_custom/UserGuide/custom.css'. This will be applied in addition to admin-theme and UserGuide-module styling.</p>
<p>You can use the '.guide' class in that file to target User Guide content.</p>
<h3>Import/Export</h3>
<p>The collection of all current guides and related data can easily be exported. Depending on available PHP functionality, export may be in the form of an XML file or a GZIP archive.</p>
<p>Previously-exported guides and related data can easily be imported.</p>
<p>If a 'Media Files Folder' is set then any export will include all files (images etc) in that folder. When importing, any media files will be stored in that same folder.</p>
<h4>Import from UserGuide2 and/or UsersGuide module</h4>
<p>If the UserGuide2 module is installed, an option will be shown to import all content from that module.</p>
<p>If the legacy (unmaintained) UsersGuide module is installed, an option will be shown to import all content from that module.</p>
<h3>Permissions</h3>
<ul>
  <li>'UserGuide - Manage' - can do everything specific to this module.</li>
  <li>'UserGuide - RestrictView' - can view Admin Console guides which are flagged as restricted.</li>
  <li>'UserGuide - Settings' - can modify this module's settings.</li>
</ul>
<p>All Admin Console users are automatically permitted to view the content of all active guides.</p>
<p>Any guide whose content is not for general consumption should be flagged as non-searchable and restricted display.</p>
<h3>Support</h3>
<p>As per the GPL, this software is provided as is. Please read the text of that license for the full disclaimer.</p>
<p>For support:</p>
<ul>
  <li>first, search the <a href="https://forum.cmsmadesimple.org" target="_blank">CMS Made Simple Forum</a>, for issues with the module similar to those you are finding</li>
  <li>then, if necessary, open a <strong>new forum topic</strong> to request help, with a thorough description of your issue, and steps to reproduce it</li>
  <li>or post that same information to the <a href="https://www.cmsmadesimple.org/support/documentation/chat" target="_blank">CMSMS Slack channel</a>.</li>
</ul>
<p>If you find a bug you can <a href="http://dev.cmsmadesimple.org/bug/list/6" target="_blank">submit a Bug Report</a>.</p>
<p>For any good ideas you can <a href="http://dev.cmsmadesimple.org/feature_request/list/6" target="_blank">submit a Feature Request</a>.</p>
<h3>Copyright &amp; License</h3>
%s
EOD;
