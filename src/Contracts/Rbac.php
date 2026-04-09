<?php

namespace AdminKit\Contracts;

/**
 * Contratto RBAC opzionale. Il kit NON implementa un RBAC: se un pacchetto (o
 * l'app) registra un service('rbac') che implementa questa interfaccia, il
 * BaseAdminController vi delega authorize()/can() automaticamente (soft-discovery).
 * Se nessun RBAC è presente, il kit resta fail-closed sulle azioni con permesso.
 */
interface Rbac
{
    /** Vero se l'utente corrente bypassa i controlli (superadmin). */
    public function isSuperAdmin(): bool;

    /** Vero se l'utente corrente ha il permesso indicato. */
    public function can(string $permission): bool;

    /** Nega l'accesso (es. eccezione 403) se l'utente non ha il permesso. */
    public function authorize(string $permission): void;
}
