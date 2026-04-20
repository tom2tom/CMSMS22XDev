{form_start action='admin_globalsettings_tab'}
  {if !$pretty_urls}
    <div class="warning" style="display:block">{lang('warn_nosefurl')}&nbsp;&nbsp;{cms_help realm='help' key='settings_nosefurl' title=lang('warn_nosefurl')}</div>
  {/if}
  <div class="pageoverflow">
    <p class="pageinput">
      <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}" accesskey="s">
    </p>
  </div>
  {if $pretty_urls}
    <div class="pageoverflow">
      <p class="pagetext"><label for="autocreate_urls">{lang('content_autocreate_urls')}:</label> {cms_help realm='help' key='settings_autocreate_url' title=lang('content_autocreate_urls')}</p>
      <p class="pageinput">
        <select id="autocreate_urls" name="{$actionid}content_autocreate_urls">
          {html_options options=$yesno selected=$content_autocreate_urls}
        </select>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="autocreate_flaturls">{lang('content_autocreate_flaturls')}:</label> {cms_help realm='help' key='settings_autocreate_flaturls' title=lang('content_autocreate_flaturls')}</p>
      <p class="pageinput">
        <select id="autocreate_flaturls" name="{$actionid}content_autocreate_flaturls">
          {html_options options=$yesno selected=$content_autocreate_flaturls}
        </select>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext"><label for="mandatory_urls">{lang('content_mandatory_urls')}:</label> {cms_help realm='help' key='settings_mandatory_urls' title=lang('content_mandatory_urls')}</p>
      <p class="pageinput">
        <select id="mandatory_urls" name="{$actionid}content_mandatory_urls">
          {html_options options=$yesno selected=$content_mandatory_urls}
        </select>
      </p>
    </div>
  {/if}
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}disallowed_contenttypes[]" value="">
    <p class="pagetext"><label for="disallowed_types">{lang('disallowed_contenttypes')}:</label> {cms_help realm='help' key='settings_badtypes' title=lang('disallowed_contenttypes')}</p>
    <p class="pageinput">
      <select id="disallowed_types" name="{$actionid}disallowed_contenttypes[]" multiple size="5">
        {html_options options=$all_contenttypes selected=$disallowed_contenttypes}
      </select>
    </p>
  </div>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}basic_attributes[]" value="">
    <p class="pagetext"><label for="basic_attrs">{lang('basic_attributes')}:</label> {cms_help realm='help' key='settings_basicattribs2' title=lang('basic_attributes')}</p>
    <p class="pageinput">
      <select id="basic_attrs" class="multicolumn" name="{$actionid}basic_attributes[]" multiple size="5">
        {CmsFormUtils::create_option($all_attributes,$basic_attributes)}
      </select>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext"><label for="imagedir">{lang('content_imagefield_path')}:</label> {cms_help realm='help' key='settings_imagefield_path' title=lang('content_imagefield_path')}</p>
    <p class="pageinput">
      <input id="imagedir" type="text" name="{$actionid}content_imagefield_path" size="50" maxlength="255" value="{$content_imagefield_path|cms_escape}">
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext"><label for="thumbdir">{lang('content_thumbnailfield_path')}:</label> {cms_help realm='help' key='settings_thumbfield_path' title=lang('content_thumbnailfield_path')}</p>
    <p class="pageinput">
      <input id="thumbdir" type="text" name="{$actionid}content_thumbnailfield_path" size="50" maxlength="255" value="{$content_thumbnailfield_path|cms_escape}">
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext"><label for="contentimage">{lang('contentimage_path')}:</label> {cms_help realm='help' key='settings_contentimage_path' title=lang('contentimage_path')}</p>
    <p class="pageinput">
      <input type="text" id="contentimage" name="{$actionid}contentimage_path" size="50" maxlength="255" value="{$contentimage_path|cms_escape}">
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext"><label for="cssblockname">{lang('cssnameisblockname')}:</label> {cms_help realm='help' key='settings_cssnameisblockname' title=lang('cssnameisblockname')}</p>
    <p class="pageinput">
      <select id="cssblockname" name="{$actionid}content_cssnameisblockname">
        {cms_yesno selected=$content_cssnameisblockname}
      </select>
    </p>
  </div>
  <br>
  <div class="pageoverflow">
    <p class="pageinput">
      <input type="submit" name="{$actionid}submit" value="{$mod->Lang('submit')}" accesskey="s">
    </p>
  </div>
</form>
