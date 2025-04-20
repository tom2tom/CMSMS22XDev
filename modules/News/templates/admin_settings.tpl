{tab_header name='categories' label=$mod->Lang('categories') active=$tab}
{tab_header name='customfields' label=$mod->Lang('customfields') active=$tab}
{tab_header name='options' label=$mod->Lang('options') active=$tab}
{tab_start name='categories'}
{include file='module_file_tpl:News;categorylist.tpl'}
{tab_start name='customfields'}
{include file='module_file_tpl:News;customfieldstab.tpl'}
{tab_start name='options'}
{include file='module_file_tpl:News;adminprefs.tpl'}
{tab_end}
