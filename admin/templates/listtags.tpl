<div class="pagecontainer">
  <div class="pageoverflow">{$header}</div>
  {if isset($subheader)}
    <div class="pageheader">{$subheader}
    {if isset($wiki_url) && isset($image_help_external)}
       <span class="helptext">
         <a class='helpicon' href="{$wiki_url}" target="_blank">{$image_help_external}</a><a href="{$wiki_url}" target="_blank">{lang('help')}</a> ({lang('new_window')})
       </span>
    {/if}
    </div>
  {/if}

  {if isset($content)}
    <br />{$content}
  {elseif isset($error)}
    <div class="pageerrorcontainer"><div class="pageoverflow"><ul class="pageerror"><li>{$error}</li></ul></div></div>
  {elseif !empty($plugins)}
    <table class="pagetable">
      <thead>
       <tr>
         <th><span title="{lang_by_realm('tags','tag_name')}">{lang('name')}</span></th>
         <th><span title="{lang_by_realm('tags','tag_type')}">{lang('type')}</span></th>
         <th class="pagew10"><span title="{lang_by_realm('tags','tag_adminplugin')}">{lang('adminplugin')}</span></th>
         <th class="pagew10"><span title="{lang_by_realm('tags','tag_cachable')}">{lang('cachable')}</span></th>
         <th class="pagew10"><span title="{lang_by_realm('tags','tag_help')}">{lang('help')}</span></th>
         <th class="pagew10"><span title="{lang_by_realm('tags','tag_about')}">{lang('about')}</span></th>
       </tr>
      </thead>
      <tbody>
      {foreach $plugins as $one}
       {cycle values="row1,row2" assign='rowclass'}
       <tr class="{$rowclass}">
         <td>
           {if isset($one.help_url)}
             <a href="{$one.help_url}" title="{lang_by_realm('tags','viewhelp')}">{$one.name}</a>
           {else}
             {$one.name}
           {/if}
         </td>
         <td>
            <span title="{lang_by_realm('tags',$one.type)}">{$one.type}</span>
         </td>
         <td>
            {if isset($one.admin) && $one.admin}
              <span title="{lang_by_realm('tags','title_admin')}">{lang('yes')}</span>
            {else}
              <span title="{lang_by_realm('tags','title_notadmin')}">{lang('no')}</span>
            {/if}
         </td>
         <td>
            {if isset($one.cachable) && $one.cachable == 'yes'}
              <span title="{lang_by_realm('tags','title_cachable')}">{lang('yes')}</span>
            {else}
              <span title="{lang_by_realm('tags','title_notcachable')}">{lang('no')}</span>
            {/if}
         </td>
         <td>
           {if isset($one.help_url)}
             <a href="{$one.help_url}" title="{lang_by_realm('tags','viewhelp')}">{lang('help')}</a>
           {/if}
         </td>
         <td>
           {if isset($one.about_url)}
             <a href="{$one.about_url}" title="{lang_by_realm('tags','viewabout')}">{lang('about')}</a>
           {/if}
         </td>
       </tr>
      {/foreach}
      </tbody>
    </table>
  {/if}
</div>
