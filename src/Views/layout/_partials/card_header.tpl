<div class="card-header d-flex align-items-center">
    <h3 class="card-title me-auto">{$title}</h3>
    {if $createUrl|default:false}
        <a href="{$createUrl}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> {$createLabel}
        </a>
    {/if}
</div>
