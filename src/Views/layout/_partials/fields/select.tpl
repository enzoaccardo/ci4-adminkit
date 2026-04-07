{* Campo select / multiselect. Se il campo ha un widget (es. tomselect),
   l'init JS è aggiunto da HasForm e aggancia questo id (f_{name}). *}
<label class="form-label" for="f_{$f.name}">{$f.label}{if $f.required} <span class="text-danger">*</span>{/if}</label>
<select name="{$f.name}{if $f.type == 'multiselect'}[]{/if}" id="f_{$f.name}" data-value="{$f.value}"
        class="form-select{if $f.error} is-invalid{/if}"{if $f.type == 'multiselect'} multiple{/if}{if $f.required} required{/if}{foreach $f.attrs as $k => $v} {$k}="{$v}"{/foreach}>
    {if $f.empty !== null}<option value="">{$f.empty}</option>{/if}
    {foreach $f.options as $o}
        <option value="{$o.value}"{if $o.selected} selected{/if}>{$o.label}</option>
    {/foreach}
</select>
{include file='layout/_partials/fields/_feedback.tpl' f=$f}
