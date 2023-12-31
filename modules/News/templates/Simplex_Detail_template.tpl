{* this is a sample detail template that works with the Simplex theme *}
{* set a canonical variable that can be used in the head section if process_whole_template is false in the config.php *}
{if isset($entry->canonical)}
  {$canonical=$entry->canonical scope=global}
  {$main_title=$entry->title scope=global}
{/if}

{* <h2>{$entry->title|cms_escape:htmlall}</h2> *}
{if $entry->summary}
    {$entry->summary}
{/if}
    {$entry->content}
{if $entry->extra}
        {$extra_label} {$entry->extra}
{/if}
{if $return_url != ""}
    <br />
        <span class='back'>&#8592; {$return_url}{if $category_name != ''} - {$category_link}{/if}</span>
{/if}

{if isset($entry->fields)}
  {foreach $entry->fields as $field}
     <div>
        {if $field->type == 'file'}
      {* this template assumes that every file uploaded is an image of some sort, because News doesn't distinguish *}
          <img src='{$entry->file_location}/{$field->value}' alt='' />
        {else}
          {$field->name}: {$field->value}
        {/if}
     </div>
  {/foreach}
{/if}
    <footer class='news-meta'>
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
