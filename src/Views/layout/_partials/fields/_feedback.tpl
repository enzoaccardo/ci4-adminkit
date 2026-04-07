{* Errore di validazione + testo di aiuto, condiviso dai campi. *}
{if $f.error}<div class="invalid-feedback d-block">{$f.error}</div>{/if}
{if $f.hint}<div class="form-text">{$f.hint}</div>{/if}
