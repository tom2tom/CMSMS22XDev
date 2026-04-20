{* set a canonical variable that can be used in the head section if process_whole_template is false in the config.php *}
{if isset($entry->canonical)}
  {* note this syntax ensures that the canonical variable is set into global scope *}
  {$canonical=$entry->canonical scope=global}
{/if}
{*news_image src='some image url' width=30 etc*}
{*if !empty($entry->image_url)}article-image handling stuff{/if*}
{if $entry->postdate}
  <div id="NewsPostDetailDate">
    {$entry->postdate|cms_date_format}
  </div>
{/if}
<h3 id="NewsPostDetailTitle">{$entry->title|cms_escape:'htmlall'}</h3>

<hr id="NewsPostDetailHorizRule">

{if $entry->summary}
{* note, for security purposes we do not pass the summary through Smarty before display. This is because articles can come from untrusted sources. *}
  <div id="NewsPostDetailSummary">
    <strong>
      {$entry->summary}
    </strong>
  </div>
{/if}

{if $entry->category}
  <div id="NewsPostDetailCategory">
    {$category_label} {$entry->category}
  </div>
{/if}
{if $entry->author}
  <div id="NewsPostDetailAuthor">
    {$author_label} {$entry->author}
  </div>
{/if}

<div id="NewsPostDetailContent">
{* note, for security purposes we do not pass the content through Smarty before display. This is because articles can come from untrusted sources. *}
  {$entry->content}
</div>

{if $entry->extra}
  <div id="NewsPostDetailExtra">
    {$extra_label} {$entry->extra}
  </div>
{/if}
{if !empty($entry->fields)}
  {foreach $entry->fields as $field}
   <div class="NewsDetailField">
    {strip}{if $field->type == 'file'}
{* this template assumes that every file-field value is an image of some sort, because News doesn't distinguish *}
      {if !empty($field->value)}
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
{if $return_url}
  <br>
  <div id="NewsPostDetailReturnLink">
    {$return_url}{if $category_name} - {$category_link}{/if}
  </div>
{/if}
