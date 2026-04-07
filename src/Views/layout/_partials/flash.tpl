{if $flashSuccess || $flashError || $flashWarning}
<script>
document.addEventListener('DOMContentLoaded', function () {
    {if $flashSuccess}
    App.toast({$flashSuccess|json_encode nofilter}, 'success');
    {/if}
    {if $flashError}
    App.toast({$flashError|json_encode nofilter}, 'danger');
    {/if}
    {if $flashWarning}
    App.toast({$flashWarning|json_encode nofilter}, 'warning');
    {/if}
});
</script>
{/if}
