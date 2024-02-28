/**
 * @name cmsms.hierselector
 * @namespace cmsms.hierselector
 *
 * @example
 * $('#myinput').cmsms_hierselector();
 */
(function($) {

  $.widget('cmsms.hierselector', {
    // these default values match the corresponding default values of
    // arguments (if they exist) of ContentOperations::CreateHierarchyDropdown()
    options: {
      current: 0, // numeric id of the current content item being processed
      value: 0, // numeric id of the now-selected content item (or -1 ?)
      allowcurrent: false, // whether the user is allowed to select the current item (or a child of that item)
      use_perms: false, // whether to use pages' editability (by the current user) to control their and their children's selectability
//    use_simple: false, // whether to use a simple dropdown (true involves 'here_up' backend operation, false involves 'userpages' op)
      allow_all: false, // whether to show all content items i.e. include inactive and not-worth-selecting ones
      for_child: false, // whether the selector is involved in adding a child page, and if so, to comply with items' WantsChildren property
      is_manager: true // whether the user is permitted to modify any page
    },

    /**
     * @ignore
     */
    _create: function() {
      if (typeof cms_data.secure_param_name !== 'undefined') this.options.secure_param = cms_data.secure_param_name;
      if (!this.options.secure_param) throw 'The secure_param option (string) must be set in the cmsms hierselector plugin';
      if (typeof cms_data.user_key !== 'undefined') this.options.user_key = cms_data.user_key;
      if (!this.options.user_key) throw 'The user_key option (string) must be set in the cmsms hierselector plugin';
      if (typeof cms_data.admin_url !== 'undefined') this.options.admin_url = cms_data.admin_url;
      if (!this.options.admin_url) throw 'The admin_url option (string) must be set in the cmsms hierselector plugin';

      // initialization
      var self = this;
      this.data = {};
      this.data.orig_val = this.element.val(); // page numeric id
      this.data.name = this.element.attr('name'); // html name attribute
      this.data.id = this.element.attr('id'); // html id attribute
      this.element.hide().val('').removeAttr('name').attr('readonly', 'readonly');
      this.data.hidden_e = $('<input type="hidden" name="' + this.data.name + '" value="' + this.data.orig_val + '">').insertAfter(this.element);
      this.data.ajax_url = this.options.admin_url + '/ajax_content.php?' + this.options.secure_param + '=' + this.options.user_key;
      $.ajax(this.data.ajax_url, {
        cache: false,
        dataType: 'json',
        data: {
          op: 'pageinfo',
          page: this.data.orig_val
        }
      }).done(function(data) {
        if (data.status === 'success') {
          self.data.orig_idhier = data.data.id_hierarchy;
          self.data.orig_pages = data.data.id_hierarchy.split('.');
        } else {
          console.log(data.message);
          self.data.hidden_e.val(-1).trigger('change');
        }
      }).fail(function(xhr, textStatus, err) {
        self.data.hidden_e.val(-1).trigger('change');
      }).always(function() {
//      self._setup_dropdowns();
        self._setup_smart_dropdowns();
      });
    },

    _setOption: function(k, v) {
      this.options[k] = v;
//    this._setup_dropdowns();
      this._setup_smart_dropdowns();
    },

/*  _setup_dropdowns: function() {
      if (this.options.use_simple) {
        this._setup_simple_dropdown();
      } else {
        this._setup_smart_dropdowns();
      }
    },

    _build_simple_select: function(name, data, cur_val, orig_page) {
      var sel = $('<select></select>',{id:name,'class':'cms_selhier',title:cms_lang('hierselect_title')});
      sel.on('change', function() {
        var v = $(this).val();
        $(this).trigger('cmsms_formchange', {
          elem: $(this),
          value: v
        });
      });
      for (var idx = 0; idx < data.length; idx++) {
        var depth = data[idx].hierarchy.split('.').length;
        var str = '&nbsp;&nbsp;'.repeat(depth - 1) + data[idx].display;
        var opt = $('<option></option>').val(data[idx].content_id).text(str);
        if (data[idx].content_id == this.options.current) {
          opt.addClass('current');
          if (!this.options.allowcurrent) {
            opt.attr('disabled', 'disabled');
          }
        }
        if (data[idx].content_id == cur_val) opt.attr('selected', 'selected').addClass('selected');
        if (data[idx].content_id == orig_page) opt.addClass('hilite');
        if (this.options.use_perms && !data[idx].can_edit) opt.attr('disabled', 'disabled').addClass('nochildren');
//      if (this.options.for_child && !data[idx].has_children && !data[idx].wants_children) opt.attr('disabled', 'disabled').addClass('nochildren');
        sel.append(opt);
      }
      return sel;
    },
*/
    // selected_id is the relevant id for the level now being populated, not necessarily this.options.value
    _build_smart_select: function(name, data, selected_id, has_optnone, orig_page) {
      var sel = $('<select></select>',{id:name,'class':'cms_selhier',title:cms_lang('hierselect_title')}); // no name-attribute
      if (has_optnone) {
        $('<option></option>').val(-1).text(cms_lang('none')).prependTo(sel);
      } else if (selected_id == -1) { // more selectors than current-page hierarchy-levels - should never happen
        selected_id = data[0].content_id; //use root-selector data to populate it ?
        this.data.hidden_e.val(selected_id).trigger('change');
      }

      for (var idx = 0; idx < data.length; idx++) {
        var opt = $('<option></option>').val(data[idx].content_id).text(data[idx].display);
        if (data[idx].content_id == this.options.current) {
          opt.addClass('current');
          if (!this.options.allowcurrent) {
            opt.attr('disabled', 'disabled');
          }
        }
        if (data[idx].content_id == selected_id) {
          opt.attr('selected', 'selected').addClass('selected');
        }
        if (data[idx].content_id == orig_page) {
          opt.addClass('hilite');
        }
        if (this.options.use_perms && !data[idx].can_edit) {
          opt.attr('disabled', 'disabled'); //TODO .addClass('nochildren') ?
        }
        if (!data[idx].has_children) {
          opt.addClass('nochildren');
          if (this.options.for_child && !data[idx].wants_children) {
            opt.attr('disabled', 'disabled');
          }
        }
        sel.append(opt);
      }

      var self = this;
      sel.on('change', function() {
        var v = $(this).val();
        if (v < 1) {
          v = $(this).prev('select').val();
          if (typeof v === 'undefined') v = -1;
        }
        self.data.hidden_e.val(v).trigger('change'); //why the event ?
        self._setup_smart_dropdowns();
        $(this).trigger('cmsms_formchange', {
          elem: $(this),
          value: v
        });
      });
      return sel;
    },

    _setup_smart_dropdowns: function() {
      var self = this;
      var cur_val = this.data.hidden_e.val();
      this.element.prevAll('select.cms_selhier').remove();
      this.element.val('');
      var opts = this.options;
      opts.op = 'here_up';
      opts.page = cur_val;
      $.ajax(this.data.ajax_url, {
        cache: false,
        dataType: 'json',
        data: opts
      }).done(function(data) {
        data = data.data;
        var found_cur = '';
        for (var idx = 0; idx < data.length; idx++) {
          for (var x2 = 0; x2 < data[idx].length; x2++) {
            if (data[idx][x2].content_id == cur_val) {
              found_cur = data[idx][x2].id_hierarchy;
              idx = data.length; // instead of a named-loop break
              break;
            }
          }
        }
        var cur_pages = [];
        if (found_cur) {
          cur_pages = found_cur.split('.');
        }
        var has_optnone = true; // whether to add a -1/None option to the
                                // selector, which if selected would cause
                                // the value of the previous/parent-selector
                                // (if any) to become the actual selection
                                // this initial value applies to the root level
        for (idx = 0; idx < data.length; idx++) {
          if (!data[idx]) break;
          var selected_id = (idx < cur_pages.length) ? cur_pages[idx] : -1; // -1 for nothing selected ? appended dummy ?
          var orig_page = (self.data.orig_pages && idx < self.data.orig_pages.length) ? self.data.orig_pages[idx] : -100;
          var sel = self._build_smart_select(self.data.id + '_' + idx, data[idx], selected_id, has_optnone, orig_page);
          sel.insertBefore(self.element); // why does js work for jQuery objects?
          // setup for next sel, if any
          if (selected_id) { // > 0 ?
            for (var x2 = 0; x2 < data[idx].length; x2++) { // does nothing for selected_id == -1 unless the following can match
              if (data[idx][x2].content_id == selected_id) { // -1 never matched unless cloned page is being processed?
                if (has_optnone) {
                  if (!(self.options.for_child || data[idx][x2].has_usable_link)) { //has_usable_link aka sometimes-worth-choosing is false for system types ErrorPage, Separator etc TODO allow_all setting should prevail?
                    has_optnone = false; // next sel (if any) will not include 'none' so the present one cannot become the selection
                    break;
                  } else if (self.options.for_child && self.options.use_perms && !(self.options.is_manager || data[idx][x2].can_edit)) {
                    // we are using permissions and the user may not edit this item TODO editability never relevant to selectability (not picking editable pages)
                    has_optnone = false; // next sel (if any) will not include 'none'
                    break;
                  }
                }
                has_optnone = data[idx][x2].wants_children; // next sel (if any) will include 'none' if that page may have children TODO insufficient test
                break;
              }
            }
          } else {
            var here = 1;
          }
        }
      }).fail(function(xhr,textStatus,err) {
        console.debug('AJAX error: ' + err);
      });
    },

/*  _setup_simple_dropdown: function() {
      var self = this;
      var opts = this.options;
      opts.op = 'userpages';
      $.ajax(this.data.ajax_url, {
        cache: false,
        dataType: 'json',
        data: opts
      }).done(function(data) {
        var cur_val = self.data.hidden_e.val();
        var orig_page = -100; //TODO what to highlight in the selector-options
        var sel = self._build_simple_select(self.data.id + '_0', data, cur_val, orig_page);
        sel.insertBefore(self.element);
      }).fail(function(xhr,textStatus,err) {
        console.debug('AJAX error: ' + err);
      });
    },
*/
    _noop: function() {}
  });
})(jQuery);
