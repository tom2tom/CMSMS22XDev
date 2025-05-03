{tab_header name='categories' label=$mod->Lang('categories') active=$tab}
{tab_header name='customfields' label=$mod->Lang('customfields') active=$tab}
{tab_header name='settings' label=$mod->Lang('settings') active=$tab}
{tab_start name='categories'}
{include file='module_file_tpl:News;categorylist.tpl'}
{tab_start name='customfields'}
{include file='module_file_tpl:News;customfieldstab.tpl'}
{tab_start name='settings'}
{include file='module_file_tpl:News;adminprefs.tpl'}
{tab_end}
