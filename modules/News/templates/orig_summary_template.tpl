<!-- Start News Display Template -->
{* This section shows a clickable list of your News categories. *}
<ul class="list1">
{foreach $cats as $node}
{if $node.depth > $node.prevdepth}
{repeat string="<ul>" times=$node.depth-$node.prevdepth}
{elseif $node.depth < $node.prevdepth}
{repeat string="</li></ul>" times=$node.prevdepth-$node.depth}
</li>
{elseif $node.index > 0}</li>
{/if}
<li{if $node.index == 0} class="firstnewscat"{/if}>
{if $node.count > 0}
  <a href="{$node.url}">{$node.news_category_name}</a>{else}<span>{$node.news_category_name} </span>{/if}
{/foreach}
{repeat string="</li></ul>" times=$node.depth-1}</li>
</ul>

{* this displays the category name if you're browsing by category *}
{if $category_name}
<h3>{$category_name}</h3>
{/if}

{* if you don't want category browsing on your summary page, remove this line and everything above it *}

{if $pagecount > 1}
<p>
{if $pagenumber > 1}
{$firstpage}&nbsp;{$prevpage}&nbsp;
{/if}
{$pagetext}&nbsp;{$pagenumber}&nbsp;{$oftext}&nbsp;{$pagecount}
{if $pagenumber < $pagecount}
&nbsp;{$nextpage}&nbsp;{$lastpage}
{/if}
</p>
{/if}
{*news_image src='/News/someimagefile' width=30*}
{foreach $items as $entry}
{*if !empty($entry->image_url)}article-image handling stuff{/if*}
<div class="NewsSummary">

{if $entry->postdate}
  <div class="NewsSummaryPostdate">
    {$entry->postdate|cms_date_format}
  </div>
{/if}

<div class="NewsSummaryLink">
{* note, for security purposes, because News articles can come from untrused sources, we do not pass the title through Smarty in the default templates *}
<a href="{$entry->moreurl}" title="{$entry->title|cms_escape:'htmlall'}">{$entry->title|cms_escape}</a>
</div>

<div class="NewsSummaryCategory">
  {$category_label} {$entry->category}
</div>

{if $entry->author}
  <div class="NewsSummaryAuthor">
    {$author_label} {$entry->author}
  </div>
{/if}

{if $entry->summary}
{* note, for security purposes, because News articles can come from untrusted sources, we do not pass the summary through Smarty in the default templates *}
  <div class="NewsSummarySummary">
    {$entry->summary}
  </div>

  <div class="NewsSummaryMorelink">
    [{$entry->morelink}]
  </div>

{elseif $entry->content}
{* note, for security purposes, because News articles can come from untrusted sources, we do not pass the content through Smarty in the default templates *}
  <div class="NewsSummaryContent">
    {$entry->content}
  </div>
{/if}

{if !empty($entry->extra)}
  <div class="NewsSummaryExtra">
    {$entry->extra}
  </div>
{/if}
{if !empty($entry->fields)}
  {foreach $entry->fields as $field}
   <div class="NewsSummaryField">
    {strip}{if $field->type == 'file'}
      {if !empty($field->value)}
{* assume the field value is an image to be displayed *}
      {$field->name}:&nbsp;<img src="{$entry->file_location}/{$field->value}" alt="{$field->value}">
      {/if}
    {elseif $field->type == 'linkedfile'}
      {if !empty($field->value)}
      <a href="{file_url file=$field->value}" title="{$field->displayvalue}">{$field->name}</a>
      {/if}
    {else}
      {$field->name}:&nbsp;{$field->displayvalue}
    {/if}{/strip}
   </div>
  {/foreach}
{/if}

</div>
{/foreach}
<!-- End News Display Template -->
