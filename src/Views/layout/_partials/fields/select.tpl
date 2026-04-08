{* Campo select / multiselect. Se il campo ha un widget (es. tomselect),
   l'init JS è aggiunto da HasForm e aggancia questo id (f_{name}).
   Se il campo ha 'createNew', accanto compare un bottone che apre in modale il
   form di un'altra entità e ne inietta il record creato (vedi modal-form.js). *}
<label class="form-label" for="f_{$f.name}">{$f.label}{if $f.required} <span class="text-danger">*</span>{/if}</label>
{if $f.createNew}<div class="input-group">{/if}
<select name="{$f.name}{if $f.type == 'multiselect'}[]{/if}" id="f_{$f.name}" data-value="{$f.value}"
        class="form-select{if $f.error} is-invalid{/if}"{if $f.type == 'multiselect'} multiple{/if}{if $f.required} required{/if}{foreach $f.attrs as $k => $v} {$k}="{$v}"{/foreach}>
    {if $f.empty !== null}<option value="">{$f.empty}</option>{/if}
    {foreach $f.options as $o}
        <option value="{$o.value}"{if $o.selected} selected{/if}>{$o.label}</option>
    {/foreach}
</select>
{if $f.createNew}
    <button type="button" class="btn btn-outline-secondary" data-create-new
            data-url="{$f.createNew.url}" data-target="f_{$f.name}" data-title="{$f.createNew.title}">
        <i class="bi bi-plus-lg"></i> {$f.createNew.label}
    </button>
</div>
{/if}
{include file='layout/_partials/fields/_feedback.tpl' f=$f}
