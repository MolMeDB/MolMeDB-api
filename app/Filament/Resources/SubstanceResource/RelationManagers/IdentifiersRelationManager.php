<?php

namespace App\Filament\Resources\SubstanceResource\RelationManagers;

use App\Enums\IconEnums;
use App\Models\SubstanceIdentifier;
use App\Rules\SubstanceIdentifier as RulesSubstanceIdentifier;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IdentifiersRelationManager extends RelationManager
{
    protected static string $relationship = 'identifiers';

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
                            $rule = new RulesSubstanceIdentifier($this->ownerRecord, $get('type'));
                            $rule->validate($attribute, $value, $fail);
                            // $fail('dwdwd ' . $this->ownerRecord->id);
                            // $fail('dwdw' . $get('type'));
                        },
                        
                    ])
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->columnSpanFull()
                    ->options(SubstanceIdentifier::types()),
                Forms\Components\Toggle::make('is_active')
                    ->hint('If active, the value will replace current active value if exists.')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('state_visible')
                    ->required()
                    ->hint('Remember, no additional validation is provided after saving.')
                    ->columnSpanFull()
                    ->options(SubstanceIdentifier::states())
                    ->default(SubstanceIdentifier::STATE_VALIDATED)
                    ->disabled(),
                Forms\Components\Hidden::make('state')
                    ->default(SubstanceIdentifier::STATE_VALIDATED),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->columns([
                Tables\Columns\TextColumn::make('value')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->columnSpan(2),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) : string => SubstanceIdentifier::enumType($state))
                    ->sortable()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('server')
                    ->badge()
                    ->label('Source')
                    ->sortable()
                    ->tooltip('States the source of the identifier, if obtained automatically.')
                    ->color('success')
                    ->formatStateUsing(fn (string $state) : string => SubstanceIdentifier::enumServers($state))
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\IconColumn::make('is_active')
                    ->alignCenter()
                    ->label('Active?')
                    ->sortable()
                    ->boolean(),
                Tables\Columns\IconColumn::make('state')
                    ->alignCenter()
                    ->label('State')
                    ->sortable()
                    ->icon(fn (?string $state): string => match ($state) {
                        strval(SubstanceIdentifier::STATE_NEW) => IconEnums::STATE_NEW->value,
                        strval(SubstanceIdentifier::STATE_VALIDATED) => IconEnums::STATE_VALIDATED->value,
                        strval(SubstanceIdentifier::STATE_INVALID) => IconEnums::STATE_INVALID->value,
                        default => IconEnums::QUESTION_MARK->value,
                    })
                    ->tooltip(fn (?string $state): string => match ($state) {
                        strval(SubstanceIdentifier::STATE_NEW) => 'Waiting for validation',
                        strval(SubstanceIdentifier::STATE_VALIDATED) => 'Validated',
                        strval(SubstanceIdentifier::STATE_INVALID) => 'Invalid identifier',
                        default => 'Unknown state',
                    })
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
