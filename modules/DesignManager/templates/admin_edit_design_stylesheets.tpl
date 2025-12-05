{if !empty($all_stylesheets)}
<script>
$(function() {
  //TODO standard use of JQUI .draggable and .droppable would assist maintainability
  $('ul.sortable-stylesheets').sortable({
    connectWith: '#selected-stylesheets ul',
    delay: 150,
    revert: true,
    placeholder: 'ui-state-highlight',
    items: 'li:not(.placeholder)',
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
      $('.sortable-stylesheets .placeholder').hide();
      $(elements).removeClass('selected ui-state-hover')
             .append($('<a></a>', {
              href:'javascript:void(0)',
              'class':'sortable-remove',
              title:'{$mod->Lang('remove')}',
              html:'<img src="{$icon_delete}" alt="{$mod->Lang('remove')}">'
             }))
             .find('input[type="checkbox"]').attr('checked', true);
    }
  });

  $('#available-stylesheets').on('click', 'li', function() {
    $(this).trigger('focus');
  }).on('keyup', 'li', function(ev) {
    if( ev.keyCode == $.ui.keyCode.ESCAPE ) {
      // escape
      $('#selected-stylesheets li').removeClass('selected');
      ev.preventDefault();
    } else if( ev.keyCode == $.ui.keyCode.SPACE || ev.keyCode == 107 ) {
      // spacebar or plus
      ev.preventDefault();
      $(this).toggleClass('selected ui-state-hover');
      find_sortable_focus(this); // IN TEMPLATES-TAB SCRIPT
    } else if( ev.keyCode == 39 ) {
      // right arrow (ok for rtl?)
      ev.preventDefault();
      $('#available-stylesheets li.selected').each(function() {
        var _t = $(this);
        _t.removeClass('selected ui-state-hover');
        var _css_id = _t.data('cmsms-item-id');
        var _url = '{cms_action_url action=admin_edit_css css=xxxx forjs=1}'.replace('xxxx',_css_id);
        var _text = _t.text().trim();
        var _el = _t.clone();
        var _a = $('<a></a>', {
          href:_url,
          'class':'edit_css unsaved',
//        title:'{$mod->Lang('edit_stylesheet')}',
          text:_text
        });
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
        $('#selected-stylesheets > ul').append(_el);
        _t.remove();
        set_changed(); //see edit_design script
        // set focus somewhere
        find_sortable_focus(this);
      });
    }
  });

  $('#selected-stylesheets').on('click', 'li', function() {
    $('a',this).first().trigger('focus');
  }).on('click', '.sortable-remove', function(ev) { //TODO support DnD back to #available-stylesheets
    ev.preventDefault();
    set_changed();
    var _t = $(this);
    _t.next().attr('checked', false); // next is a hidden checkbox
    _t.parent('li').appendTo('#available-stylesheets ul');
    _t.remove();
  });
/* no in-design css editing
  $(document).on('click', '.edit_css', function(ev) {
    if( __changed ) { // see edit_design script
      ev.preventDefault();
      var _url = $(this).attr('href');
      cms_confirm('{$mod->Lang('confirm_save_design')}').done(function() {
        // save and redirect
        save_design().done(function() {
          window.location.href = _url;
        });
      });
    }
    // normal default link behavior
  });
*/
});
</script>

<p class="information">{$mod->Lang('info_edittemplate_stylesheets_tab')}</p>
<br>
<div class="c_full cf">{$cssl=$design->get_stylesheets()}
  <div class="grid_6 draggable-area">
    <fieldset>
      <legend>{$mod->Lang('available_stylesheets')}</legend>
      <div id="available-stylesheets">
        <ul class="sortable-stylesheets sortable-list available-items available-stylesheets">
        {foreach $all_stylesheets as $sid => $sname}
          {if !$cssl || !in_array($sid,$cssl)}
          <li class="ui-state-default" data-cmsms-item-id="{$sid}" tabindex="0">
            <span>{$sname}</span>
            <input class="hidden" type="checkbox" name="{$actionid}assoc_css[]" value="{$sid}">
          </li>
          {/if}
        {/foreach}
        </ul>
      </div>
    </fieldset>
  </div>
  <div class="grid_6">
    <fieldset>
      <legend>{$mod->Lang('attached_stylesheets')}</legend>
      <div id="selected-stylesheets">
        <ul class="sortable-stylesheets sortable-list selected-stylesheets">
        {if $cssl}
          {foreach $cssl as $sid}
          <li class="ui-state-default cf sortable-item" data-cmsms-item-id="{$sid}">
            <span>{$all_stylesheets.$sid}</span>
            <a href="javascript:void(0);" title="{$mod->Lang('remove')}" class="sortable-remove"><img src="{$icon_delete}" alt="{$mod->Lang('remove')}"></a>
            <input class="hidden" type="checkbox" name="{$actionid}assoc_css[]" value="{$sid}" checked>
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
<p class="information">{$mod->Lang('warning_editdesign_nostylesheets')}</p>
{/if}
