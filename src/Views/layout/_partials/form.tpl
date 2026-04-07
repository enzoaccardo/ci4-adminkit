{* Form dichiarativo generato da FormBuilder. Richiede $form (+ $sessionErrors). *}
{include file='layout/_partials/flash.tpl'}
{include file='layout/_partials/errors.tpl' errors=$sessionErrors}

<form action="{$form.action}" method="{$form.method}"{if $form.multipart} enctype="multipart/form-data"{/if}{if $form.ajax} data-ajax="1"{/if}{if $form.logicJson} data-logic='{$form.logicJson nofilter}'{/if}>
    {csrf_field}

    {foreach $form.sections as $section}
        <div class="card mb-4">
            {if $section.title}
                <div class="card-header"><h3 class="card-title mb-0">{$section.title}</h3></div>
            {/if}
            <div class="card-body">
                <div class="row g-3">
                    {foreach $section.fields as $f}
                        {include file='layout/_partials/form_field.tpl' f=$f}
                    {/foreach}
                </div>
            </div>
        </div>
    {/foreach}

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">{$form.submitLabel}</button>
        {if $form.cancelUrl}<a href="{$form.cancelUrl}" class="btn btn-secondary">Annulla</a>{/if}
    </div>
</form>
