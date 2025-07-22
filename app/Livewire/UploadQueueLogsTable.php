<?php

namespace App\Livewire;

use App\Enums\UploadQUeueLogContextEnums;
use App\Models\UploadQueue;
use App\Models\User;
use App\ValueObjects\UploadQueueLog;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

class UploadQueueLogsTable extends TableWidget implements Tables\Contracts\HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public ?\App\Models\UploadQueue $record = null;
    protected int | string | array $columnSpan = 2;


    public function getTableRecords(): EloquentCollection
    {
        $records = $this->record->logs;

        // Sort
        $sort_by = $this->getTableSortColumn() ?? "timestamp";
        $sort_dir = $this->getTableSortDirection() ?? "asc";
        $records = $records->sortBy($sort_by, SORT_REGULAR, $sort_dir === 'asc');

        return $records;
    }

    public function getTableRecordKey(Model $record): string
    {
        return $record->message;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(UploadQueue::query()->where('id', $this->record->id))
            ->description('Logs describing the history of the upload process.')
            ->paginationPageOptions([10, 25, 50])
            ->paginated(10)
            ->columns([
                TextColumn::make('context')
                    ->badge()
                    ->sortable()
                    ->label('Type'),
                TextColumn::make('message')
                    ->html()
                    ->color(fn (UploadQueueLog $record) => match($record->context) {
                        UploadQUeueLogContextEnums::ERROR => 'danger',
                        UploadQUeueLogContextEnums::INFO => 'primary',
                        UploadQUeueLogContextEnums::SUCCESS => 'success',
                        UploadQUeueLogContextEnums::WARNING => 'warning',
                        default => 'default',
                    })
                    ->label('Log message'),
                TextColumn::make('user_id')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => User::find($state)?->name)
                    ->label('User'),
                TextColumn::make('timestamp')
                    ->dateTime()
                    ->sortable()
                    ->label('Date'),

            ])
            ->emptyStateHeading('No logs found.')
            ->defaultSort('timestamp', 'desc')
            ->filters([
                // ...
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }

    public function render(): View
    {
        return view('livewire.oneline-logs-table');
    }
}