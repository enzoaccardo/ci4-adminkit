<?php

namespace AdminKit\Traits;

trait HasFilters
{
    /**
     * Legge i valori GET per i campi definiti e restituisce solo quelli non vuoti.
     *
     * @param array $fields  Definizione campi: ['key' => ['type' => 'text', 'label' => '...'], ...]
     * @return array         Valori attivi: ['key' => 'valore', ...]
     */
    protected function getFilters(array $fields): array
    {
        $values = [];

        foreach ($fields as $key => $def) {
            if (in_array($def['type'] ?? 'text', ['date', 'datetime'], true)) {
                $from = $this->request->getGet($key . '_from');
                $to   = $this->request->getGet($key . '_to');
                if ($from !== null && $from !== '') {
                    $values[$key . '_from'] = $from;
                }
                if ($to !== null && $to !== '') {
                    $values[$key . '_to'] = $to;
                }
            } else {
                $value = $this->request->getGet($key);
                if ($value !== null && $value !== '') {
                    $values[$key] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * Applica i filtri attivi al query builder in base al tipo del campo.
     * Supporta sia CI4 Model che BaseBuilder (per query con join).
     *
     * @param object $builder  Model o BaseBuilder di CI4
     * @param array  $fields   Definizione campi
     * @param array  $values   Valori attivi restituiti da getFilters()
     */
    protected function applyFilters(object $builder, array $fields, array $values): void
    {
        foreach ($fields as $key => $def) {
            $column = $def['column'] ?? $key;

            if (in_array($def['type'] ?? 'text', ['date', 'datetime'], true)) {
                $from = $values[$key . '_from'] ?? '';
                $to   = $values[$key . '_to']   ?? '';
                if ($from !== '') {
                    $builder->where("$column >=", $from);
                }
                if ($to !== '') {
                    $builder->where("$column <=", $to);
                }
                continue;
            }

            if (! isset($values[$key]) || $values[$key] === '') {
                continue;
            }

            $value = $values[$key];

            match ($def['type']) {
                'text'     => $builder->like($column, $value),
                'integer',
                'select'   => $builder->where($column, $value),
                default    => $builder->like($column, $value),
            };
        }
    }

    /**
     * Assegna al template le definizioni dei campi (con il valore corrente)
     * e un flag per sapere se ci sono filtri attivi.
     *
     * @param array $fields   Definizione campi
     * @param array $values   Valori attivi restituiti da getFilters()
     */
    protected function assignFilters(array $fields, array $values): void
    {
        foreach ($fields as $key => &$def) {
            $def['name'] = $key;
            if (in_array($def['type'] ?? 'text', ['date', 'datetime'], true)) {
                $def['value_from'] = $values[$key . '_from'] ?? '';
                $def['value_to']   = $values[$key . '_to']   ?? '';
            } else {
                $def['value'] = $values[$key] ?? '';
            }
        }
        unset($def);

        $this->assign('filterFields', $fields);
        $this->assign('hasActiveFilters', ! empty($values));
    }
}