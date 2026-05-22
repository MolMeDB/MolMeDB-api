<?php

namespace App\Filament\Resources\Membranes;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\SharedRelationManagers\PublicationsRelationManager;
use App\Filament\Resources\SharedRelationManagers\KeywordsRelationManager;
use App\Filament\Resources\SharedRelationManagers\FileRelationManager;
use App\Filament\Resources\SharedRelationManagers\DatasetsRelationManager;
use App\Filament\Resources\SharedRelationManagers\InteractionsPassiveRelationManager;
use App\Filament\Resources\SharedRelationManagers\InteractionsActiveRelationManager;
use App\Filament\Resources\Membranes\Pages\ListMembranes;
use App\Filament\Resources\Membranes\Pages\CreateMembrane;
use App\Filament\Resources\Membranes\Pages\EditMembrane;
use App\Enums\IconEnums;
use App\Models\Category;
use App\Models\Membrane;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class MembraneResource extends Resource
{
    protected static ?string $model = Membrane::class;
    protected static string | \BackedEnum | null $navigationIcon = IconEnums::MEMBRANE->value;
    protected static string | \UnitEnum | null $navigationGroup = 'Data management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('Description')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->hint('Maximum 150 characters.')
                            ->maxLength(150)
                            ->required(),
                        TextInput::make('abbreviation')
                            ->hint('Maximum 15 characters.')
                            ->maxLength(15)
                            ->minLength(2)
                            ->rule('regex:/^[a-zA-Z0-9-_]+$/') 
                            ->required(),
                        RichEditor::make('description')
                          ->fileAttachmentsDirectory(self::$model::folder().'attachments')
                          ->fileAttachmentsDisk('public')
                          ->fileAttachmentsVisibility('public')
                          ->columnSpanFull(),
                    ]),
                Fieldset::make('Assignment')
                    ->schema([
                        Select::make('type')
                            ->label('Special type')
                            ->disabled()
                            ->hint('Internal link for db purposes. Cannot be manually changed.')
                            ->hintColor('warning')
                            ->options(Membrane::types())
                            ->columnSpanFull(),
                        SelectTree::make('categories')
                            ->relationship('categories', 'title', 'parent_id', modifyQueryUsing: fn ($query) => $query->where('type', Category::TYPE_MEMBRANE))
                            ->required()
                            ->pivotData(['model_type' => Membrane::class])
                            ->withCount()
                            ->parentNullValue(-1)
                            ->defaultOpenLevel(2)
                            ->clearable(false)
                            ->placeholder('Please, select membrane category')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->label('Special type')
                    ->color('warning')
                    ->formatStateUsing(fn (string $state) : string => Membrane::enumType($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('abbreviation')
                    ->label('Abbrev.')
                    ->badge()
                    ->color(fn (Membrane $record) => $record->trashed() ? 'danger' : 'primary')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->sortable()
                    ->label('Name and description')
                    ->description(fn(Membrane $record) => Str::limit(strip_tags($record->description), 90))
                    ->searchable(),
                TextColumn::make('keywords.value')
                    ->label('Keywords')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->badge()
                    ->alignCenter()
                    ->searchable(),
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
                SelectFilter::make('type')
                    ->options(Membrane::types()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make()
                    ->modalHeading('Restore method?')
                    ->modalDescription('Warning! All associated files, datasets and interactions will be also restored and be directly visible.')
                    ->modalSubmitActionLabel('Understand. Restore')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            PublicationsRelationManager::class,
            KeywordsRelationManager::class,
            FileRelationManager::class,
            DatasetsRelationManager::class,
            InteractionsPassiveRelationManager::class,
            InteractionsActiveRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembranes::route('/'),
            'create' => CreateMembrane::route('/create'),
            'edit' => EditMembrane::route('/{record}/edit'),
            // 'categoryTree' => Pages\MembraneCategoryTree::route('/manage'),
        ];
    }
}
