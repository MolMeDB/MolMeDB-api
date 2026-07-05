@php
$stepLabels = [
    'optimization-orca'      => 'Opt. Orca',
    'optimization-turbomole' => 'Opt. Turbomole',
    'rdkit'                  => 'RDKit',
    'conformers'             => 'Conformers',
    'cosmo'                  => 'COSMO',
];

if (! function_exists('heartbeatBadge')) {
    function heartbeatBadge(int $seconds): string {
        if ($seconds < 120) {
            return '<span style="display:inline-block; padding:.1rem .55rem; border-radius:9999px; font-size:.7rem; white-space:nowrap; background:#dcfce7; color:#15803d;">' . $seconds . 's</span>';
        }
        if ($seconds < 600) {
            return '<span style="display:inline-block; padding:.1rem .55rem; border-radius:9999px; font-size:.7rem; white-space:nowrap; background:#ffedd5; color:#c2410c;">' . $seconds . 's</span>';
        }
        return '<span style="display:inline-block; padding:.1rem .55rem; border-radius:9999px; font-size:.7rem; white-space:nowrap; background:#fee2e2; color:#b91c1c;">' . $seconds . 's (stale)</span>';
    }
}

if (! function_exists('predictionLink')) {
    function predictionLink(?\Modules\PredictionWorkers\Models\Prediction $pred): string {
        if (! $pred) {
            return '<span style="font-size:.7rem; color:#9ca3af; font-style:italic;">Not in DB</span>';
        }

        $url = route('filament.admin.resources.predictions.edit', $pred);
        $state = $pred->effectiveStateLabel();

        return '<a href="' . e($url) . '" target="_blank" rel="noopener" style="display:inline-block; padding:.15rem .55rem; border-radius:.35rem; font-size:.7rem; font-weight:600; white-space:nowrap; background:#eef2ff; color:#4338ca; text-decoration:none;">#' . $pred->id . ' (' . e($state) . ')</a>';
    }
}
@endphp

