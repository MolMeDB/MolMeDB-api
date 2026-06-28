@php
    $record = $getRecord();
    $logs   = array_reverse(is_array($record->logs) ? $record->logs : []);

    $levelStyle = [
        'error'   => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
        'info'    => ['bg' => '#dbeafe', 'text' => '#1e40af'],
    ];
@endphp

@if (empty($logs))
    <p style="font-size:.85rem; color:#9ca3af; font-style:italic;">No logs.</p>
@else
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:.78rem;">
            <thead>
                <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                    <th style="padding:.5rem .75rem; text-align:left; font-weight:600; color:#6b7280; white-space:nowrap; width:130px;">Time</th>
                    <th style="padding:.5rem .75rem; text-align:left; font-weight:600; color:#6b7280; width:60px;">Level</th>
                    <th style="padding:.5rem .75rem; text-align:left; font-weight:600; color:#6b7280; width:140px;">Step</th>
                    <th style="padding:.5rem .75rem; text-align:left; font-weight:600; color:#6b7280; width:120px;">Conformer</th>
                    <th style="padding:.5rem .75rem; text-align:left; font-weight:600; color:#6b7280;">Message</th>
                    <th style="padding:.5rem .75rem; text-align:center; font-weight:600; color:#6b7280; width:70px;">Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $i => $log)
                    @php
                        $level   = $log['level'] ?? 'info';
                        $ls      = $levelStyle[$level] ?? $levelStyle['info'];
                        $hasDetails = !empty($log['details']);
                        $rowBg   = $i % 2 === 0 ? '#fff' : '#f9fafb';
                    @endphp
                    <tr style="border-bottom:1px solid #f3f4f6; background:{{ $rowBg }};">
                        <td style="padding:.45rem .75rem; color:#6b7280; white-space:nowrap; font-variant-numeric:tabular-nums;">
                            {{ isset($log['created_at']) ? \Carbon\Carbon::parse($log['created_at'])->format('d.m. H:i:s') : '—' }}
                        </td>
                        <td style="padding:.45rem .75rem;">
                            <span style="display:inline-block; padding:.15rem .4rem; border-radius:.25rem;
                                         font-size:.7rem; font-weight:600;
                                         background:{{ $ls['bg'] }}; color:{{ $ls['text'] }};">
                                {{ strtoupper($level) }}
                            </span>
                        </td>
                        <td style="padding:.45rem .75rem; color:#374151; font-family:monospace; font-size:.72rem;">
                            {{ $log['step'] ?? '—' }}
                        </td>
                        <td style="padding:.45rem .75rem; color:#6b7280; font-family:monospace; font-size:.68rem; max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                            title="{{ $log['conformer_id'] ?? '' }}">
                            {{ ($log['conformer_id'] ?? null) ? substr((string)$log['conformer_id'], -12) : '—' }}
                        </td>
                        <td style="padding:.45rem .75rem; color:#111827;">
                            {{ $log['message'] ?? '' }}
                        </td>
                        <td style="padding:.45rem .75rem; text-align:center;">
                            @if ($hasDetails)
                                <button
                                    type="button"
                                    x-data
                                    @click="
                                        const w = window.open('', '_blank', 'width=900,height=700,scrollbars=yes');
                                        w.document.write('<html><head><title>Log details<\/title><style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;white-space:pre-wrap;word-break:break-all;font-size:13px}<\/style><\/head><body>' + JSON.stringify({{ json_encode($log['details']) }}, null, 2) + '<\/body><\/html>');
                                        w.document.close();
                                    "
                                    style="padding:.2rem .5rem; border-radius:.25rem; border:1px solid #d1d5db;
                                           background:#fff; color:#374151; cursor:pointer; font-size:.72rem;
                                           transition:background .15s;"
                                    onmouseover="this.style.background='#f3f4f6'"
                                    onmouseout="this.style.background='#fff'"
                                >
                                    Open
                                </button>
                            @else
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
