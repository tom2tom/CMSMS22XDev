{* cssmenu_ulshadow navigation *}
{* note, function can only be defined once *}
{* 
  variables:
  node: contains the current node.
  aclass: is used to build a string containing class names given to the a tag if one is used
  liclass: is used to build a string containing class names given to the li tag.
*}

{function name=cssmenu_ulshadow depth=1}
<ul{if $depth ==0} id="primary-nav"{else} class="unli"{/if}>
  {foreach $data as $node}
    {* setup classes for the anchor and list item *}
    {$liclass=''}
    {*{$liclass=' depth'|cat:$depth}*}
    {$aclass=''}

    {* the first child gets a special class 
    {if $node@first && $node@total > 1}{$liclass=$liclass|cat:' first_child'}{/if}
    *}

    {* the last child gets a special class 
    {if $node@last && $node@total > 1}{$liclass=$liclass|cat:' last_child'}{/if}
    *}

    {if $node->current}
      {* this is the current page *}
      {$liclass=$liclass|cat:' menuactive'}
      {$aclass=$aclass|cat:' menuactive'}
    {else if $node->parent}
      {* this is a parent of the current page *}
      {$liclass=$liclass|cat:' parent'}
      {$aclass=$aclass|cat:' parent'}
    {/if}
    {if isset($node->children)}
      {$liclass=$liclass|cat:' menuparent'}
      {$aclass=$aclass|cat:' menuparent'}
    {/if}

    {* build the menu item node *}
    {if $node->type == 'sectionheader'}
      <li class='sectionheader {$liclass}'><span>{$node->menutext}</span>
        {if isset($node->children)}
          {cssmenu_ulshadow data=$node->children depth=$depth+1}
        {/if}
      </li>
    {else if $node->type == 'separator'}
      <li class='separator {$liclass}'><hr class='separator'/></li>
    {else}
      {* regular item *}
      <li class="{$liclass}">
        <a class="{$aclass}" href="{$node->url}"{if $node->target ne ""} target="{$node->target}"{/if}><span>{$node->menutext}</span></a>
        {if isset($node->children)}
          {cssmenu_ulshadow data=$node->children depth=$depth+1}
        {/if}
      </li>
    {/if}
  {/foreach}
  {if $depth > 0}
    <li class="separator once" style="list-style-type: none;">&nbsp;</li>
  {/if}
</ul>
{/function}

{if isset($nodes)}
<div id="menuwrapper">
  {cssmenu_ulshadow data=$nodes depth=0}
  <div class="clearb"></div>
</div>
{/if}
