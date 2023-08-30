{* wizard step 6 *}
{extends file='wizard_step.tpl'}

{block name='logic'}
    {$subtitle = tr('title_step6')}
    {$current_step = '6'}
{/block}

{block name='contents'}

<div class="installer-form">
{wizard_form_start}
    {if $action == 'install'}
        <h3>{tr('prompt_sitename')}</h3>
        <p>{tr('info_sitename')}</p>

        <div class="row form-row">
            <div class="twelve-col">
                <input class="form-field required full-width" type="text" name="sitename" value="{$siteinfo.sitename}" placeholder="{tr('ph_sitename')}" required>
                <div class="corner red">
                    <i class="icon-asterisk"></i>
                </div>
            </div>
        </div>
    {/if}

{if !empty($language_list)}
    <h3>{tr('prompt_addlanguages')}</h3>
    <p>{tr('info_addlanguages')}</p>

    <div class="row form-row">
        <select class="form-field" name="languages[]" multiple size="8">
            {html_options options=$language_list selected=$siteinfo.languages}
        </select>
    </div>
{/if}

    <div id="bottom_nav">
    <input class="action-button positive" type="submit" name="next" value="{tr('next')} &rarr;">
    </div>

{wizard_form_end}
</div>

{/block}
