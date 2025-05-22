{*css related to site generally N/A inside TMCE iframe without mucho hacking*}
{tab_header name='example' label=$mod->Lang('example') active=$tab}
{tab_header name='settings' label=$mod->Lang('settings') active=$tab}
{tab_start name='example' active=$tab}
 {cms_textarea forcemodule='MicroTiny' name='mt_example' id='mt_example' enablewysiwyg=1 rows=10 columns=80 value=
"<p><img src=\"{$imgbase_url}/demo.png\" style=\"float:{$ndside}\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris et ipsum id ante dignissim cursus sollicitudin eget erat. Quisque sit amet arcu urna. Nulla ultricies lacinia sapien, sed aliquam quam feugiat in. Donec consectetur pretium congue. Integer aliquam facilisis lacus, ut facilisis erat pharetra eget. Duis dapibus posuere nunc, id gravida massa pellentesque ac. Duis massa lectus, tempor sed imperdiet aliquam, luctus ut risus. Integer nisl libero, porttitor sit amet sagittis at, sodales at urna. Maecenas facilisis arcu eget nulla imperdiet sed interdum massa pretium. In id eros orci, pharetra dignissim nisl. Quisque vitae luctus turpis. Aenean pulvinar accumsan justo, vel pulvinar mi consequat in. Vestibulum ac turpis vel massa venenatis volutpat placerat in diam. Quisque ac magna dolor. Aliquam sagittis interdum urna a euismod.</p>"}
 <span style="clear:both"></span>
{tab_start name='settings'}
  {if !empty($profiles)}
  <fieldset>
    <legend>{$mod->Lang('prompt_profiles')}</legend>
    <table class="pagetable">
      <thead>
        <tr>
          <th>{$mod->Lang('prompt_name')}</th>
          <th class="pageicon"></th>{* edit *}
        </tr>
      </thead>
      <tbody>
      {foreach $profiles as $profile}
       {cms_action_url action='admin_editprofile' profile=$profile.name profile=$profile.name assign='edit_url'}
        <tr>
          <td><a href="{$edit_url}" title="{$mod->Lang('title_edit_profile')}">{$profile.label}</a></td>
          <td><a href="{$edit_url}">{admin_icon icon='edit.gif' alt=$mod->Lang('title_edit_profile')}</a></td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  </fieldset>
  {else}
  <p class="information">{$mod->Lang('none')}</p>
  {/if}
{tab_end}
