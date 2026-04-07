{* Gruppo di radio button. *}
<label class="form-label d-block">{$f.label}{if $f.required} <span class="text-danger">*</span>{/if}</label>
{foreach $f.options as $o}
    <div class="form-check">
        <input class="form-check-input" type="radio" name="{$f.name}" id="f_{$f.name}_{$o.value}" value="{$o.value}"{if $o.selected} checked{/if}>
        <label class="form-check-label" for="f_{$f.name}_{$o.value}">{$o.label}</label>
    </div>
{/foreach}
{include file='layout/_partials/fields/_feedback.tpl' f=$f}
