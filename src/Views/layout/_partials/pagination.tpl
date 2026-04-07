{if $pagination->totalPages > 1 || $pagination->total > 0}
<div class="d-flex align-items-center justify-content-between mt-3">

    <small class="text-muted">
        {if $pagination->total > 0}
            Risultati {$pagination->from}–{$pagination->to} di {$pagination->total}
        {else}
            Nessun risultato
        {/if}
    </small>

    {if $pagination->totalPages > 1}
    <nav>
        <ul class="pagination pagination-sm mb-0">

            <li class="page-item {if !$pagination->hasPrev}disabled{/if}">
                <a class="page-link" href="{paginate_url page=$pagination->page-1}">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>

            {if $pagination->rangeStart > 1}
                <li class="page-item">
                    <a class="page-link" href="{paginate_url page=1}">1</a>
                </li>
                {if $pagination->rangeStart > 2}
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                {/if}
            {/if}

            {for $p=$pagination->rangeStart to $pagination->rangeEnd}
                <li class="page-item {if $p == $pagination->page}active{/if}">
                    <a class="page-link" href="{paginate_url page=$p}">{$p}</a>
                </li>
            {/for}

            {if $pagination->rangeEnd < $pagination->totalPages}
                {if $pagination->rangeEnd < $pagination->totalPages - 1}
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                {/if}
                <li class="page-item">
                    <a class="page-link" href="{paginate_url page=$pagination->totalPages}">{$pagination->totalPages}</a>
                </li>
            {/if}

            <li class="page-item {if !$pagination->hasNext}disabled{/if}">
                <a class="page-link" href="{paginate_url page=$pagination->page+1}">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>

        </ul>
    </nav>
    {/if}

</div>
{/if}
