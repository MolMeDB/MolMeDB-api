<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Enums\IconEnums;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\SshCredentialResource\Pages;
use App\Models\SshCredential;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SshCredentialResource extends Resource
{
    protected static ?string $model = SshCredential::class;

    protected static ?string $navigationIcon = IconEnums::COMMAND_LINE->value;

    protected static ?string $cluster = Settings::class;

    protected static ?string $navigationGroup = 'Services';

    protected static ?string $navigationLabel = 'SSH Credentials';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\TextInput::make('name')
                ->required()
                ->columnSpanFull()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->hint('A descriptive name for this SSH Credential.'),

            \Filament\Forms\Components\Section::make('Authorization details')
                ->schema([
                    \Filament\Forms\Components\Select::make('type')
                        ->required()
                        ->label('Authentication type')
                        ->columnSpanFull()
                        ->reactive()
                        ->options(SshCredential::types()),
                    \Filament\Forms\Components\TextInput::make('username')
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('password')
                        ->password()
                        ->maxLength(255)
                        ->reactive()
                        ->required(fn (Get $get) => $get('type') === SshCredential::AUTH_TYPE_PASSWORD)
                        ->hint('Leave empty for key-based authentication.'),
                    \Filament\Forms\Components\Textarea::make('private_key')
                        ->rows(5)
                        ->minLength(20)
                        ->maxLength(65535)
                        ->nullable()
                        ->reactive()
                        ->required(fn (Get $get) => $get('type') === SshCredential::AUTH_TYPE_KEY)
                        ->columnSpanFull()
                        ->hint('The private key for key-based authentication. Leave empty to use password authentication.'),
                    \Filament\Forms\Components\TextInput::make('passphrase')
                        ->password()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->nullable()
                        ->hint('The passphrase for the private key, if required.'),
                ])
                ->columns(2),
            ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                \Filament\Tables\Columns\TextColumn::make('host')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(fn (SshCredential $record) : string => $record->username . '@' . $record->host . ':' . $record->port)
                    ->label('Host'),
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Authentication')
                    ->formatStateUsing(fn (string $state) : string => SshCredential::types()[$state] ?? $state)
                    ->sortable()
                    ->color('primary'),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSshCredentials::route('/'),
            'create' => Pages\CreateSshCredential::route('/create'),
            'edit' => Pages\EditSshCredential::route('/{record}/edit'),
            'activities' => Pages\SshCredentialActivities::route('/{record}/activities'),
        ];
    }
}
