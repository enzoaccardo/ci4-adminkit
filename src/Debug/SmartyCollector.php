<?php

namespace AdminKit\Debug;

use CodeIgniter\Debug\Toolbar\Collectors\BaseCollector;

/**
 * Collector della debug toolbar di CodeIgniter per Smarty: mostra i template
 * renderizzati nella richiesta, il path risolto (quale templateDir ha vinto),
 * il tempo di render e le variabili assegnate. Da registrare in
 * app/Config/Toolbar.php ($collectors). Attivo solo con la toolbar (development).
 */
class SmartyCollector extends BaseCollector
{
    protected $hasTimeline   = true;
    protected $hasTabContent = true;
    protected $hasVarData    = true;
    protected $hasLabel      = true;
    protected $title         = 'Smarty';

    /** @return list<array{template:string,path:string,start:float,duration:float,vars:list<string>,data:array}> */
    private function log(): array
    {
        try {
            return service('smarty')->getDebugLog();
        } catch (\Throwable) {
            return [];
        }
    }

    public function getTitleDetails(): string
    {
        $log   = $this->log();
        $total = array_sum(array_column($log, 'duration')) * 1000;

        return count($log) . ' template · ' . number_format($total, 2) . ' ms';
    }

    public function display(): string
    {
        $log = $this->log();

        if ($log === []) {
            return '<p>Nessun template Smarty renderizzato in questa richiesta.</p>';
        }

        $html = '';
        foreach ($log as $r) {
            $html .= '<h4 style="margin:.75rem 0 .25rem">' . esc($r['template'])
                . ' <small style="font-weight:normal;color:#888">'
                . esc($r['path']) . ' · ' . number_format($r['duration'] * 1000, 2) . ' ms · '
                . count($r['vars']) . ' variabili</small></h4>';

            if ($r['data'] === []) {
                $html .= '<p><em>Nessuna variabile assegnata.</em></p>';
                continue;
            }

            $rows = '';
            foreach ($r['data'] as $name => $value) {
                $rows .= '<tr>'
                    . '<td style="vertical-align:top;white-space:nowrap"><strong>' . esc((string) $name) . '</strong></td>'
                    . '<td>' . $this->formatValue($value) . '</td>'
                    . '</tr>';
            }

            $html .= '<table><thead><tr><th>Variabile</th><th>Valore</th></tr></thead><tbody>'
                . $rows . '</tbody></table>';
        }

        return $html;
    }

    /**
     * Formatta un valore per il tab: scalari inline, array/oggetti in un blocco
     * pre troncato (esplorabile senza appesantire la toolbar).
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '<em style="color:#999">null</em>';
        }
        if (is_bool($value)) {
            return '<span style="color:#0a7">' . ($value ? 'true' : 'false') . '</span>';
        }
        if (is_scalar($value)) {
            $s = (string) $value;

            return esc(mb_strlen($s) > 300 ? mb_substr($s, 0, 300) . ' …' : $s);
        }

        $type = is_array($value)
            ? 'array(' . count($value) . ')'
            : 'object(' . (is_object($value) ? $value::class : gettype($value)) . ')';

        $dump = @print_r($value, true);
        if (strlen($dump) > 2000) {
            $dump = substr($dump, 0, 2000) . "\n… (troncato)";
        }

        return '<span style="color:#888">' . esc($type) . '</span>'
            . '<pre style="white-space:pre-wrap;max-height:220px;overflow:auto;margin:.25rem 0;font-size:11px">'
            . esc($dump) . '</pre>';
    }

    /**
     * Espone le variabili assegnate a ciascun template nel tab "Vars" della
     * toolbar (una sezione per render, coppie nome → valore esplorabili).
     */
    public function getVarData(): array
    {
        $out = [];
        foreach ($this->log() as $i => $r) {
            $label = 'Smarty: ' . $r['template'] . ($i > 0 ? ' #' . ($i + 1) : '');
            $out[$label] = $r['data'] ?? [];
        }

        return $out;
    }

    public function getBadgeValue(): int
    {
        return count($this->log());
    }

    public function isEmpty(): bool
    {
        return $this->log() === [];
    }

    public function formatTimelineData(): array
    {
        $data = [];
        foreach ($this->log() as $r) {
            $data[] = [
                'name'      => 'Smarty: ' . $r['template'],
                'component' => 'Smarty',
                'start'     => $r['start'],
                'duration'  => $r['duration'],
            ];
        }

        return $data;
    }

    public function icon(): string
    {
        // Icona "documento" (data URI), stile coerente con gli altri collector.
        return 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">'
            . '<path fill="#9e9e9e" d="M9 1H3a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V6zm4 13H3V2h5v4h5z"/></svg>'
        );
    }
}
