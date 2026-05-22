@php
    use App\Models\UploadQueue;

    $columns = collect($rows)
        ->flatMap(fn (array $row) => array_keys($row))
        ->unique()
        ->values();
@endphp

<div class="space-y-5" style="display: flex; flex-direction: column; gap: 20px;">
    <div
        class="grid grid-cols-1 gap-3 text-sm md:grid-cols-4"
        style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px;"
    >
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900" style="border: 1px solid #d1d5db; border-radius: 10px; background: #f8fafc; padding: 12px 16px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);">
            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Dataset</div>
            <div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white" style="margin-top: 4px; font-size: 14px; font-weight: 700; color: #111827;">{{ $record->dataset?->name ?? 'Unnamed' }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900" style="border: 1px solid #d1d5db; border-radius: 10px; background: #f8fafc; padding: 12px 16px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);">
            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">File</div>
            <div class="mt-1 break-words text-sm font-semibold text-gray-950 dark:text-white" style="margin-top: 4px; overflow-wrap: anywhere; font-size: 14px; font-weight: 700; color: #111827;">{{ $record->file?->name ?? '-' }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900" style="border: 1px solid #d1d5db; border-radius: 10px; background: #f8fafc; padding: 12px 16px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);">
            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">State</div>
            <div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white" style="margin-top: 4px; font-size: 14px; font-weight: 700; color: #111827;">{{ UploadQueue::$ui_enum_states[$record->state] ?? UploadQueue::enumState($record->state) }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900" style="border: 1px solid #d1d5db; border-radius: 10px; background: #f8fafc; padding: 12px 16px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);">
            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400" style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Validated rows</div>
            <div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white" style="margin-top: 4px; font-size: 14px; font-weight: 700; color: #111827;">{{ $record->config->validatedRows() ?? '-' }}</div>
        </div>
    </div>

    @if ($record->config->adminReviewRejectedReason())
        <div class="rounded-lg border border-danger-200 bg-danger-50 px-3 py-2 text-sm text-danger-800">
            {{ $record->config->adminReviewRejectedReason() }}
        </div>
    @endif

    @if ($columns->isNotEmpty())
        <div
            x-data="{ visibleRows: 20, totalRows: {{ count($rows) }} }"
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            style="border: 1px solid #d1d5db; border-radius: 10px; background: #ffffff; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08); overflow: hidden;"
        >
            <div
                class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800"
                style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #d1d5db; background: #f8fafc;"
            >
                <div>
                    <div class="text-sm font-semibold text-gray-950 dark:text-white" style="font-size: 15px; font-weight: 700; color: #111827;">Mapped rows preview</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400" style="margin-top: 2px; font-size: 12px; color: #6b7280;">
                        Showing <span x-text="Math.min(visibleRows, totalRows)"></span> of {{ count($rows) }} loaded rows
                    </div>
                </div>
                <div
                    class="rounded-full bg-gray-200 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                    style="border-radius: 999px; background: #e5e7eb; padding: 4px 10px; font-size: 12px; font-weight: 600; color: #374151; white-space: nowrap;"
                >
                    {{ count($columns) }} columns
                </div>
            </div>

            <div
                class="max-h-[32rem] overflow-auto"
                style="max-height: 32rem; overflow: auto;"
                @scroll="if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 80) visibleRows = Math.min(visibleRows + 20, totalRows)"
            >
            <table class="min-w-full border-separate border-spacing-0 text-left text-sm" style="width: 100%; min-width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px;">
                <thead class="sticky top-0 z-10 bg-gray-100 text-gray-700 shadow-sm dark:bg-gray-800 dark:text-gray-200" style="position: sticky; top: 0; z-index: 10; background: #eef2f7; color: #374151; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <th class="whitespace-nowrap border-b border-gray-200 px-4 py-3 text-xs font-semibold uppercase dark:border-gray-700" style="border-right: 1px solid #d1d5db; border-bottom: 1px solid #cbd5e1; padding: 11px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; white-space: nowrap; width: 1%; color: #475569;">#</th>
                        @foreach ($columns as $column)
                            <th class="whitespace-nowrap border-b border-gray-200 px-4 py-3 text-xs font-semibold uppercase dark:border-gray-700" style="border-right: {{ $loop->last ? '0' : '1px solid #d1d5db' }}; border-bottom: 1px solid #cbd5e1; padding: 11px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; white-space: nowrap; color: #475569;">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($rows as $index => $row)
                        <tr
                            x-show="{{ $index }} < visibleRows"
                            class="odd:bg-white even:bg-gray-50 hover:bg-warning-50/60 dark:odd:bg-gray-900 dark:even:bg-gray-950 dark:hover:bg-gray-800"
                            style="background: {{ $index % 2 === 0 ? '#ffffff' : '#f9fafb' }};"
                        >
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-500 dark:text-gray-400" style="border-right: 1px solid #e5e7eb; border-bottom: 1px solid #eef2f7; padding: 10px 14px; color: #64748b; font-weight: 600; white-space: nowrap; vertical-align: top;">{{ $index + 1 }}</td>
                            @foreach ($columns as $column)
                                <td class="max-w-sm whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100" title="{{ $row[$column] ?? '' }}" style="border-right: {{ $loop->last ? '0' : '1px solid #e5e7eb' }}; border-bottom: 1px solid #eef2f7; padding: 10px 14px; color: #111827; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; white-space: nowrap; vertical-align: top;">
                                    <span style="display: inline-block; max-width: 26rem; overflow: hidden; text-overflow: ellipsis; vertical-align: top;">{{ $row[$column] ?? '' }}</span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            @if (count($rows) > 20)
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-2 text-center text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    Scroll down to reveal more rows.
                </div>
            @endif
        </div>
    @else
        <div class="rounded-lg border border-warning-200 bg-warning-50 px-3 py-2 text-sm text-warning-800">
            No mapped rows are available for review.
        </div>
    @endif

    <div class="rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-900 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-100" style="display: flex; gap: 12px; align-items: flex-start; border: 1px solid #f59e0b; border-radius: 10px; background: #fffbeb; padding: 14px 16px; color: #78350f;">
        <div style="display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; flex: 0 0 28px; border-radius: 999px; background: #fef3c7; color: #92400e; font-weight: 800;">!</div>
        <div>
            <div style="font-size: 14px; font-weight: 700; color: #78350f;">Review required before decision</div>
            <div style="margin-top: 2px; font-size: 13px; line-height: 1.5; color: #92400e;">
                Approve or reject only after checking the mapped table above. The decision actions are below this preview.
            </div>
        </div>
    </div>
</div>
