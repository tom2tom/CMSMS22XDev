<h3>{$mod->Lang('currentpath')} :
{if $path_parts}
   <span class="pathselector">
   {foreach $path_parts as $part}
     {if !empty($part->url)}
       <a href="{$part->url}">{$part->name}</a>
     {else}
       {$part->name}
     {/if}
     {if !$part@last} <span class="ds">/</span>{/if}
   {/foreach}
   </span>
{else}
( {$mod->Lang('top')} )
{/if}
</h3>
