@php
$stepLabels = [
    'optimization-turbomole' => 'Opt. Turbomole',
    'optimization-orca'      => 'Opt. Orca',
    'rdkit'                  => 'RDKit',
    'conformers'             => 'Conformers',
    'cosmo'                  => 'COSMO',
];

function heartbeatBadge(int $seconds): string {
    if ($seconds < 120) {
        return '<span class="inline-block px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">' . $seconds . 's</span>';
    }
    if ($seconds < 600) {
        return '<span class="inline-block px-2 py-0.5 rounded-full text-xs bg-orange-100 text-orange-700">' . $seconds . 's</span>';
    }
    return '<span class="inline-block px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">' . $seconds . 's (stale)</span>';
}

function smilesTrunc(string $smiles, int $len = 40): string {
    return strlen($smiles) > $len ? substr($smiles, 0, $len) . '…' : $smiles;
}
@endphp

{{-- ─────── COSMO calculations ─────── --}}
<div class="space-y-6">
    <div>
        <h4 class="text-sm font-semibold text-gray-700 mb-2">
            COSMO calculations
            <span class="ml-2 text-xs font-normal text-gray-400">({{ count($runningCosmo) }} running)</span>
        </h4>

        @if (count($runningCosmo) === 0)
            <p class="text-sm text-gray-400 italic">No running COSMO calculations.</p>
        @else
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                        <th class="px-3 py-2 border border-gray-200">SMILES</th>
                        <th class="px-3 py-2 border border-gray-200">Membrane / Temp.</th>
                        <th class="px-3 py-2 border border-gray-200">Started</th>
                        <th class="px-3 py-2 border border-gray-200">Heartbeat</th>
                        <th class="px-3 py-2 border border-gray-200">DB prediction</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($runningCosmo as $job)
                        @php
                            $calcId = $job['calculation_id'] ?? null;
                            $pred = $calcId ? ($predsByCalcId[$calcId] ?? null) : null;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-3 py-2 border border-gray-200 font-mono text-xs max-w-xs truncate" title="{{ $job['canonical_smiles'] ?? '' }}">
                                {{ smilesTrunc($job['canonical_smiles'] ?? '—') }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200 text-xs text-gray-600">
                                {{ $job['membrane_key'] ?? '—' }} / {{ $job['temperature_c'] ?? '—' }} °C
                            </td>
                            <td class="px-3 py-2 border border-gray-200 text-xs text-gray-500">
                                {{ isset($job['started_at']) ? \Carbon\Carbon::parse($job['started_at'])->format('d.m. H:i') : '—' }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200">
                                {!! heartbeatBadge((int) ($job['heartbeat_age_seconds'] ?? 0)) !!}
                            </td>
                            <td class="px-3 py-2 border border-gray-200">
                                @if ($pred)
                                    <a href="{{ route('filament.admin.resources.predictions.edit', $pred) }}"
                                       class="text-primary-600 hover:underline font-medium text-xs"
                                       target="_blank">
                                        #{{ $pred->id }} ({{ \Modules\PredictionWorkers\Models\Prediction::enumState($pred->state) }})
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 italic">Not in DB</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ─────── Conformer optimizations ─────── --}}
    <div>
        <h4 class="text-sm font-semibold text-gray-700 mb-2">
            Conformer optimizations
            <span class="ml-2 text-xs font-normal text-gray-400">({{ count($runningConformers) }} running)</span>
        </h4>

        @if (count($runningConformers) === 0)
            <p class="text-sm text-gray-400 italic">No running conformer optimizations.</p>
        @else
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                        <th class="px-3 py-2 border border-gray-200">Step</th>
                        <th class="px-3 py-2 border border-gray-200">SMILES</th>
                        <th class="px-3 py-2 border border-gray-200 w-20">Conf. #</th>
                        <th class="px-3 py-2 border border-gray-200">Started</th>
                        <th class="px-3 py-2 border border-gray-200">Heartbeat</th>
                        <th class="px-3 py-2 border border-gray-200">DB prediction</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($runningConformers as $job)
                        @php
                            $molId = $job['molecule_id'] ?? null;
                            $pred = $molId ? ($predsByMoleculeId[$molId] ?? null) : null;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-3 py-2 border border-gray-200 text-xs font-medium text-gray-700">
                                {{ $stepLabels[$job['step'] ?? ''] ?? ($job['step'] ?? '—') }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200 font-mono text-xs max-w-xs truncate" title="{{ $job['canonical_smiles'] ?? '' }}">
                                {{ smilesTrunc($job['canonical_smiles'] ?? '—') }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200 text-center text-xs text-gray-600">
                                {{ $job['conformer_index'] ?? '—' }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200 text-xs text-gray-500">
                                {{ isset($job['started_at']) ? \Carbon\Carbon::parse($job['started_at'])->format('d.m. H:i') : '—' }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200">
                                {!! heartbeatBadge((int) ($job['heartbeat_age_seconds'] ?? 0)) !!}
                            </td>
                            <td class="px-3 py-2 border border-gray-200">
                                @if ($pred)
                                    <a href="{{ route('filament.admin.resources.predictions.edit', $pred) }}"
                                       class="text-primary-600 hover:underline font-medium text-xs"
                                       target="_blank">
                                        #{{ $pred->id }} ({{ \Modules\PredictionWorkers\Models\Prediction::enumState($pred->state) }})
                                    </a>
                                @elseif ($molId)
                                    <span class="text-xs text-gray-400 italic">Not in DB</span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ─────── Molecule-level steps ─────── --}}
    @if (count($runningMoleculeSteps) > 0)
        <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">
                Molecule-level steps
                <span class="ml-2 text-xs font-normal text-gray-400">({{ count($runningMoleculeSteps) }} running)</span>
            </h4>
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                        <th class="px-3 py-2 border border-gray-200">Step</th>
                        <th class="px-3 py-2 border border-gray-200">SMILES</th>
                        <th class="px-3 py-2 border border-gray-200">Heartbeat</th>
                        <th class="px-3 py-2 border border-gray-200">DB prediction</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($runningMoleculeSteps as $job)
                        @php
                            $molId = $job['molecule_id'] ?? null;
                            $pred = $molId ? ($predsByMoleculeId[$molId] ?? null) : null;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-3 py-2 border border-gray-200 text-xs font-medium">
                                {{ $stepLabels[$job['step'] ?? ''] ?? ($job['step'] ?? '—') }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200 font-mono text-xs truncate" title="{{ $job['canonical_smiles'] ?? '' }}">
                                {{ smilesTrunc($job['canonical_smiles'] ?? '—') }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200">
                                {!! heartbeatBadge((int) ($job['heartbeat_age_seconds'] ?? 0)) !!}
                            </td>
                            <td class="px-3 py-2 border border-gray-200">
                                @if ($pred)
                                    <a href="{{ route('filament.admin.resources.predictions.view', $pred) }}"
                                       class="text-primary-600 hover:underline font-medium text-xs"
                                       target="_blank">
                                        #{{ $pred->id }}
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 italic">Not in DB</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
