<?php

namespace App\Filament\Resources\SharedRelationManagers;

use App\Enums\IconEnums;
use App\Filament\Resources\StructureResource;
use App\Models\Dataset;
use App\Models\Identifier;
use App\Models\Structure;
use App\Models\User;
use App\Rules\SubstanceIdentifier as RulesIdentifier;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class IdentifiersRelationManager extends RelationManager
{
    protected static string $relationship = 'identifiers';
    protected static ?string $icon = IconEnums::IDENTIFIERS->value;
    protected static ?string $title = 'Identifiers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->hint('The value will be validated before saving if possible.')
                    ->columnSpanFull()
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            $rule = new RulesIdentifier($this->ownerRecord, $get('type'));
                            $rule->validate($attribute, $value, $fail);
                        },
                        
                    ])
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->columnSpanFull()
                    ->options(Identifier::types()),
                Forms\Components\Select::make('state_visible')
                    ->hint('Remember, no additional validation is provided after saving.')
                    ->columnSpanFull()
                    ->hiddenOn('edit')
                    ->options(Identifier::states())
                    ->default(Identifier::STATE_VALIDATED)
                    ->disabled(),
                Forms\Components\Hidden::make('source_id')
                        ->default(Auth::user()->id),
                Forms\Components\Hidden::make('source_type')
                        ->default(User::class),
                Forms\Components\Hidden::make('state')
                    ->default(Identifier::STATE_VALIDATED),
            ]);
    }

    public function table(Table $table): Table
    {
        static $isParentTrashed = $this->ownerRecord->trashed();
        return $table
            ->recordTitleAttribute('value')
            ->description(fn() : ?string => $this->getDescription())
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->color(fn(Identifier $record) => $record->trashed() ? 'danger' : null)
                    ->tooltip(fn(Identifier $record) => $record->trashed() ? 'Deleted record' : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('structure.identifier')
                    ->label('Structure')
                    ->sortable()
                    ->visible(fn (): bool => !$this->isSourceTypeOwner())
                    ->color('warning'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) : string => Identifier::enumType($state))
                    ->sortable()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('value')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->columnSpan(2),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->label('Source')
                    ->sortable()
                    ->wrap()
                    ->tooltip('The source of the identifier.')
                    ->formatStateUsing(fn (Model $record) : string => Str::limit($record->source->name(), 20))
                    ->visible(fn (): bool => !$this->isSourceTypeOwner())
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\IconColumn::make('state')
                    ->alignCenter()
                    ->label('State')
                    ->sortable()
                    ->icon(fn (?string $state): string => match ($state) {
                        strval(Identifier::STATE_NEW) => IconEnums::STATE_NEW->value,
                        strval(Identifier::STATE_VALIDATED) => IconEnums::STATE_VALIDATED->value,
                        strval(Identifier::STATE_INVALID) => IconEnums::STATE_INVALID->value,
                        default => IconEnums::QUESTION_MARK->value,
                    })
                    ->tooltip(fn (?string $state): string => match ($state) {
                        strval(Identifier::STATE_NEW) => 'Waiting for validation',
                        strval(Identifier::STATE_VALIDATED) => 'Validated',
                        strval(Identifier::STATE_INVALID) => 'Invalid identifier',
                        default => 'Unknown state',
                    }),
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
                Tables\Filters\TrashedFilter::make()
                    ->default($isParentTrashed ? 1 : null),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (): bool => $this->createButtonVisible())
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Identifier $record): bool => $record->source_type == User::class),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Identifier $record): bool => $record->source_type == User::class),
                Tables\Actions\Action::make('compound_detail')
                    ->label('Structure')
                    ->icon(IconEnums::VIEW->value)
                    ->url(fn ($record) => StructureResource::getUrl('edit', ['record' => $record->structure]))
                    ->visible(fn() : bool => $this->isSourceTypeOwner()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function createButtonVisible() : bool
    {
        return !in_array($this->ownerRecord::class, [
            Dataset::class
        ]);
    }

    private function isSourceTypeOwner() : bool
    {
        return !in_array($this->ownerRecord::class, [
            Structure::class
        ]);
    }

    private function getDescription() : ?string {
        return match($this->ownerRecord::class) {
            Dataset::class => 'Structure identifiers added from current dataset',
            default => null  
        };
    }
}
