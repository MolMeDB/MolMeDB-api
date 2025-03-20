<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\MembraneResource\Pages;
use App\Filament\Resources\SharedRelationManagers;
use App\Models\Category;
use App\Models\Membrane;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MembraneResource extends Resource
{
    protected static ?string $model = Membrane::class;
    protected static ?string $navigationIcon = IconEnums::MEMBRANE->value;
    protected static ?string $navigationGroup = 'Data management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Assignment')
                    ->schema([
                        Forms\Components\Select::make('type')
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
                Fieldset::make('Description')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->hint('Maximum 150 characters.')
                            ->maxLength(150)
                            ->required(),
                        Forms\Components\TextInput::make('abbreviation')
                            ->hint('Maximum 15 characters.')
                            ->maxLength(15)
                            ->minLength(2)
                            ->rule('regex:/^[a-zA-Z0-9-_]+$/') 
                            ->required(),
                        Forms\Components\RichEditor::make('description')
                          ->fileAttachmentsDirectory(self::$model::folder().'attachments')
                          ->fileAttachmentsDisk('public')
                          ->fileAttachmentsVisibility('public')
                          ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Special type')
                    ->color('warning')
                    ->formatStateUsing(fn (string $state) : string => Membrane::enumType($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('abbreviation')
                    ->label('Abbrev.')
                    ->badge()
                    ->color(fn (Membrane $record) => $record->trashed() ? 'danger' : 'primary')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->wrap()
                    ->lineClamp(2)
                    ->searchable(),
                Tables\Columns\TextColumn::make('keywords.value')
                    ->label('Keywords')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->badge()
                    ->alignCenter()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(Membrane::types()),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->modalHeading('Restore method?')
                    ->modalDescription('Warning! All associated files, datasets and interactions will be also restored and be directly visible.')
                    ->modalSubmitActionLabel('Understand. Restore')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            SharedRelationManagers\PublicationsRelationManager::class,
            SharedRelationManagers\KeywordsRelationManager::class,
            SharedRelationManagers\FileRelationManager::class,
            SharedRelationManagers\DatasetsRelationManager::class,
            SharedRelationManagers\InteractionsPassiveRelationManager::class,
            SharedRelationManagers\InteractionsActiveRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembranes::route('/'),
            'create' => Pages\CreateMembrane::route('/create'),
            'edit' => Pages\EditMembrane::route('/{record}/edit'),
            // 'categoryTree' => Pages\MembraneCategoryTree::route('/manage'),
        ];
    }
}
