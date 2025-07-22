<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\MethodResource\Pages;
use App\Filament\Resources\SharedRelationManagers;
use App\Models\Category;
use App\Models\Method;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class MethodResource extends Resource
{
    protected static ?string $model = Method::class;

    protected static ?string $navigationIcon = IconEnums::METHOD->value;
    protected static ?string $navigationGroup = 'Data management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Special type')
                            ->disabled()
                            ->options(Method::types())
                            ->columnSpanFull(),
                        SelectTree::make('categories')
                            ->relationship('categories', 'title', 'parent_id', modifyQueryUsing: fn (Builder $query) => $query->where('type', Category::TYPE_METHOD))
                            ->required()
                            ->pivotData(['model_type' => Method::class])
                            ->withCount()
                            ->parentNullValue(-1)
                            ->defaultOpenLevel(2)
                            ->clearable(false)
                            ->placeholder('Please, select method category')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->hint('Maximum 150 characters.')
                            ->maxLength(150)
                            ->unique(ignoreRecord: true)
                            ->required(),
                        Forms\Components\TextInput::make('abbreviation')
                            ->hint('Maximum 15 characters.')
                            ->maxLength(15)
                            ->minLength(2)
                            ->unique(ignoreRecord: true)
                            ->rule('regex:/^[a-zA-Z0-9-_]+$/') 
                            ->required(),
                        Forms\Components\RichEditor::make('description')
                            ->fileAttachmentsDirectory(self::$model::folder().'attachments')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Configuration')
                    ->description('It is used to set up alerts for unusual values in interactions.')
                    ->schema([
                        FieldSet::make('LogPerm custom alert limits')
                            ->schema([
                                Forms\Components\TextInput::make('parameters.alert_limits.logperm.min')
                                    ->numeric(),
                                Forms\Components\TextInput::make('parameters.alert_limits.logperm.max')
                                    ->numeric(),
                            ])
                            ->columns(2),
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
                    ->formatStateUsing(fn (string $state) : string => Method::enumType($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('abbreviation')
                    ->label('Abbrev.')
                    ->badge()
                    ->color(fn (Method $record) => $record->trashed() ? 'danger' : 'primary')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->label('Name and description')
                    ->description(fn(Method $record) => Str::limit(strip_tags($record->description), 90))
                    ->searchable(),
                Tables\Columns\TextColumn::make('keywords.value')
                    ->label('Keywords')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->badge()
                    ->alignCenter()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(Method::types()),
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
            'index' => Pages\ListMethods::route('/'),
            'create' => Pages\CreateMethod::route('/create'),
            'edit' => Pages\EditMethod::route('/{record}/edit'),
        ];
    }
}