<div style="display:flex; flex-direction:column; gap:1.75rem;">
    {{-- ─────── COSMO calculations ─────── --}}
    <div>
        <div style="font-size:.8rem; font-weight:700; color:#374151; margin-bottom:.6rem;">
            COSMO calculations
            <span style="margin-left:.4rem; font-size:.75rem; font-weight:400; color:#9ca3af;">({{ count($runningCosmo) }} running)</span>
        </div>

        @if (count($runningCosmo) === 0)
            <p style="font-size:.8rem; color:#9ca3af; font-style:italic;">No running COSMO calculations.</p>
        @else
            <div style="overflow-x:auto; border:1px solid #f3f4f6; border-radius:.5rem;">
                <table style="width:100%; border-collapse:collapse; font-size:.8rem;">
                    <thead>
                        <tr style="background:#f9fafb; text-align:left; font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.03em;">
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">SMILES</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">Membrane / Temp.</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">Started</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">Heartbeat</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">DB prediction</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runningCosmo as $job)
                            @php
                                $calcId = $job['calculation_id'] ?? null;
                                $pred = $calcId ? ($predsByCalcId[$calcId] ?? null) : null;
                            @endphp
                            <tr style="border-bottom:1px solid #f9fafb;">
                                <td style="padding:.5rem .75rem; font-family:ui-monospace,monospace; font-size:.7rem; color:#374151; word-break:break-all;">
                                    {{ $job['canonical_smiles'] ?? '—' }}
                                </td>
                                <td style="padding:.5rem .75rem; color:#4b5563; white-space:nowrap;">
                                    {{ $job['membrane_key'] ?? '—' }} / {{ $job['temperature_c'] ?? '—' }} °C
                                </td>
                                <td style="padding:.5rem .75rem; color:#6b7280; white-space:nowrap;">
                                    {{ isset($job['started_at']) ? \Carbon\Carbon::parse($job['started_at'])->format('d.m. H:i') : '—' }}
                                </td>
                                <td style="padding:.5rem .75rem;">
                                    {!! heartbeatBadge((int) ($job['heartbeat_age_seconds'] ?? 0)) !!}
                                </td>
                                <td style="padding:.5rem .75rem;">
                                    {!! predictionLink($pred) !!}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ─────── Conformer optimizations ─────── --}}
    <div>
        <div style="font-size:.8rem; font-weight:700; color:#374151; margin-bottom:.6rem;">
            Conformer optimizations
            <span style="margin-left:.4rem; font-size:.75rem; font-weight:400; color:#9ca3af;">({{ count($runningConformers) }} running)</span>
        </div>

        @if (count($runningConformers) === 0)
            <p style="font-size:.8rem; color:#9ca3af; font-style:italic;">No running conformer optimizations.</p>
        @else
            <div style="overflow-x:auto; border:1px solid #f3f4f6; border-radius:.5rem;">
                <table style="width:100%; border-collapse:collapse; font-size:.8rem;">
                    <thead>
                        <tr style="background:#f9fafb; text-align:left; font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.03em;">
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">Step</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">SMILES</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6; width:5rem;">Conf. #</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">Started</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">Heartbeat</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">DB prediction</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runningConformers as $job)
                            @php
                                $molId = $job['molecule_id'] ?? null;
                                $pred = $molId ? ($predsByMoleculeId[$molId] ?? null) : null;
                            @endphp
                            <tr style="border-bottom:1px solid #f9fafb;">
                                <td style="padding:.5rem .75rem; font-weight:600; color:#374151; white-space:nowrap;">
                                    {{ $stepLabels[$job['step'] ?? ''] ?? ($job['step'] ?? '—') }}
                                </td>
                                <td style="padding:.5rem .75rem; font-family:ui-monospace,monospace; font-size:.7rem; color:#374151; word-break:break-all;">
                                    {{ $job['canonical_smiles'] ?? '—' }}
                                </td>
                                <td style="padding:.5rem .75rem; text-align:center; color:#4b5563;">
                                    {{ $job['conformer_index'] ?? '—' }}
                                </td>
                                <td style="padding:.5rem .75rem; color:#6b7280; white-space:nowrap;">
                                    {{ isset($job['started_at']) ? \Carbon\Carbon::parse($job['started_at'])->format('d.m. H:i') : '—' }}
                                </td>
                                <td style="padding:.5rem .75rem;">
                                    {!! heartbeatBadge((int) ($job['heartbeat_age_seconds'] ?? 0)) !!}
                                </td>
                                <td style="padding:.5rem .75rem;">
                                    @if ($pred)
                                        {!! predictionLink($pred) !!}
                                    @elseif ($molId)
                                        <span style="font-size:.7rem; color:#9ca3af; font-style:italic;">Not in DB</span>
                                    @else
                                        <span style="font-size:.7rem; color:#d1d5db;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ─────── Molecule-level steps ─────── --}}
    @if (count($runningMoleculeSteps) > 0)
        <div>
            <div style="font-size:.8rem; font-weight:700; color:#374151; margin-bottom:.6rem;">
                Molecule-level steps
                <span style="margin-left:.4rem; font-size:.75rem; font-weight:400; color:#9ca3af;">({{ count($runningMoleculeSteps) }} running)</span>
            </div>
            <div style="overflow-x:auto; border:1px solid #f3f4f6; border-radius:.5rem;">
                <table style="width:100%; border-collapse:collapse; font-size:.8rem;">
                    <thead>
                        <tr style="background:#f9fafb; text-align:left; font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.03em;">
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">Step</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">SMILES</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">Heartbeat</th>
                            <th style="padding:.5rem .75rem; border-bottom:1px solid #f3f4f6;">DB prediction</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runningMoleculeSteps as $job)
                            @php
                                $molId = $job['molecule_id'] ?? null;
                                $pred = $molId ? ($predsByMoleculeId[$molId] ?? null) : null;
                            @endphp
                            <tr style="border-bottom:1px solid #f9fafb;">
                                <td style="padding:.5rem .75rem; font-weight:600; color:#374151; white-space:nowrap;">
                                    {{ $stepLabels[$job['step'] ?? ''] ?? ($job['step'] ?? '—') }}
                                </td>
                                <td style="padding:.5rem .75rem; font-family:ui-monospace,monospace; font-size:.7rem; color:#374151; word-break:break-all;">
                                    {{ $job['canonical_smiles'] ?? '—' }}
                                </td>
                                <td style="padding:.5rem .75rem;">
                                    {!! heartbeatBadge((int) ($job['heartbeat_age_seconds'] ?? 0)) !!}
                                </td>
                                <td style="padding:.5rem .75rem;">
                                    @if ($pred)
                                        {!! predictionLink($pred) !!}
                                    @else
                                        <span style="font-size:.7rem; color:#9ca3af; font-style:italic;">Not in DB</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
