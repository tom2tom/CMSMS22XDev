<script>
$(function() {
  $('img.viewhelp').on('click', function() {
    var n = $(this).attr('name');
    $('#'+n).dialog();
  });

  $(document).on('click','#clearlocks,#cssclearlocks',function(ev) {
     ev.preventDefault();
     var url = $(this).attr('href');
     cms_confirm("{$mod->Lang('confirm_clearlocks')|escape:'javascript'}").done(function() {
       window.location.href = url;
     });
     return false;
  });
});
</script>

{$showtabs=$manage_stylesheets||$manage_designs||$manage_templates}
{if $showtabs}
{tab_header name='templates' label=$mod->Lang('prompt_templates') active=$tab}
{/if}
{if $manage_stylesheets}
{tab_header name='stylesheets' label=$mod->Lang('prompt_stylesheets') active=$tab}
{/if}
{if $manage_designs}
{tab_header name='designs' label=$mod->Lang('prompt_designs') active=$tab}
{/if}
{if $manage_templates}
{tab_header name='types' label=$mod->Lang('prompt_templatetypes') active=$tab}
{tab_header name='categories' label=$mod->Lang('prompt_categories') active=$tab}
{/if}
{if $showtabs}
{tab_start name='templates'}
{/if}
{include file='module_file_tpl:DesignManager;admin_defaultadmin_templates.tpl' scope='root'}
{if $manage_stylesheets}
{tab_start name='stylesheets'}
	{include file='module_file_tpl:DesignManager;admin_defaultadmin_stylesheets.tpl' scope='root'}
{/if}
{if $manage_designs}
{tab_start name='designs'}
	{include file='module_file_tpl:DesignManager;admin_defaultadmin_designs.tpl' scope='root'}
{/if}
{if $manage_templates}
{tab_start name='types'}
	{include file='module_file_tpl:DesignManager;admin_defaultadmin_types.tpl' scope='root'}
{tab_start name='categories'}
	{include file='module_file_tpl:DesignManager;admin_defaultadmin_categories.tpl' scope='root'}
{/if}
{if $showtabs}
{tab_end}
{/if}
