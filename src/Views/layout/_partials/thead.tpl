<thead>
    <tr>
        {foreach $columns as $colKey => $col}
            {assign var='isSorted' value=($sortField|default:'' == $colKey)}
            {assign var='nextDir' value=($isSorted && $sortDir|default:'asc' == 'asc') ? 'desc' : 'asc'}
            <th {if $col.class|default:false}class="{$col.class}"{/if}>
                {if $col.sortable|default:false}
                    <a href="{sort_url sort=$colKey dir=$nextDir}" class="text-decoration-none text-reset d-flex align-items-center gap-1">
                        {$col.label}
                        {if $isSorted}
                            <i class="bi bi-arrow-{if $sortDir|default:'asc' == 'asc'}up{else}down{/if} small"></i>
                        {else}
                            <i class="bi bi-arrow-down-up small text-muted opacity-50"></i>
                        {/if}
                    </a>
                {else}
                    {$col.label}
                {/if}
                {include file='layout/_partials/filter_input.tpl' fieldKey=$colKey}
                {if $colKey == '_actions' && $filterFields|default:false}
                    <div class="d-flex gap-1 mt-1">
                        {if $hasActiveFilters|default:false}
                            <a href="{current_url}" class="btn btn-sm btn-outline-danger" title="Azzera filtri">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        {/if}
                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Filtra">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                {/if}
            </th>
        {/foreach}
    </tr>
</thead>
