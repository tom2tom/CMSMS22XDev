{* CSS classes used in this template:
#menuwrapper - The id for the <div> that the menu is wrapped in. Sets the width, background etc. for the menu.
#primary-nav - The id for the <ul>
.menuparent - The class for each <li> that has children.
.menuactive - The class for each <li> that is active or is a parent (on any level) of a child that is active. *}

{$number_of_levels=10000}
{if isset($menuparams.number_of_levels)}
  {$number_of_levels=$menuparams.number_of_levels}
{/if}

{if $count > 0}
<div id="menuwrapper">
<ul id="primary-nav">
{foreach $nodelist as $node}
{if $node->depth > $node->prevdepth}
  {repeat string='<ul class="unli">' times=$node->depth-$node->prevdepth}
{elseif $node->depth < $node->prevdepth}
  {repeat string='</li><li class="separator once" style="list-style-type: none;">&nbsp;</li></ul>' times=$node->prevdepth-$node->depth}
  </li>
  {elseif $node->index > 0}</li>
{/if}
{if $node->parent == true or $node->current == true}
  {$classes='menuactive'}
  {if $node->parent == true}
    {$classes='menuactive menuparent'}
  {/if}
  {if $node->children_exist == true and $node->depth < $number_of_levels}
    {$classes=$classes|cat:' parent'}
  {/if}
  {if $node->type == 'sectionheader'}
    <li class="{$classes}"><a class="{$classes}"><span class="sectionheader">{$node->menutext}</span></a>
  {else}
    <li class="{$classes}"><a class="{$classes}"
  {/if}
{elseif $node->type == 'sectionheader' and $node->haschildren == true}
  <li><a ><span class="sectionheader">{$node->menutext}</span></a>
{elseif $node->type == 'sectionheader'}
  <li><a ><span class="sectionheader">{$node->menutext}</span></a>
{elseif $node->type == 'separator'}
  <li style="list-style-type: none;"> <hr class="menu_separator" />
{elseif $node->children_exist == true and $node->depth < $number_of_levels and $node->type != 'sectionheader' and $node->type != 'separator'}
  <li class="menuparent"><a class="menuparent" 
{else}
  <li>
  <a 
{/if}
{if $node->type != 'sectionheader' and $node->type != 'separator'}
{if $node->target}target="{$node->target}" {/if}
href="{$node->url}"><span>{$node->menutext}</span></a>
{/if}
{/foreach}
{repeat string='</li><li class="separator once" style="list-style-type: none;">&nbsp;</li></ul>' times=$node->depth-1}
</li>
</ul>
<div class="clearb"></div>
</div>
{/if}
