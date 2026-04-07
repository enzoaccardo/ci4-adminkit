{if $errors|default:false}
    <div class="alert alert-danger">
        <ul class="mb-0">
            {foreach $errors as $error}
                <li>{$error}</li>
            {/foreach}
        </ul>
    </div>
{/if}
