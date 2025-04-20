{tab_header name='general' label=$mod->Lang('prompt_general') active=$tab}
{tab_header name='listsettings' label=$mod->Lang('prompt_listsettings') active=$tab}
{tab_header name='pagedefaults' label=$mod->Lang('prompt_pagedefaults') active=$tab}
{tab_start name='general'}
{include file='module_file_tpl:CMSContentManager;admin_general_tab.tpl'}
{tab_start name='listsettings'}
{include file='module_file_tpl:CMSContentManager;admin_listsettings_tab.tpl'}
{tab_start name='pagedefaults'}
{include file='module_file_tpl:CMSContentManager;admin_pagedefaults_tab.tpl'}
{tab_end}
