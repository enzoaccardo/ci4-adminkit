{* Campo input: text, email, password, number, url, tel, date, datetime, file, ... *}
<label class="form-label" for="f_{$f.name}">{$f.label}{if $f.required} <span class="text-danger">*</span>{/if}</label>
<input type="{if $f.type == 'datetime'}datetime-local{else}{$f.type}{/if}"
       name="{$f.name}" id="f_{$f.name}"
       class="form-control{if $f.error} is-invalid{/if}"
       value="{$f.value}"{if $f.required} required{/if}{foreach $f.attrs as $k => $v} {$k}="{$v}"{/foreach}>
{include file='layout/_partials/fields/_feedback.tpl' f=$f}
