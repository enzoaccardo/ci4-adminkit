{* Campo checkbox / switch. *}
<div class="form-check{if $f.type == 'switch'} form-switch{/if} pt-2">
    <input class="form-check-input" type="checkbox" name="{$f.name}" value="1" id="f_{$f.name}"{if $f.checked} checked{/if}{foreach $f.attrs as $k => $v} {$k}="{$v}"{/foreach}>
    <label class="form-check-label" for="f_{$f.name}">{$f.label}</label>
</div>
{if $f.hint}<div class="form-text">{$f.hint}</div>{/if}
