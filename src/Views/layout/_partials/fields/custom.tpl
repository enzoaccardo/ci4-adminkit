{* HTML arbitrario passato dal controller (es. widget come il cron-builder). *}
{if $f.label}<label class="form-label">{$f.label}</label>{/if}
{$f.html nofilter}
{if $f.hint}<div class="form-text">{$f.hint}</div>{/if}
