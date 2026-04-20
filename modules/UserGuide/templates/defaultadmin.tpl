{if $pmod || $pset}
{tab_header name='list' label=$mod->Lang('tab_list') active=$tab}
{/if}
{if $pmod}
{tab_header name='transfer' label=$mod->Lang('tab_transfers') active=$tab}
{/if}
{if $pset}
{tab_header name='settings' label=$mod->Lang('tab_settings') active=$tab}
{/if}
{if $pmod || $pset}
{tab_start name='list'}
{/if}
{include file='module_file_tpl:UserGuide;guideslist.tpl' scope='root'}
{if $pmod}
{tab_start name='transfer'}
{include file='module_file_tpl:UserGuide;transfer.tpl' scope='root'}
{/if}
{if $pset}
{tab_start name='settings'}
{include file='module_file_tpl:UserGuide;settings.tpl' scope='root'}
{/if}
{if $pmod || $pset}
{tab_end}
{/if}
