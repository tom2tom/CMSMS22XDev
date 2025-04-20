{* admin statistics tab *}
{if !empty($topwords)}
{$sformstart}
<div class="pageoverflow">
  <table class="pagetable">
    <thead>
      <tr>
        <th style="width:75%">{$wordtext}</th>
        <th style="width:25%">{$counttext}</th>
      </tr>
    </thead>
    <tbody>
{foreach $topwords as $entry}{cycle values='row1,row2' assign='rowclass'}
      <tr class="{$rowclass}">
        <td>{$entry.word}</td>
        <td>{$entry.count}</td>
      </tr>
{/foreach}
    </tbody>
  </table>
</div>
<br>
<div class="pageoverflow">
  <p class="pageinput">
    {$clearwordcount}&nbsp;{$exportcsv}
  </p>
</div>
{$formend}
{else}
<div class="information">{lang_by_realm('Search','nostatistics')}</div>
{/if}
