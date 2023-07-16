{* wizard step 8 -- database work *}
{extends file='wizard_step.tpl'}

{block name='logic'}
    {$subtitle = tr('title_step8')}
    {$current_step = '8'}
{/block}

{block name='contents'}

    <div id="inner" style="overflow: auto; min-height: 10em; max-height: 35em;"></div>
    <div id="bottom_nav">
    {if !empty($next_url)}
        <a class="action-button positive" href="{$next_url}" title="{tr('next')}">{tr('next')} &rarr;</a>
    {/if}
    </div>

{/block}
