<?php

namespace App\Filament\Resources\PublicationResource\RelationManagers;

use App\Enums\IconEnums;
use App\Models\Author;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AuthorRelationManager extends RelationManager
{
    protected static string $relationship = 'authors';
    protected static ?string $icon = IconEnums::AUTHORS->value;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->description('Authors table is updated automatically after saving a publication record.')
            ->columns([
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->label('Full name')
                    ->formatStateUsing(fn (Author $record) => "$record->first_name $record->last_name")
                    ->description(fn (Author $record) => Str::limit($record->affiliation, 150))
                    ->tooltip(fn (Author $record) => $record->affiliation)
                    ->sortable(),
                Tables\Columns\TextColumn::make('email'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
