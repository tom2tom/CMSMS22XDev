<script type="text/javascript">
$(function() {
  $('#clearlog').on('click', function(ev) {
    ev.preventDefault();
    var _hr = $(this).attr('href');
    cms_confirm("{$sysmain_confirmclearlog|escape:'javascript'}").done(function() {
      window.location.href = _hr;
    });
  });
  $('#pagesel').on('change', function() {
    $(this).closest('form').trigger('submit');
  });
  $('#toggle_filters').on('click', function() {
    $('#adminlog_filters').dialog({
      modal: true,
      width: 'auto'
    });
  });
});
</script>

<div class="pagecontainer">
  <div class="pageoverflow">
    <div id="adminlog_filters" style="display: none;" title="{lang('filter')}">
        <form id="adminlog_filter" method="post" action="adminlog.php?{$SECURE_PARAM_NAME}={$CMS_USER_KEY}">
          <div class="c_full">
            <label>{$langfilteraction}</label>
            <input type="text" name="filteraction" value="{$filter->action}" class="grid_10" />
            <div class="clearb"></div>
          </div>
          <div class="c_full">
            <label>{lang('item_name_contains')}</label>
            <input type="text" name="filteritem" value="{$filter->item_name}" class="grid_10" />
            <div class="clearb"></div>
          </div>
          <div class="c_full">
            <label>{$langfilteruser}:</label>
            <input type="text" name="filteruser" value="{$filter->user}" class="grid_10" />
            <div class="clearb"></div>
          </div>
          <div class="pageoverflow">
            <p class="pageinput">
             <input type="submit" name="filterapply" value="{lang('apply')}"/>
             <input type="submit" name="filterreset" value="{lang('reset')}"/>
            </p>
          </div>
        </form>
    </div>

    <div class="c_full">
      <div class="grid_8" style="padding-top: 8px;">
        <a id="toggle_filters">{admin_icon icon='view.gif' alt=""} {lang('filter')}</a>
        {if $filter_applied}<span style="color: green;"><em>({lang('applied')})</em></span>{/if}
        {if isset($downloadlink)}
          <a href="adminlog.php{$urlext}&amp;download=1">{$downloadlink}</a>&nbsp;
          <a href="adminlog.php{$urlext}&amp;download=1">{$langdownload}</a>
        {/if}
        {if $clearicon != ''}
          &nbsp;
          <a href="adminlog.php{$urlext}&amp;clear=true">{$clearicon}</a>
          <a id="clearlog" href="adminlog.php{$urlext}&amp;clear=true">{$langclear}</a>
        {/if}
      </div>
      {if !empty($pagelist)}
      <div class="grid_4" style="text-align: right;">
        <form method="post" action="adminlog.php?{$SECURE_PARAM_NAME}={$CMS_USER_KEY}">
          {lang('page')}:&nbsp;
          <select id="pagesel" name="page">{html_options options=$pagelist selected=$page}</select>
        </form>
      </div>
      {/if}
      <div class="clear"></div>
  </div>

  {if !empty($loglines)}
    <table class="pagetable">
      <thead>
      <tr>
        <th>{lang('ip_addr')}</th>
        <th>{$languser}</th>
        <th>{$langitemid}</th>
        <th>{$langitemname}</th>
        <th>{$langaction}</th>
        <th>{$langdate}</th>
      </tr>
      </thead>
      <tbody>
        {foreach $loglines as $line}
          {cycle values='row1,row2' assign='currow'}
        <tr class="{$currow}">
          <td>{$line.ip_addr|default:''}</td>
          <td>{$line.username}</td>
          <td>{$line.itemid}</td>
          <td>{$line.itemname}</td>
          <td>{$line.action}</td>
          <td>{$line.date|localedate_format:'j %h Y H:i:s'}</td>
        </tr>
        {/foreach}

      </tbody>
    </table>
  {else}
    <h3 style="text-align: center; color: red;">{$langlogempty}</h3>
  {/if}

  </div>
</div>
