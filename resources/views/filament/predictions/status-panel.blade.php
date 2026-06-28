@php
    $record    = $getRecord();
    $isRunning = $record->state === \Modules\PredictionWorkers\Models\Prediction::STATE_RUNNING;

    $stateColor = match ((int) $record->state) {
        \Modules\PredictionWorkers\Models\Prediction::STATE_FINISHED => ['bg' => '#dcfce7', 'text' => '#166534', 'dot' => '#22c55e'],
        \Modules\PredictionWorkers\Models\Prediction::STATE_RUNNING  => ['bg' => '#fef9c3', 'text' => '#854d0e', 'dot' => '#eab308'],
        \Modules\PredictionWorkers\Models\Prediction::STATE_ERROR    => ['bg' => '#fee2e2', 'text' => '#991b1b', 'dot' => '#ef4444'],
        \Modules\PredictionWorkers\Models\Prediction::STATE_REMOVE   => ['bg' => '#fee2e2', 'text' => '#991b1b', 'dot' => '#ef4444'],
        \Modules\PredictionWorkers\Models\Prediction::STATE_STOPPED  => ['bg' => '#f3f4f6', 'text' => '#4b5563', 'dot' => '#9ca3af'],
        default                                                        => ['bg' => '#eff6ff', 'text' => '#1e40af', 'dot' => '#3b82f6'],
    };

    // Remote step → human label & color (primary indicator when running)
    $remoteStepLabels = [
        'rdkit'                  => 'RDKit (SDF)',
        'conformers'             => 'Conformer generation',
        'optimization-orca'      => 'Opt. Orca',
        'optimization-turbomole' => 'Opt. Turbomole',
        'cosmo'                  => 'COSMO prediction',
    ];
    $remoteStepColors = [
        'cosmo'                  => ['bg' => '#f3e8ff', 'text' => '#6b21a8'],
        'optimization-turbomole' => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'optimization-orca'      => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'conformers'             => ['bg' => '#dbeafe', 'text' => '#1e40af'],
        'rdkit'                  => ['bg' => '#dbeafe', 'text' => '#1e40af'],
    ];

    $remoteStep      = $record->remote_current_step;
    $remoteStepLabel = $remoteStep ? ($remoteStepLabels[$remoteStep] ?? $remoteStep) : null;
    $remoteStepColor = $remoteStep ? ($remoteStepColors[$remoteStep] ?? ['bg' => '#f3f4f6', 'text' => '#374151']) : null;

    // Local workflow step (secondary — can lag behind remote)
    $localStepColor = match ((int) $record->step) {
        \Modules\PredictionWorkers\Models\Prediction::STEP_RESULT_DB_STORE,
        \Modules\PredictionWorkers\Models\Prediction::STEP_RESULT_PARSE,
        \Modules\PredictionWorkers\Models\Prediction::STEP_RESULT_DOWNLOAD => ['bg' => '#dcfce7', 'text' => '#166534'],
        \Modules\PredictionWorkers\Models\Prediction::STEP_COSMO           => ['bg' => '#f3e8ff', 'text' => '#6b21a8'],
        \Modules\PredictionWorkers\Models\Prediction::STEP_OPTIMIZATION    => ['bg' => '#fef3c7', 'text' => '#92400e'],
        \Modules\PredictionWorkers\Models\Prediction::STEP_SDF_READY       => ['bg' => '#dbeafe', 'text' => '#1e40af'],
        default                                                             => ['bg' => '#f3f4f6', 'text' => '#374151'],
    };

    $heartbeatAt   = $record->remote_heartbeat_at;
    $heartbeatDiff = $heartbeatAt ? $heartbeatAt->diffForHumans() : null;
@endphp

<style>
@keyframes pred-heartbeat {
    0%, 100% { transform: scale(1); }
    14%       { transform: scale(1.35); }
    28%       { transform: scale(1); }
    42%       { transform: scale(1.25); }
    70%       { transform: scale(1); }
}
.pred-heart { display:inline-block; animation: pred-heartbeat 1.4s ease-in-out infinite; color:#ef4444; }
</style>

<div style="display:flex; align-items:flex-start; gap:1rem; flex-wrap:wrap;">

    {{-- Remote current step (primary — reflects live server state) --}}
    @if ($remoteStepLabel)
        <div>
            <div style="font-size:.7rem; font-weight:500; color:#6b7280; margin-bottom:.25rem; text-transform:uppercase; letter-spacing:.04em;">
                Current step
            </div>
            <span style="display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .75rem;
                         border-radius:9999px; font-size:.8rem; font-weight:600;
                         background:{{ $remoteStepColor['bg'] }}; color:{{ $remoteStepColor['text'] }};">
                {{ $remoteStepLabel }}
            </span>
        </div>
    @endif

    {{-- Local workflow step (secondary — can lag behind remote) --}}
    <div>
        <div style="font-size:.7rem; font-weight:500; color:#6b7280; margin-bottom:.25rem; text-transform:uppercase; letter-spacing:.04em;">
            {{ $remoteStepLabel ? 'Workflow step' : 'Current step' }}
        </div>
        <span style="display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .75rem;
                     border-radius:9999px; font-size:.8rem; font-weight:600;
                     background:{{ $localStepColor['bg'] }}; color:{{ $localStepColor['text'] }};">
            {{ \Modules\PredictionWorkers\Models\Prediction::enumStep($record->step) }}
        </span>
    </div>

    {{-- State badge --}}
    <div>
        <div style="font-size:.7rem; font-weight:500; color:#6b7280; margin-bottom:.25rem; text-transform:uppercase; letter-spacing:.04em;">
            State
        </div>
        <span style="display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .75rem;
                     border-radius:9999px; font-size:.8rem; font-weight:600;
                     background:{{ $stateColor['bg'] }}; color:{{ $stateColor['text'] }};">
            <span style="width:7px; height:7px; border-radius:50%; background:{{ $stateColor['dot'] }};
                         flex-shrink:0;"></span>
            {{ \Modules\PredictionWorkers\Models\Prediction::enumState($record->state) }}
        </span>
    </div>

    {{-- Heartbeat (only when running and heartbeat exists) --}}
    @if ($isRunning && $heartbeatAt)
        <div>
            <div style="font-size:.7rem; font-weight:500; color:#6b7280; margin-bottom:.25rem; text-transform:uppercase; letter-spacing:.04em;">
                Heartbeat
            </div>
            <span style="display:inline-flex; align-items:center; gap:.4rem; font-size:.8rem; color:#374151;">
                <span class="pred-heart">♥</span>
                <span style="font-weight:600;">{{ $heartbeatAt->format('d.m.Y H:i:s') }}</span>
                <span style="color:#9ca3af;">({{ $heartbeatDiff }})</span>
            </span>
        </div>
    @endif

</div>
