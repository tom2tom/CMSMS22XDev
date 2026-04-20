<div class="pageoptions endside last endalign" style="margin-{$ndside}:3%">
  <a href="{$back_url}">{admin_icon icon='back.gif'}&nbsp;{$mod->Lang('back')}</a>
</div>

<p class="pageheader">{$mod->Lang('title_missingdeps2')}:</p>
<table class="pagetable">
  <tr>
    <td>{$mod->Lang('nametext')}:</td>
    <td>{$info.name}</td>
  </tr>
  <tr>
    <td>{$mod->Lang('vertext')}:</td>
    <td>{$info.version}</td>
  </tr>
</table>

<table class="pagetable">
  <thead>
    <tr>
      <th>{$mod->Lang('nametext')}</th>
      <th>{$mod->Lang('minversion')}</th>
    </tr>
  </thead>
  <tbody>
  {foreach $info.missing_deps as $name => $version}
    <tr>
      <td>{$name}</td>
      <td>{$version}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
