<?php

namespace App\Filament\Resources\Methods;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Fieldset;
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
use App\Filament\Resources\Methods\Pages\ListMethods;
use App\Filament\Resources\Methods\Pages\CreateMethod;
use App\Filament\Resources\Methods\Pages\EditMethod;
use App\Enums\IconEnums;
use App\Models\Category;
use App\Models\Method;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class MethodResource extends Resource
{
    protected static ?string $model = Method::class;

    protected static string | \BackedEnum | null $navigationIcon = IconEnums::METHOD->value;
    protected static string | \UnitEnum | null $navigationGroup = 'Data management';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Description')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->hint('Maximum 150 characters.')
                            ->maxLength(150)
                            ->unique(ignoreRecord: true)
                            ->required(),
                        TextInput::make('abbreviation')
                            ->hint('Maximum 15 characters.')
                            ->maxLength(15)
                            ->minLength(2)
                            ->unique(ignoreRecord: true)
                            ->rule('regex:/^[a-zA-Z0-9-_]+$/') 
                            ->required(),
                        RichEditor::make('description')
                            ->fileAttachmentsDirectory(self::$model::folder().'attachments')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull(),
                    ]),
                Section::make('Assignment')
                    ->schema([
                        Select::make('type')
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
                Section::make('Configuration')
                    ->description('It is used to set up alerts for unusual values in interactions.')
                    ->schema([
                        Fieldset::make('LogPerm custom alert limits')
                            ->schema([
                                TextInput::make('parameters.alert_limits.logperm.min')
                                    ->numeric(),
                                TextInput::make('parameters.alert_limits.logperm.max')
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
                TextColumn::make('type')
                    ->badge()
                    ->label('Special type')
                    ->color('warning')
                    ->formatStateUsing(fn (string $state) : string => Method::enumType($state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('abbreviation')
                    ->label('Abbrev.')
                    ->badge()
                    ->color(fn (Method $record) => $record->trashed() ? 'danger' : 'primary')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->sortable()
                    ->label('Name and description')
                    ->description(fn(Method $record) => Str::limit(strip_tags($record->description), 90))
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
                    ->options(Method::types()),
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
            InteractionsActiveRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMethods::route('/'),
            'create' => CreateMethod::route('/create'),
            'edit' => EditMethod::route('/{record}/edit'),
        ];
    }
}
