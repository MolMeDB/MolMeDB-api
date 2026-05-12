@php
    use App\Models\UploadQueue;

    $logs = $record->logs
        ->sortByDesc(fn ($log) => $log->timestamp)
        ->values();

    $tone = function ($log): string {
        return match ($log->context->value) {
            'error' => 'border-danger-200 bg-danger-50 text-danger-800',
            'success' => 'border-success-200 bg-success-50 text-success-800',
            'warning' => 'border-warning-200 bg-warning-50 text-warning-800',
            default => 'border-gray-200 bg-gray-50 text-gray-800',
        };
    };

    $formatTimestamp = function (?string $timestamp): string {
        if (! $timestamp) {
            return '';
        }

        return preg_replace('/\.\d+Z?$/', '', str_replace('T', ' ', $timestamp)) ?? $timestamp;
    };
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
        <div>
            <div class="text-gray-500">Dataset</div>
            <div class="font-semibold">{{ $record->dataset?->name ?? 'Unnamed' }}</div>
        </div>
        <div>
            <div class="text-gray-500">File</div>
            <div class="font-semibold">{{ $record->file?->name ?? '-' }}</div>
        </div>
        <div>
            <div class="text-gray-500">State</div>
            <div class="font-semibold">{{ UploadQueue::$ui_enum_states[$record->state] ?? UploadQueue::enumState($record->state) }}</div>
        </div>
    </div>

    <div class="space-y-2">
        @forelse ($logs as $log)
            @php
                $payload = $log->payload ? json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null;
                $stateLabel = UploadQueue::$ui_enum_states[$log->state] ?? UploadQueue::enumState($log->state);
            @endphp

            <div class="rounded-lg border px-3 py-2 text-sm {{ $tone($log) }}">
                <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                    <div class="font-semibold">{{ $log->type->value }} · {{ $stateLabel }}</div>
                    <div class="opacity-75">{{ $formatTimestamp($log->timestamp) }}</div>
                </div>
                <div class="mt-2 whitespace-pre-wrap">{{ $log->message }}</div>
                @if ($payload)
                    <pre class="mt-2 max-h-56 overflow-auto rounded-md bg-white/70 px-3 py-2 text-xs text-gray-900">{{ $payload }}</pre>
                @endif
            </div>
        @empty
            <div class="text-sm text-gray-500">No logs available.</div>
        @endforelse
    </div>
</div>
