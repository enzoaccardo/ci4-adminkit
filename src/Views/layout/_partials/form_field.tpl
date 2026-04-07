{* Dispatcher di campo: instrada al partial della famiglia ($f.tpl, costruito
   dal FormBuilder). I campi hidden non hanno wrapper di colonna. *}
{if $f.partial == 'hidden'}
    {include file=$f.tpl f=$f}
{else}
    <div class="col-md-{$f.col}" data-field="{$f.name}">
        {include file=$f.tpl f=$f}
    </div>
{/if}
