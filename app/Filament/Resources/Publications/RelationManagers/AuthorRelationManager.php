<?php

namespace App\Filament\Resources\Publications\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use App\Enums\IconEnums;
use App\Models\Author;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AuthorRelationManager extends RelationManager
{
    protected static string $relationship = 'authors';
    protected static string | \BackedEnum | null $icon = IconEnums::AUTHORS->value;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->description('Authors table is updated automatically after saving a publication record.')
            ->columns([
                TextColumn::make('last_name')
                    ->searchable()
                    ->label('Full name')
                    ->formatStateUsing(fn (Author $record) => "$record->first_name $record->last_name")
                    ->description(fn (Author $record) => Str::limit($record->affiliation, 150))
                    ->tooltip(fn (Author $record) => $record->affiliation)
                    ->sortable(),
                TextColumn::make('email'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->recordActions([
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                ]),
            ]);
    }
}
