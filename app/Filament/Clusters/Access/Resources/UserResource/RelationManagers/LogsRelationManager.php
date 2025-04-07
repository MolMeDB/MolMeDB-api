<?php

namespace App\Filament\Clusters\Access\Resources\UserResource\RelationManagers;

use App\Enums\IconEnums;
use App\Filament\Clusters\Access\Resources\UserResource;
use App\Filament\Resources\DatasetResource;
use App\Filament\Resources\MembraneResource;
use App\Filament\Resources\MethodResource;
use App\Filament\Resources\ProteinResource;
use App\Filament\Resources\PublicationResource;
use App\Filament\Resources\StructureResource;
use App\Models\Dataset;
use App\Models\Membrane;
use App\Models\Method;
use App\Models\Protein;
use App\Models\Publication;
use App\Models\Structure;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';
    protected static ?string $icon = IconEnums::LOGS->value;
    protected static ?string $title = 'Logs';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->description('User activity logs')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->sortable()
                    ->searchable()
                    ->color(fn ($state) => match($state) {
                        'edited' => 'warning',
                        'deleted' => 'danger',
                        default => 'primary',
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Target')
                    ->color('primary')
                    ->sortable()
                    ->formatStateUsing(function ($state, Model $record) {
                        $name = preg_replace('/[^\\\\]+\\\\/', '', $state);
                        return $name . ' [' . $record->subject_id . ']';
                    })
                    ->tooltip('Click to open the record')
                    ->url(fn ($state, Model $record) => $record->subject ? match($record->subject_type) {
                        Dataset::class => DatasetResource::getUrl('edit', ['record' => $record->subject]), 
                        Publication::class => PublicationResource::getUrl('edit', ['record' => $record->subject]), 
                        User::class => UserResource::getUrl('edit', ['record' => $record->subject]),
                        Membrane::class => MembraneResource::getUrl('edit', ['record' => $record->subject]),
                        Method::class => MethodResource::getUrl('edit', ['record' => $record->subject]),
                        Protein::class => ProteinResource::getUrl('edit', ['record' => $record->subject]),
                        Structure::class => StructureResource::getUrl('edit', ['record' => $record->subject]),
                        default => null
                    } : null)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTimeTooltip()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                //
            ])
            ->modifyQueryUsing(fn ($query) => $query->orderBy('created_at', 'desc'))
            ->headerActions([
            ]);
    }
}
