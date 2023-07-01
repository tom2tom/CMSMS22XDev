{* wizard step 7 -- files *}
{extends file='wizard_step.tpl'}

{block name='logic'}
    {$subtitle = tr('title_step7')}
    {$current_step = '7'}
{/block}

{block name='contents'}

    <div id="inner" style="overflow: auto; min-height: 10em; max-height: 35em;"></div>
    <div id="bottom_nav">
    {if isset($next_url)}
        <a class="action-button positive" href="{$next_url}" title="{tr('next')}">{tr('next')} &rarr;</a>
    {/if}
    </div>
{/block}