{if !empty($all_templates)}
<script>
function find_sortable_focus(in_e) {
  var _list = $(':tabbable');
  var _idx = _list.index(in_e);
  var _out_e = _list.eq(_idx+1).length ? _list.eq(_idx+1) : _list.eq(0);
  _out_e.trigger('focus');
}
$(function() {
  var _manage_templates = '{$manage_templates}';
  var _edit_url = '{cms_action_url action=admin_edit_template tpl=xxxx forjs=1}';
  //TODO standard use of JQUI .draggable and .droppable would assist maintainability
  $('ul.sortable-templates').sortable({
    connectWith: '#selected-templates ul',
    delay: 150,
    revert: true,
    placeholder: 'ui-state-highlight',
    items: 'li:not(.no-sort)',
    helper: function (event, ui) {
      if (!ui.hasClass('selected')) {
        ui.addClass('selected')
          .siblings()
          .removeClass('selected');
      }
      var elements = ui.parent()
               .children('.selected')
               .clone(),
        helper = $('<li></li>');
      ui.data('multidrag', elements).siblings('.selected').remove();
      return helper.append(elements);
    },
    stop: function (event, ui) {
      var elements = ui.item.data('multidrag');
      ui.item.after(elements).remove();
    },
    receive: function(event, ui) {
      var elements = ui.item.data('multidrag');
      $('.sortable-templates .placeholder').hide();
      $(elements).each(function() {
        var _t = $(this);
        var _tpl_id = _t.data('cmsms-item-id');
        var _url = _edit_url.replace('xxxx',_tpl_id);
        var _text = _t.text().trim();
        var _e;
        if( _manage_templates ) {
          _e = $('<a></a>', {
            href:_url,
            'class':'edit_tpl unsaved',
//          title:"{$mod->Lang('edit_template')}",
            text:_text
          });
        } else {
          _e = $('<span></span>', { text:_text });
        }
        $('span',this).remove();
        _t.append(_e);
        _t.removeClass('selected ui-state-hover')
             .attr('tabindex',-1)
             .addClass('unsaved')
             .append($('<a></a>', {
              href:'javascript:void(0)',
              'class':'sortable-remove',
              title:'{$mod->Lang("remove")}',
              html:'<img src="{$icon_delete}" alt="{$mod->Lang('remove')}">'
             }))
             .find('input[type="checkbox"]').attr('checked', true);
      });
      set_changed(); //see edit_design script
    }
  });

  $('#available-templates').on('click', 'li', function() {
    $(this).trigger('focus');
  }).on('keyup', 'li', function(ev) {
    if( ev.keyCode == $.ui.keyCode.ESCAPE ) {
      // escape
      $('#available-templates li').removeClass('selected');
      ev.preventDefault();
    } else if( ev.keyCode == $.ui.keyCode.SPACE || ev.keyCode == 107 ) {
      // spacebar or plus
      console.debug('selected');
      ev.preventDefault();
      $(this).toggleClass('selected ui-state-hover');
      find_sortable_focus(this);
    } else if( ev.keyCode == 39 ) {
      // right arrow
      $('#available-templates li.selected').each(function() {
        var _t = $(this);
        _t.removeClass('selected');
        var _tpl_id = _t.data('cmsms-item-id');
        var _url = _edit_url.replace('xxxx',_tpl_id);
        var _text = _t.text().trim();
        var _el = _t.clone();
        var _a;
        if( _manage_templates ) {
          _a = $('<a></a>', {
            href:_url,
            'class':'edit_tpl unsaved',
//          title:'{$mod->Lang('edit_template')}',
            text:_text
          });
        } else {
          _a = $('<span></span>', { text:_text });
        }
        $('span',_el).remove();
        $(_el).append(_a);
        $(_el).removeClass('selected ui-state-hover')
           .attr('tabindex',-1)
           .addClass('unsaved')
           .append($('<a></a>', {
             href:'javascript:void(0)',
             'class':'sortable-remove',
             title:'{$mod->Lang('remove')}',
             html:'<img src="{$icon_delete}" alt="{$mod->Lang('remove')}">'
           }))
           .find('input[type="checkbox"]').attr('checked', true);
        $('#selected-templates > ul').append(_el);
        _t.remove();
        set_changed();
        // set focus somewhere
        find_sortable_focus(this);
      });
      console.debug('got arrow');
    }
  });

  $('#selected-templates').on('click', 'li', function() {
    $('a',this).first().trigger('focus');
  }).on('click', '.sortable-remove', function(ev) { //TODO support DnD back to #available-templates
    // click on remove icon
    ev.preventDefault();
    set_changed();
    var _t = $(this);
    _t.next().attr('checked', false); // next is a hidden checkbox
    _t.parent('li').appendTo('#available-templates ul');
    _t.remove();
  });
/* no in-design tpl editing
  $(document).on('click', '.edit_tpl', function(ev) {
    if( __changed ) { // see edit_design script
      ev.preventDefault();
      var _url = $(this).attr('href');
      cms_confirm('{$mod->Lang('confirm_save_design')}').done(function() {
        // save and redirect
        save_design().done(function() { //see edit_design script
          window.location.href = _url;
        });
      });
    }
    // normal default link behavior
  });
*/
});
</script>

<p class="information">{$mod->Lang('info_edittemplate_templates_tab')}</p>
<br>
<div class="c_full cf" id="template_sel">{$tmpl=$design->get_templates()}
  <div class="grid_6 draggable-area">
    <fieldset>
      <legend>{$mod->Lang('available_templates')}</legend>
      <div id="available-templates">
        <ul class="sortable-templates sortable-list available-items available-templates">
        {foreach $all_templates as $tid => $tname}
          {if !$tmpl || !in_array($tid,$tmpl)}
          <li class="ui-state-default" data-cmsms-item-id="{$tid}" tabindex="0">
            <span>{$tname}</span>
            <input class="hidden" type="checkbox" name="{$actionid}assoc_tpl[]" value="{$tid}">
          </li>
          {/if}
        {/foreach}
        </ul>
      </div>
    </fieldset>
  </div>
  <div class="grid_6">
    <fieldset>
      <legend>{$mod->Lang('attached_templates')}</legend>
      <div id="selected-templates">
        <ul class="sortable-templates sortable-list selected-templates">
        {if $tmpl}
          {foreach $tmpl as $tid}
          <li class="ui-state-default cf sortable-item" data-cmsms-item-id="{$tid}">
            <span>{$all_templates.$tid}</span>
            <a href="javascript:void(0);" title="{$mod->Lang('remove')}" class="sortable-remove"><img src="{$icon_delete}" alt="{$mod->Lang('remove')}"></a>
            <input class="hidden" type="checkbox" name="{$actionid}assoc_tpl[]" value="{$tid}" checked>
          </li>
          {/foreach}
        {else}
          <li class="placeholder no-sort">{$mod->Lang('drop_items')}</li>
        {/if}
        </ul>
      </div>
    </fieldset>
  </div>
</div>
{else}
<p class="information">{$mod->Lang('warning_edittemplate_notemplates')}</p>
{/if}
