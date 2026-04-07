{* json_encode con flag 15 = JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT:
   evita che un messaggio flash contenente </script> o virgolette rompa il
   contesto <script> (difesa in profondità se un flash include input utente). *}
{if $flashSuccess || $flashError || $flashWarning}
<script>
document.addEventListener('DOMContentLoaded', function () {
    {if $flashSuccess}
    App.toast({$flashSuccess|json_encode:15 nofilter}, 'success');
    {/if}
    {if $flashError}
    App.toast({$flashError|json_encode:15 nofilter}, 'danger');
    {/if}
    {if $flashWarning}
    App.toast({$flashWarning|json_encode:15 nofilter}, 'warning');
    {/if}
});
</script>
{/if}
