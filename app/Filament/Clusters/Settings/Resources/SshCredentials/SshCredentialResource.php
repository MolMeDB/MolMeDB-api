<?php

namespace App\Filament\Clusters\Settings\Resources\SshCredentials;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Settings\Resources\SshCredentials\Pages\ListSshCredentials;
use App\Filament\Clusters\Settings\Resources\SshCredentials\Pages\CreateSshCredential;
use App\Filament\Clusters\Settings\Resources\SshCredentials\Pages\EditSshCredential;
use App\Filament\Clusters\Settings\Resources\SshCredentials\Pages\SshCredentialActivities;
use App\Enums\IconEnums;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Clusters\Settings\Resources\SshCredentialResource\Pages;
use App\Models\SshCredential;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SshCredentialResource extends Resource
{
    protected static ?string $model = SshCredential::class;

    protected static string | \BackedEnum | null $navigationIcon = IconEnums::COMMAND_LINE->value;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Services';

    protected static ?string $navigationLabel = 'SSH Credentials';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(self::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->columnSpanFull()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->hint('A descriptive name for this SSH Credential.'),

            Section::make('Authorization details')
                ->schema([
                    Select::make('type')
                        ->required()
                        ->label('Authentication type')
                        ->columnSpanFull()
                        ->reactive()
                        ->options(SshCredential::types()),
                    TextInput::make('username')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('password')
                        ->password()
                        ->maxLength(255)
                        ->reactive()
                        ->required(fn (Get $get) => $get('type') === SshCredential::AUTH_TYPE_PASSWORD)
                        ->hint('Leave empty for key-based authentication.'),
                    Textarea::make('private_key')
                        ->rows(5)
                        ->minLength(20)
                        ->maxLength(65535)
                        ->nullable()
                        ->reactive()
                        ->required(fn (Get $get) => $get('type') === SshCredential::AUTH_TYPE_KEY)
                        ->columnSpanFull()
                        ->hint('The private key for key-based authentication. Leave empty to use password authentication.'),
                    TextInput::make('passphrase')
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                TextColumn::make('host')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(fn (SshCredential $record) : string => $record->username . '@' . $record->host . ':' . $record->port)
                    ->label('Host'),
                TextColumn::make('type')
                    ->badge()
                    ->label('Authentication')
                    ->formatStateUsing(fn (string $state) : string => SshCredential::types()[$state] ?? $state)
                    ->sortable()
                    ->color('primary'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSshCredentials::route('/'),
            'create' => CreateSshCredential::route('/create'),
            'edit' => EditSshCredential::route('/{record}/edit'),
            'activities' => SshCredentialActivities::route('/{record}/activities'),
        ];
    }
}
