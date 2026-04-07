{* Campo textarea. *}
<label class="form-label" for="f_{$f.name}">{$f.label}{if $f.required} <span class="text-danger">*</span>{/if}</label>
<textarea name="{$f.name}" id="f_{$f.name}" class="form-control{if $f.error} is-invalid{/if}"
          rows="3"{if $f.required} required{/if}{foreach $f.attrs as $k => $v} {$k}="{$v}"{/foreach}>{$f.value}</textarea>
{include file='layout/_partials/fields/_feedback.tpl' f=$f}
