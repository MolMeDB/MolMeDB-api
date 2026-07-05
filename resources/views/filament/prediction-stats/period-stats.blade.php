@php
$periods = ['day' => 'Today', 'week' => 'This week', 'month' => 'This month', 'year' => 'This year', 'total' => 'Total'];
$stepLabels = [
    'rdkit'                   => 'RDKit',
    'conformers'              => 'Conformers',
    'optimization-orca'       => 'Opt. Orca',
    'optimization-turbomole'  => 'Opt. Turbomole',
    'cosmo'                   => 'COSMO',
];

function fmtDuration(?float $sec): string {
    if ($sec === null) return '—';
    if ($sec < 60) return round($sec) . 's';
    if ($sec < 3600) return round($sec / 60, 1) . 'm';
    return round($sec / 3600, 1) . 'h';
}
@endphp

<div class="space-y-6">
    @foreach ($periods as $key => $label)
        @php $pdata = $payload['periods'][$key]['steps'] ?? []; @endphp
        <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ $label }}</h4>
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                        <th class="px-3 py-2 border border-gray-200 w-40">Step</th>
                        <th class="px-3 py-2 border border-gray-200 w-20 text-right">Total</th>
                        <th class="px-3 py-2 border border-gray-200">Completed</th>
                        <th class="px-3 py-2 border border-gray-200 w-20 text-right">Running</th>
                        <th class="px-3 py-2 border border-gray-200 w-20 text-right">Failed</th>
                        <th class="px-3 py-2 border border-gray-200 w-28 text-right">Avg duration</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stepLabels as $stepKey => $stepLabel)
                        @php
                            $s = $pdata[$stepKey] ?? null;
                            $started = $s['started'] ?? 0;
                            $completed = $s['completed'] ?? 0;
                            $failed = $s['failed'] ?? 0;
                            $running = $s['running'] ?? max(0, $started - $completed - $failed);
                            $total = $s['total'] ?? ($completed + $failed + $running);
                            $avg = $s['avg_duration_seconds'] ?? null;
                            $pct = $total > 0 ? round($completed / $total * 100) : 0;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-3 py-2 border border-gray-200 font-medium text-gray-700">{{ $stepLabel }}</td>
                            <td class="px-3 py-2 border border-gray-200 text-right text-gray-600">{{ $total }}</td>
                            <td class="px-3 py-2 border border-gray-200">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-4 rounded-full bg-gray-100 overflow-hidden">
                                        @if ($total > 0)
                                            <div
                                                class="h-full rounded-full {{ $failed > 0 ? 'bg-orange-400' : 'bg-green-500' }}"
                                                style="width: {{ $pct }}%"
                                            ></div>
                                        @endif
                                    </div>
                                    <span class="text-xs text-gray-500 whitespace-nowrap w-20 text-right">
                                        {{ $completed }} / {{ $total }}
                                        @if ($total > 0)
                                            ({{ $pct }}%)
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 border border-gray-200 text-right {{ $running > 0 ? 'text-orange-600 font-semibold' : 'text-gray-400' }}">
                                {{ $running > 0 ? $running : '—' }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200 text-right {{ $failed > 0 ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                                {{ $failed > 0 ? $failed : '—' }}
                            </td>
                            <td class="px-3 py-2 border border-gray-200 text-right text-gray-500">{{ fmtDuration($avg) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
