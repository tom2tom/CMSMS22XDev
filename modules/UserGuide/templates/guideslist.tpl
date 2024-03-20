{if $pmod && !empty($guides)}
<script>
 $(function() {
  $('a.del_guide').on('click',function(ev) {
   ev.preventDefault();
   var self = $(this);
   var nm = self.data('title');
   var msg = '{$mod->Lang("confirm_delete")|escape:"javascript"}'.replace('%s', nm);
   cms_confirm(msg).done(function() {
    window.location.href = self.attr('href');
   });
  });
  $('.sortable-list').sortable({
   delay: 150,
   revert: 300,
   containment: 'parent',
   update: function(ev, ui) {
    $(ui.item).parent().children().each(function(index) {
     $(this).removeClass('row1 row2').addClass('row'+(index%2+1));
    });
    var gids = [];
    $.each($('.itemid'), function(idx, el) {
     gids.push(parseInt(el.textContent, 10));
    });
    $('#loader').show();//CSS spinner OR cms_busy()
    var joined = JSON.stringify(gids),
      url = '{$reorder_url}'.replace('XXX', joined);
    $.get(url).always(function() {
     $('#loader').hide();//OR cms_busy(false)
    });
   }
  });
 });
</script>
{/if}
<div class="guide">
{if $pmod}
  <span id="loader" style="display:none"> </span>{/if}
{if !empty($guides)}
  <span class="admin-only">* = {$mod->Lang('admin_only_visible')}</span>
{/if}
{if $pmod}
  {$addname = $mod->Lang('add_item')}
  <div class="pageoptions"><a href="{cms_action_url action=edit gid=0}">{admin_icon icon='newobject.gif'} {$addname}</a></div>
{else}
<br>
{/if}
{if !empty($guides)}
  <table class="pagetable" style="width:max-content">
    <thead>
    <tr>
{if $pmod}      <th>{$mod->Lang('id')}</th>
{/if}
      <th>{lang('name')}</th>
      <th>{$mod->Lang('revision')}</th>
      <th>{$mod->Lang('modified')}</th>
      <th class="pageicon">{lang('active')}</th>
      <th class="pageicon">{$mod->Lang('searchable')}</th>
      <th class="pageicon" title="{$mod->Lang('admin_only_visible')}">{$mod->Lang('admin')} *</th>
      <th class="pageicon"></th>{*view icon*}
{if $pmod}      <th class="pageicon"></th>{*edit icon*}
      <th class="pageicon"></th>{/if}{*delete icon*}
    </tr>
    </thead>
    <tbody class="sortable-list">{if $pmod}{assign 'aname' 'edit'}{assign 'tkey' 'edit'}{else}{assign 'aname' 'view'}{assign 'tkey' 'display'}{/if}
{foreach $guides as $one}{cms_action_url action=$aname gid=$one->id assign='action_url'}
    <tr class="{if $one@index is even}row1{else}row2{/if}">
{if $pmod}      <td class="itemid">{$one->id}</td>
{/if}
      <td>{if $pmod || $one->active}<a href="{$action_url}" title="{$mod->Lang($tkey)}">{/if}{$one->name}{if $pmod || $one->active}</a>{/if}</td>
      <td>{$one->revision}</td>
      <td>{if $one->modified_date}{$one->modified_date|cms_date_format}{elseif $one->create_date}{$one->create_date|cms_date_format}{else}--{/if}</td>
      <td class="pagepos">{if $pmod}<a class="active_guide" href="{cms_action_url action=toggle_active gid=$one->id}" title="{$mod->Lang('toggle_active')}">{/if}{if $one->active}{admin_icon icon='true.gif'}{else}{admin_icon icon='false.gif'}{/if}{if $pmod}</a>{/if}
      </td>
      <td class="pagepos">{if $pmod}<a class="search_guide" href="{cms_action_url action=toggle_search gid=$one->id}" title="{$mod->Lang('toggle_search')}">{/if}{if $one->search}{admin_icon icon='true.gif'}{else}{admin_icon icon='false.gif'}{/if}{if $pmod}</a>{/if}
      </td>
      <td class="pagepos">{if $pmod}<a class="admin_guide" href="{cms_action_url action=toggle_admin gid=$one->id}" title="{$mod->Lang('toggle_admin')}">{/if}{if $one->admin}{admin_icon icon='true.gif'}{else}{admin_icon icon='false.gif'}{/if}{if $pmod}</a>{/if}
      </td>
      <td>{if $one->active}<a href="{cms_action_url action='view' gid=$one->id}" title="{$mod->Lang('display')}">{admin_icon icon='view.gif'}</a>{/if}</td>
{if $pmod}      <td><a href="{$action_url}" title="{$mod->Lang('edit')}">{admin_icon icon='edit.gif'}</a></td>
      <td><a class="del_guide" href="{cms_action_url action=delete gid=$one->id}" title="{$mod->Lang('delete')}" data-title="{$one->name}">{admin_icon icon='delete.gif'}</a></td>
{/if}
    </tr>
{/foreach}
    </tbody>
  </table>
{if $pmod && count($guides) > 10}
  <div class="pageoptions"><a href="{cms_action_url action=edit gid=0}">{admin_icon icon='newobject.gif'} {$addname}</a></div>
{/if}
{else}
  <p class="information">{$mod->Lang('no_guide')}</p>
{/if}
</div>
