<?php

namespace App\Filament\Resources\ProteinResource\RelationManagers;

use App\Enums\IconEnums;
use App\Models\Protein;
use App\Models\ProteinIdentifier;
use App\Models\User;
use App\Rules\SubstanceIdentifier;
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
    protected static string $model = ProteinIdentifier::class;
    protected static ?string $icon = IconEnums::IDENTIFIERS->value;

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
                            $rule = new SubstanceIdentifier($this->ownerRecord, $get('type'));
                            $rule->validate($attribute, $value, $fail);
                        },
                        
                    ])
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->columnSpanFull()
                    ->options(ProteinIdentifier::types()),
                Forms\Components\Select::make('state_visible')
                    ->hint('Remember, no additional validation is provided after saving.')
                    ->columnSpanFull()
                    ->hiddenOn('edit')
                    ->options(ProteinIdentifier::states())
                    ->default(ProteinIdentifier::STATE_VALIDATED)
                    ->disabled(),
                Forms\Components\Hidden::make('source_id')
                        ->default(Auth::user()->id),
                Forms\Components\Hidden::make('source_type')
                        ->default(User::class),
                Forms\Components\Hidden::make('state')
                    ->default(ProteinIdentifier::STATE_VALIDATED),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->description('Other protein identifiers such as alternative names.')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->color(fn(ProteinIdentifier $record) => $record->trashed() ? 'danger' : null)
                    ->tooltip(fn(ProteinIdentifier $record) => $record->trashed() ? 'Deleted record' : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('protein.uniprot_id')
                    ->label('Uniprot ID')
                    ->sortable()
                    ->visible(fn (): bool => $this->getOwnerRecord()::class !== Protein::class)
                    ->color('warning'),
                    Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) : string => ProteinIdentifier::enumType($state))
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
                    ->visible(fn (): bool => $this->getOwnerRecord()::class == Protein::class)
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\IconColumn::make('state')
                    ->alignCenter()
                    ->label('State')
                    ->sortable()
                    ->icon(fn (?string $state): string => match ($state) {
                        strval(ProteinIdentifier::STATE_NEW) => IconEnums::STATE_NEW->value,
                        strval(ProteinIdentifier::STATE_VALIDATED) => IconEnums::STATE_VALIDATED->value,
                        strval(ProteinIdentifier::STATE_INVALID) => IconEnums::STATE_INVALID->value,
                        default => IconEnums::QUESTION_MARK->value,
                    })
                    ->tooltip(fn (?string $state): string => match ($state) {
                        strval(ProteinIdentifier::STATE_NEW) => 'Waiting for validation',
                        strval(ProteinIdentifier::STATE_VALIDATED) => 'Validated',
                        strval(ProteinIdentifier::STATE_INVALID) => 'Invalid identifier',
                        default => 'Unknown state',
                    })
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New identifier')
                    ->icon(IconEnums::ADD->value)
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
