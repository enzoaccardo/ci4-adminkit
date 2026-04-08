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

        $rows = '';
        foreach ($log as $r) {
            $rows .= '<tr>'
                . '<td>' . esc($r['template']) . '</td>'
                . '<td><small>' . esc($r['path']) . '</small></td>'
                . '<td style="text-align:right">' . number_format($r['duration'] * 1000, 2) . ' ms</td>'
                . '<td style="text-align:right">' . count($r['vars']) . '</td>'
                . '</tr>';
        }

        return '<p>Le variabili assegnate sono esplorabili nel tab <strong>Vars</strong> (sezioni «Smarty: …»).</p>'
            . '<table><thead><tr>'
            . '<th>Template</th><th>Path risolto</th><th>Tempo</th><th>N. variabili</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>';
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
