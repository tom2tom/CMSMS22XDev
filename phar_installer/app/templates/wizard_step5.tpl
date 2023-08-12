{* wizard step 5 *}

{extends file='wizard_step.tpl'}

{block name='logic'}
    {$subtitle = tr('title_step5')}
    {$current_step = '5'}
{/block}

{block name='contents'}

<div class="installer-form">
{wizard_form_start}
    <p>{tr('info_adminaccount')}</p>

    <fieldset>
        <div class="row form-row">
            <div class="four-col">
                <label for="userin">{tr('username')}</label>
            </div>
            <div class="eight-col">
                <input id="userin" class="form-field required full-width" type="text" name="username" required>
                <div class="corner red">
                    <i class="icon-asterisk"></i>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="four-col">
                <label for="emailin">{tr('emailaddr')}</label>
            </div>
            <div class="eight-col">
            {if 1}
                <input id="emailin" class="form-field full-width" type="email" name="emailaddr">
            {else}
                <input id="emailin" class="form-field required full-width" type="email" name="emailaddr" required>
                <div class="corner red">
                    <i class="icon-asterisk"></i>
                </div>
            {/if}
            </div>
        </div>
        <div class="row form-row">
            <div class="four-col">
                <label for="passin">{tr('password')}</label>
            </div>
            <div class="eight-col">
                <input id="passin" class="form-field required full-width" type="password" name="password" required autocomplete="off">
                <div class="corner red">
                    <i class="icon-asterisk"></i>
                </div>
            </div>
        </div>
        <div class="row form-row">
            <div class="four-col">
                <label for="passinagn">{tr('repeatpw')}</label>
            </div>
            <div class="eight-col">
                <input id="passinagn" class="form-field required full-width" type="password" name="repeatpw" required ="required" autocomplete="off">
                <div class="corner red">
                    <i class="icon-asterisk"></i>
                </div>
            </div>
        </div>
        {if $verbose}
        <div class="row form-row">
            <div class="four-col">
                <label for="saltsel">{tr('saltpasswords')}</label>
            </div>
            <div class="eight-col">
                <select id="saltsel" name="saltpw" class="form-field">
                    {html_options options=$yesno selected=$account.saltpw}
                </select>
            </div>
        </div>
        {/if}
        <div class="row form-row">
            <div class="four-col">
                <label for="emailacctinfo">{tr('emailaccountinfo')}</label>
            </div>
            <div class="eight-col">
                <select id="emailacctinfo" name="emailaccountinfo" class="form-field">
                    {html_options options=$yesno selected=$account.emailaccountinfo}
                </select>
            </div>
        </div>
        <div class="message yellow">{tr('warn_email')}</div>
    </fieldset>
    <div id="bottom_nav">
        <input class="action-button positive" type="submit" name="next" value="{tr('next')} &rarr;">
    </div>

{wizard_form_end}
</div>

{/block}
