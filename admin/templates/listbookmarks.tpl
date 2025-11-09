<div class="pagecontainer">
  {$header}
  <div class="pageoptions">
    <a href="addbookmark.php{$urlext}">{$iconadd}</a>
    <a class="pageoptions" href="addbookmark.php{$urlext}">{lang('addbookmark')}</a>
  </div>
{if !empty($pagination)}
  <div class="pageshowrows">
    {$pagination}
  </div>
{/if}
{if $marklist}
  {if $showinfo}<p class="information message">{lang('show_shortcuts_message')}</p>{/if}
  <table class="pagetable">
    <thead>
    <tr>
      <th class="pagew60">{lang('name')}</th>
      <th class="pagew60">{lang('url')}</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    {foreach $marklist as $onemark}{$bid=$onemark->bookmark_id}
      <tr class="{cycle values='row1,row2'}">{$ttl=$onemark->title}
        <td><a href="editbookmark.php{$urlext}&amp;bookmark_id={$bid}">{$ttl}</a></td>
        <td>{$onemark->url}</td>
        <td><a href="editbookmark.php{$urlext}&amp;bookmark_id={$bid}">{$iconedit}</a></td>
        <td><a href="deletebookmark.php{$urlext}&amp;bookmark_id={$bid}" onclick="return confirm('{lang('deleteconfirm', {$ttl})}');">{$icondelete}</a></td>
      </tr>
{/foreach}
    </tbody>
  </table>
{if count($marklist) > 10}
  <br><br>
  <div class="pageoverflow">
    <div class="pageoptions">
      <a href="addbookmark.php{$urlext}">{$iconadd}</a>
      <a class="pageoptions" href="addbookmark.php{$urlext}">{lang('addbookmark')}</a>
    </div>
  </div>
{/if}
{else}
  <p class="information">{lang('no_shortcuts')}</p>
{/if}
</div>{* pagecontainer *}
