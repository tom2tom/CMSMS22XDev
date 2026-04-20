{* this is a sample news-detail template that works with the Simplex theme *}
{* set a canonical variable that can be used in the head section if process_whole_template is false in the config.php *}
{if isset($entry->canonical)}
  {$canonical=$entry->canonical scope=global}
  {$main_title=$entry->title scope=global}
{/if}
{*news_image src='/news/somepath/somefile.png' width=30 example image tag *}
{* <h2>{$entry->title|cms_escape:'htmlall'}</h2> example title *}
{if $entry->summary}
  {$entry->summary}
{/if}
  {$entry->content}
{if $entry->extra}
  {$extra_label} {$entry->extra}
{/if}
{if !empty($entry->fields)}
  {foreach $entry->fields as $field}
   <div>
    {strip}{if $field->type == 'file'}
{* this template assumes that every file-field value is an image of some sort, because News doesn't distinguish *}
     {if !empty($field->value)}
      {$field->name}: <img src="{$entry->file_location}/{$field->value}" alt="{$field->value}">
     {/if}
    {elseif $field->type == 'linkedfile'}
     {if !empty($field->value)}
      <a href="{file_url file=$field->value}" title="{$field->displayvalue}">{$field->name}</a>
     {/if}
    {else}
      {$field->name}: {$field->displayvalue}
    {/if}{/strip}
   </div>
  {/foreach}
{/if}
{if $entry->postdate || $entry->category || $entry->author}
  <footer class="news-meta">
  {if $entry->postdate}
    {$entry->postdate|cms_date_format}
  {/if}
  {if $entry->category}
    <strong>{$category_label}</strong> {$entry->category}
  {/if}
  {if $entry->author}
    <strong>{$author_label}</strong> {$entry->author}
  {/if}
  </footer>
{elseif $return_url}
  <br>
{/if}
{if $return_url}
  <span class="back">&#8592; {$return_url}{if $category_name} - {$category_link}{/if}</span>
{/if}
