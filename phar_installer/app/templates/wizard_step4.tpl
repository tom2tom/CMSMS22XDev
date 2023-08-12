{* wizard step 4 *}

{extends file='wizard_step.tpl'}
{block name='logic'}
    {$subtitle = tr('title_step4')}
    {$current_step = '4'}
{/block}

{block name='contents'}
<div class="installer-form">
{wizard_form_start}
{if $action != 'freshen'}
    <h3>{tr('prompt_dbinfo')}</h3>
    <p>{tr('info_dbinfo')}</p>

    <fieldset>
        {if $verbose}
        <div class="row form-row">
            <div class="four-col">
                <label for="dbtypesel">{tr('prompt_dbtype')}</label>
            </div>
            <div class="eight-col">
                <select id="dbtypesel" class="form-field" name="dbtype">
                    {html_options options=$dbtypes selected=$config.dbtype}
                </select>
            </div>
        </div>
        {/if}
        <div class="row form-row">
            <div class="four-col">
                <label for="hostin">{tr('prompt_dbhost')}</label>
            </div>
            <div class="eight-col">
                <input id="hostin" class="form-field required full-width" type="text" name="dbhost" value="{$config.dbhost}" required>
                <div class="corner red">
                    <i class="icon-asterisk"></i>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="four-col">
                <label for="dbnamein">{tr('prompt_dbname')}</label>
            </div>
            <div class="eight-col">
                <input id="dbnamein" class="form-field required full-width" type="text" name="dbname" value="{$config.dbname}" required>
                <div class="corner red">
                    <i class="icon-asterisk"></i>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="four-col">
                <label for="dbuserin">{tr('prompt_dbuser')}</label>
            </div>
            <div class="eight-col">
                <input id="dbuserin" class="form-field required full-width" type="text" name="dbuser" value="{$config.dbuser}" autocomplete="off" required>
                <div class="corner red">
                    <i class="icon-asterisk"></i>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="four-col">
                <label for="dbpassin">{tr('prompt_dbpass')}</label>
            </div>
            <div class="eight-col">
                <input id="dbpassin" class="form-field required full-width" type="password" name="dbpass" value="" autocomplete="off" required>
                <div class="corner red">
                    <i class="icon-asterisk"></i>
                </div>
            </div>
        </div>
        {if $verbose}
        <div class="row form-row">
            <div class="four-col">
                <label for="dbportin">{tr('prompt_dbport')}</label>
            </div>
            <div class="eight-col">
                <input id="dbportin" class="form-field full-width" type="text" name="dbport" value="{$config.dbport}">
            </div>
        </div>
        <div class="row form-row">
            <div class="four-col">
                <label for="tblprefin">{tr('prompt_dbprefix')}</label>
            </div>
            <div class="eight-col">
                <input id="tblprefin" class="form-field full-width" type="text" name="dbprefix" value="{$config.dbprefix}">
            </div>
        </div>
        {/if}
    </fieldset>
{/if}
    <h3>{tr('prompt_timezone')}</h3>
    <p>{tr('info_timezone')}</p>

    <div class="row form-row">
        <label for="tzonesel" class="visuallyhidden">{tr('prompt_timezone')}</label>
        <select id="tzonesel" class="form-field" name="timezone">
            {html_options options=$timezones selected=$config.timezone}
        </select>
    </div>

    {if $verbose}
    <h3>{tr('prompt_queryvar')}</h3>
    <p class="info">{tr('info_queryvar')}</p>

    <div class="row form-row">
        <div class="four-col">
            <label for="queryin">{tr('prompt_queryvar')}</label>
        </div>
        <div class="eight-col">
            <input id="queryin" class="form-field" type="text" name="query_var" value="{$config.query_var}">
        </div>
    </div>
    {/if}

    {if $verbose and $action == 'install'}
    <h3>{tr('prompt_installcontent')}</h3>
    <p>{tr('info_installcontent')}</p>

    <div class="row form-row">
        <label for="contentsel">{tr('prompt_installcontent')}</label>
        <select id="contentsel" class="form-field" name="samplecontent">
            {html_options options=$yesno selected=$config.samplecontent}
        </select>
    </div>
    {/if}

    <div id="bottom_nav">
    <input class="action-button positive" type="submit" name="next" value="{tr('next')} &rarr;">
    </div>

{wizard_form_end}
</div>
{/block}