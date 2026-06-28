@php
    $record = $getRecord();
    $totals = $record->payload['totals'] ?? [];

    $categories = [
        'molecules'          => 'Molecules',
        'conformers'         => 'Conformers',
        'cosmo_calculations' => 'COSMO calculations',
    ];

    $segmentDefs = [
        'completed'          => ['label' => 'Completed',         'color' => '#22c55e'],
        'running'            => ['label' => 'Running',            'color' => '#f97316'],
        'queued'             => ['label' => 'Queued',             'color' => '#9ca3af'],
        'waiting_for_script' => ['label' => 'Waiting for script', 'color' => '#a78bfa'],
        'failed'             => ['label' => 'Failed',             'color' => '#ef4444'],
    ];

    function buildConicGradient(array $data, array $defs): string {
        $total = array_sum(array_map(fn($k) => $data[$k] ?? 0, array_keys($defs)));
        if ($total === 0) return '#e5e7eb 0% 100%';
        $stops = [];
        $cumulative = 0;
        foreach ($defs as $key => $def) {
            $value = $data[$key] ?? 0;
            if ($value <= 0) continue;
            $pct = $value / $total * 100;
            $from = round($cumulative, 3);
            $to   = round($cumulative + $pct, 3);
            $stops[] = "{$def['color']} {$from}% {$to}%";
            $cumulative += $pct;
        }
        if ($cumulative < 100) {
            $stops[] = '#e5e7eb ' . round($cumulative, 3) . '% 100%';
        }
        return implode(', ', $stops);
    }
@endphp

<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1.5rem;">
    @foreach ($categories as $key => $label)
        @php
            $data  = $totals[$key] ?? [];
            $total = $data['total'] ?? array_sum(array_map(fn($k) => $data[$k] ?? 0, array_keys($segmentDefs)));
            $conic = buildConicGradient($data, $segmentDefs);
        @endphp
        <div style="display:flex; flex-direction:column; align-items:center; gap:1rem;
                    padding:1.25rem; border-radius:0.75rem; border:1px solid #f3f4f6; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.06);">

            <div style="font-size:.8rem; font-weight:600; color:#4b5563; letter-spacing:.02em;">
                {{ strtoupper($label) }}
            </div>

            {{-- Donut via conic-gradient --}}
            <div style="position:relative; width:130px; height:130px;">
                <div style="
                    width:130px; height:130px; border-radius:50%;
                    background: conic-gradient({{ $conic }});
                "></div>
                {{-- Cut-out hole --}}
                <div style="
                    position:absolute; top:50%; left:50%;
                    transform:translate(-50%,-50%);
                    width:82px; height:82px; border-radius:50%;
                    background:#fff;
                    display:flex; flex-direction:column; align-items:center; justify-content:center;
                ">
                    <span style="font-size:1.6rem; font-weight:700; color:#111827; line-height:1;">
                        {{ $total }}
                    </span>
                    <span style="font-size:.7rem; color:#9ca3af; margin-top:2px;">total</span>
                </div>
            </div>

            {{-- Legend --}}
            <div style="width:100%; display:flex; flex-direction:column; gap:.35rem;">
                @foreach ($segmentDefs as $sKey => $def)
                    @php $val = $data[$sKey] ?? 0; @endphp
                    @if ($val > 0 || in_array($sKey, ['completed', 'running', 'queued']))
                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:.75rem;">
                            <div style="display:flex; align-items:center; gap:.4rem;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%;
                                             background:{{ $val > 0 ? $def['color'] : '#e5e7eb' }};
                                             flex-shrink:0;"></span>
                                <span style="color:#6b7280;">{{ $def['label'] }}</span>
                            </div>
                            <span style="font-weight:600; color:{{ $val > 0 ? $def['color'] : '#d1d5db' }};">
                                {{ $val }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>
