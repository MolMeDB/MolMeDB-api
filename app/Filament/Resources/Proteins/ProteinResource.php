<?php

namespace App\Filament\Resources\Proteins;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\SharedRelationManagers\InteractionsActiveRelationManager;
use App\Filament\Resources\Proteins\Pages\ListProteins;
use App\Filament\Resources\Proteins\Pages\CreateProtein;
use App\Filament\Resources\Proteins\Pages\EditProtein;
use App\Enums\IconEnums;
use App\Filament\Resources\Proteins\RelationManagers\IdentifiersRelationManager;
use App\Models\Category;
use App\Models\Protein;
use App\Services\External\Chemical\Chebi\Chebi;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProteinResource extends Resource
{
    protected static ?string $model = Protein::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::PROTEIN->value;
    protected static string | \UnitEnum | null $navigationGroup = 'Data management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic information')
                    ->schema([
                        TextInput::make('uniprot_id')
                            ->label('Uniprot ID')
                            ->required()
                            ->maxLength(50)
                            ->hint('Maximum 50 characters.')
                            ->hintColor('danger'),
                        SelectTree::make('categories')
                            ->relationship('categories', 'title', 'parent_id', modifyQueryUsing: fn ($query) => $query->where('type', Category::TYPE_PROTEIN))
                            ->required()
                            ->pivotData(['model_type' => Protein::class])
                            ->withCount()
                            ->parentNullValue(-1)
                            ->defaultOpenLevel(2)
                            ->clearable(false)
                            ->placeholder('Please, select protein category')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('uniprot_id')
                    ->label('Uniprot ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('interactionsActive_count')
                    ->getStateUsing(fn ($record) => $record->interactionsActive()->count())
                    ->label('Total interactions')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }


    public static function getRelations(): array
    {
        return [
            IdentifiersRelationManager::class,
            InteractionsActiveRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProteins::route('/'),
            'create' => CreateProtein::route('/create'),
            'edit' => EditProtein::route('/{record}/edit'),
        ];
    }
}
