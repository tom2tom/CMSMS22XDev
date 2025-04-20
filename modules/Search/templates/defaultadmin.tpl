{tab_header name='statistics' label=$mod->Lang('statistics') active=$tab}
{tab_header name='settings' label=$mod->Lang('settings') active=$tab}
{tab_start name='statistics'}
{include file='module_file_tpl:Search;statistics_tab.tpl' scope='root'}
{tab_start name='settings'}
{include file='module_file_tpl:Search;options_tab.tpl' scope='root'}
{tab_end}
