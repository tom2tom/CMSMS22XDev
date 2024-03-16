/**
 * @fileOverview plugin code for cmsms_dirtyform plugin.
 * @version 0.1
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 */

/**
 * @name cmsms.dirtyForm
 * @namespace cmsms
 *
 * @example
 * $.cmsms_lock();
 */
(function($) {
  $.widget('cmsms.lockManager', {
    options: {
      touch_handler: null,
      lostlock_handler: null,
      error_handler: null,
      change_noticed: 0,
      lock_timeout: 60,
      lock_refresh: 60
    },

    _settings: {
      locked: 0,
      lock_id: -1,
      lock_expires: -1
    },

    _error_handler: function(error) {
      if (typeof error === 'string') {
        var key = 'error_lock_' + error;
        var msg = 'Unknown Error';
        if (typeof this._settings.lang[key] !== 'undefined') msg = this._settings.lang[key];
        error = {
          type: error,
          msg: msg
        };
      }
      if (typeof this.options.error_handler === 'function') {
        this.options.error_handler(error);
      } else {
        console.debug('Error: ' + error.type + ' - ' + error.msg);
      }
    },

    _lostlock_handler: function(error) {
      if (typeof this.options.lostlock_handler === 'function') {
        this.options.lostlock_handler(error);
      }
      console.debug('Error: ' + error.type + ' - ' + error.msg);
    },

    _create: function() {
      // do initial error checking (user key)
      this.options.lock_refresh = Math.max(this.options.lock_refresh, 30);
      this.options.lock_refresh = Math.min(this.options.lock_refresh, 3600);
      if (typeof cms_data.secure_param_name !== 'undefined') this.options.secure_param = cms_data.secure_param_name;
      if (!this.options.secure_param) throw 'The secure_param option (string) must be set in the cmsms_lock plugin';
      if (typeof cms_data.user_key !== 'undefined') this.options.user_key = cms_data.user_key;
      if (!this.options.user_key) throw 'The user_key option (string) must be set in the cmsms_lock plugin';
      if (typeof cms_data.admin_url !== 'undefined') this.options.admin_url = cms_data.admin_url;
      if (!this.options.admin_url) throw 'The admin_url option (string) must be set in the cmsms_lock plugin';
      if (!this.options.type) throw 'The type option (string) must be set in a cmsms_lock plugin';
      // don't care about about values of: this.options.oid, this.options.uid
      // the latter will be [re-]populated in the backend if appropriate

      // do initial ajax connection to fill settings
      var self = this;
      var ajax_url = this.options.admin_url + '/ajax_lock.php?showtemplate=false';
      var opts = {
        opt: 'setup',
        oid: this.options.oid,
        uid: this.options.uid
      };
      opts[this.options.secure_param] = this.options.user_key;
      $.post(ajax_url, opts, function(data, textStatus) {
        if (textStatus !== 'success') throw 'Problem communicating with ajax url ' + ajax_url;
        if (data.status === 'error') {
          self._error_handler(data.error);
        }

        if (typeof data.uid === 'undefined' || self.options.uid != data.uid) {
          // for the first time, we can use the onError callback
          self._error_handler('useridmismatch');
          return;
        }

        delete data.status;
        self._settings = $.extend(self._settings, data);
        self._settings.ajax_url = ajax_url;
        self._settings.oid = self.options.oid;

        self.options.change_noticed = 0;
        if (self.options.lock_timeout) {
          // setup our event handlers
          self._setup_handlers();
          // do our initial lock.
          self._lock();
        }
      });
    },

    _setup_touch: function() {
      var interval = this.options.lock_refresh;
      interval = Math.min(3600, Math.max(5, interval));
      if (typeof this._settings.touch_timer !== 'undefined') clearTimeout(this._settings.touch_timer);
      var self = this;
      self._settings.touch_timer = setTimeout(function() {
        self._touch();
      }, interval * 1000);
    },

    _setup_handlers: function() {
      var self = this;
      this._settings.touch_skipped = 0;
      this.element.on('change', 'input:not([type=submit]), select, textarea', function() {
        self.options.change_noticed = 1;
        if (self._settings.touch_skipped) {
          self._touch();
        }
      });
      if (this.options.lock_refresh > 0) this._setup_touch();
    },

    _touch: function() {
      if (this.options.change_noticed && this._settings.locked && this._settings.lock_id > 0) {
        // do ajax touch
        console.debug('lockmanager: touching lock');
        this._settings.touch_skipped = 0;
        var opts = {
          opt: 'touch',
          type: this.options.type,
          oid: this._settings.oid,
          uid: this._settings.uid,
          lock_id: this._settings.lock_id
        };
        opts[this.options.secure_param] = this.options.user_key;
        var self = this;
        $.post(self._settings.ajax_url, opts, function(data, textStatus) {
          if (textStatus !== 'success') throw 'Problem communicating with ajax url ' + self._settings.ajax_url;
          if (data.status === 'error') {
            if (data.error.type === 'cmsnolockexception') {
              self._lostlock_handler(data.error);
            } else {
              self._error_handler(data.error);
            }
            // assume we are no longer locked...
            self._settings.locked = 0;
            self._settings.lock_id = -1;
            self._settings.lock_expires = -1;
            return;
          }
          if (self.options.touch_handler) self.options.touch_handler();
          self._settings.lock_expires = data.lock_expires;
          self.options.change_noticed = 0;
        });
      } else {
        this._settings.touch_skipped = 1;
      }
      this._setup_touch();
    },

    _lock: function() {
      if (!this._settings.locked) {
        // do ajax lock
        var opts = {
          opt: 'lock',
          type: this.options.type,
          oid: this.options.oid,
          uid: this.options.uid,
          lifetime: this.options.lock_timeout
        };
        opts[this.options.secure_param] = this.options.user_key;
        var self = this;
        $.post(this._settings.ajax_url, opts, function(data, textStatus) {
          if (textStatus != 'success') throw 'Problem communicating with ajax url ' + self._settings.ajax_url;
          if (data.status == 'error') {
            // todo: here handle the type of error.
            self._error_handler(data.error);
            return;
          }
          if (self.options.lock_handler) self.options.lock_handler();
          self._settings.lock_id = data.lock_id;
          self._settings.lock_expires = data.lock_expires;
          self._settings.locked = 1;
        });
      }
    },

    relock: function() {
      this._lock();
    },

    unlock: function() {
      if (this._settings.locked && this._settings.lock_id > 0 &&
          this._settings.oid > 0 && this._settings.uid > 0 ) { // oid or uid < 1 would match nothing
        // do ajax unlock
        var opts = {
          opt: 'unlock',
          type: this.options.type,
          oid: this._settings.oid,
          uid: this._settings.uid,
          lock_id: this._settings.lock_id
        };
        opts[this.options.secure_param] = this.options.user_key;
        var self = this;
        var jqxhr = $.ajax(this._settings.ajax_url, {
          method: 'POST',
          cache: false,
          data: opts,
          dataType: 'json'
        }).done(function(data, textStatus) {
          if (self.options.unlock_handler) self.options.unlock_handler();
          self._settings.locked = 0;
          self._settings.lock_id = -1;
          self._settings.lock_expires = -1;
        }).fail(function(xhr, textStatus, error) {
          console.debug('unlock failed: ' + textStatus + ' // ' + error + ' // ' + xhr.status);
          setTimeout(function() {
            // nothing here
          }, 2000);
        });
        return jqxhr;
      } else {
        var data;
        if (this._settings.oid > 0 && this._settings.uid > 0) {
            data = {
                status: 'success' // nothing to unlock
            };
        } else {
            data = {
                status: 'error',
                error: 'No possible lock-match'
            };
        }
        return $.Deferred().resolve(data, '');
      }
    },

    // mark the end of the functions
    _noop: function() {}
  });
})(jQuery);
