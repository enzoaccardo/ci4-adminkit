<?php

namespace AdminKit\Traits;

trait HasSorting
{
    /**
     * Legge i parametri GET `sort` e `dir`, valida che la colonna sia sortable.
     *
     * @param array  $columns     Array $columns del controller (con chiave 'sortable')
     * @param string $defaultKey  Colonna di default se nessun sort attivo
     * @param string $defaultDir  Direzione di default ('asc' o 'desc')
     * @return array              ['key' => '...', 'dir' => 'asc|desc']
     */
    protected function getSort(array $columns, string $defaultKey = '', string $defaultDir = 'asc'): array
    {
        $key = $this->request->getGet('sort') ?? '';
        $dir = strtolower($this->request->getGet('dir') ?? '') === 'desc' ? 'desc' : 'asc';

        if ($key !== '' && isset($columns[$key]) && ! empty($columns[$key]['sortable'])) {
            return ['key' => $key, 'dir' => $dir];
        }

        return ['key' => $defaultKey, 'dir' => $defaultDir];
    }

    /**
     * Applica l'orderBy al builder in base al sort attivo.
     * Usa 'column' dalla definizione colonna se presente (per join con alias).
     *
     * @param object $builder  Model o BaseBuilder di CI4
     * @param array  $columns  Array $columns del controller
     * @param array  $sort     Restituito da getSort()
     */
    protected function applySort(object $builder, array $columns, array $sort): void
    {
        if ($sort['key'] === '') {
            return;
        }

        $col    = $columns[$sort['key']] ?? [];
        $column = $col['column'] ?? $sort['key'];

        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $column)) {
            return;
        }

        $builder->orderBy($column, $sort['dir']);
    }

    /**
     * Assegna al template le variabili $sortField e $sortDir.
     *
     * @param array $sort  Restituito da getSort()
     */
    protected function assignSort(array $sort): void
    {
        $this->assign('sortField', $sort['key']);
        $this->assign('sortDir',   $sort['dir']);
    }
}