<?php

namespace App\Filament\Resources;

use App\Enums\IconEnums;
use App\Filament\Resources\MembraneResource\Pages;
use App\Filament\Resources\MembraneResource\RelationManagers;
use App\Filament\Resources\MembraneResource\RelationManagers\PublicationsRelationManager;
use App\Filament\Resources\MembraneResource\Widgets\MembraneCategoryWidget;
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
    protected static ?int $navigationSort = 2;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Assignment')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Special type')
                            ->disabled()
                            ->options(Membrane::types())
                            ->columnSpanFull(),
                        SelectTree::make('category_id')
                            ->relationship('category', 'title', 'parent_id', modifyQueryUsing: fn (Builder $query) => $query->where('type', Category::TYPE_MEMBRANE))
                            ->required()
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
                            ->required(),
                        Forms\Components\RichEditor::make('description')
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
                    ->color('primary')
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->badge()
                    ->color('danger')
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Author')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PublicationsRelationManager::class,
            RelationManagers\KeywordsRelationManager::class
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
