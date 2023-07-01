{strip}

{if $count > 0}
    <ul class='cf'>
    {foreach $nodelist as $node}
        {if $node->depth > $node->prevdepth}
            {repeat string='<ul>' times=$node->depth-$node->prevdepth}
        {elseif $node->depth < $node->prevdepth}
            {repeat string='</li></ul>' times=$node->prevdepth-$node->depth}
            </li>
        {elseif $node->index > 0}
            </li>
        {/if}
        {if $node->current}
            <li{if $node->parent || $node->haschildren} class='parent current'{/if}>
                <a href='{$node->url}' class='current'{if $node->target != ''} target='{$node->target}'{/if}>{$node->menutext}</a>
        {elseif $node->parent && ($node->type != 'sectionheader' && $node->type != 'separator')}
            <li class='parent current'>
                <a href='{$node->url}' class='current'{if $node->target != ''} target='{$node->target}'{/if}>{$node->menutext}</a>
        {elseif $node->type == 'sectionheader'}
            <li class='sectionheader'>
                <span class='sectionheader {if $node->parent} parent{/if}{if $node->current} current{/if}'>{$node->menutext}</span>
        {elseif $node->type == 'separator'}
            <li class='separator'>
                <hr class='separator' />
        {else}
            <li{if $node->parent || $node->haschildren} class='parent'{/if}>
                <a href='{$node->url}'{if $node->target != ''} target='{$node->target}'{/if}>{$node->menutext}</a>
        {/if}
    {/foreach}

{repeat string='</li></ul>' times=$node->depth-1}</li>
    </ul>
{/if}

{/strip}
