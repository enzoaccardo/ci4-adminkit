{if isset($filterFields[$fieldKey])}
    {assign var='filterField' value=$filterFields[$fieldKey]}

    {if $filterField.type == 'select'}
        <select name="{$fieldKey}" class="form-select form-select-sm mt-1">
            {foreach $filterField.options as $optVal => $optLabel}
                <option value="{$optVal}" {if $filterField.value == $optVal}selected{/if}>
                    {$optLabel}
                </option>
            {/foreach}
        </select>

    {elseif $filterField.type == 'date'}
        <input type="date" name="{$fieldKey}_from"
               class="form-control form-control-sm mt-1"
               placeholder="Dal"
               value="{$filterField.value_from}">
        <input type="date" name="{$fieldKey}_to"
               class="form-control form-control-sm mt-1"
               placeholder="Al"
               value="{$filterField.value_to}">

    {elseif $filterField.type == 'datetime'}
        <input type="datetime-local" name="{$fieldKey}_from"
               class="form-control form-control-sm mt-1"
               placeholder="Dal"
               value="{$filterField.value_from}">
        <input type="datetime-local" name="{$fieldKey}_to"
               class="form-control form-control-sm mt-1"
               placeholder="Al"
               value="{$filterField.value_to}">

    {elseif $filterField.type == 'integer'}
        <input type="number" name="{$fieldKey}"
               class="form-control form-control-sm mt-1"
               value="{$filterField.value}">

    {else}
        <input type="text" name="{$fieldKey}"
               class="form-control form-control-sm mt-1"
               value="{$filterField.value}">
    {/if}

{/if}
