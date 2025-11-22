<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Enums\IconEnums;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\FilesystemResource\Pages;
use App\Models\Filesystem;
use App\Models\SshCredential;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FilesystemResource extends Resource
{
    protected static ?string $model = Filesystem::class;

    protected static ?string $navigationIcon = IconEnums::SERVER->value;

    protected static ?string $cluster = Settings::class;

    protected static ?string $navigationGroup = 'Filesystem';

    public static function form(Form $form): Form
    {
        return $form
            ->disabled(fn (?Filesystem $record) => $record && $record->type < 0)
            ->schema([
                \Filament\Forms\Components\Select::make('type')
                    ->label('Service type')
                    ->options(Filesystem::types())
                    ->disableOptionWhen(function ($value, ?Filesystem $record) {
                        $t = Filesystem::where('type', $value)->first();
                        return $t !== null && $t->id !== $record?->id;
                    } )
                    ->hint('Each service type can only be added once.')
                    ->hintColor('warning')
                    ->columnSpanFull()
                    ->required(),
                \Filament\Forms\Components\Select::make('ssh_credential_id')
                    ->label('SSH Credential')
                    ->searchable()
                    ->preload()
                    ->disabled(fn (Get $get) => $get('driver') == Filesystem::DRIVER_LOCAL)
                    ->suffixAction(fn (Get $get) => $get('driver') == Filesystem::DRIVER_LOCAL ? null :
                        \Filament\Forms\Components\Actions\Action::make('create')
                            ->label('New SSH Credential')
                            ->modal()
                            ->icon(IconEnums::ADD->value)
                            ->tooltip('Create a new SSH Credential')
                            ->form(SshCredentialResource::getFormSchema())
                            ->action(function (array $data, callable $set) {
                                $credential = SshCredential::create($data);
                                $set('ssh_credential_id', $credential->id);
                            })
                        )
                    ->relationship('sshCredential', 'name'),
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                \Filament\Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->nullable()
                    ->hint('A brief description of the service.'),

                \Filament\Forms\Components\Section::make('Connection details')
                    ->schema([
                        \Filament\Forms\Components\Select::make('scope_id')
                            ->relationship('scope', 'name', fn ($query) => $query->where('scope_id', null)->orderBy('id', 'asc'), true)
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, ?string $state) {
                                $record = Filesystem::find($state);
                                $set('driver', $record?->driver);
                                $set('host', $record?->host);
                                $set('port', $record?->port);
                            })
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Group::make()
                            ->columnSpanFull()
                            ->columns(2)
                            ->reactive()
                            ->schema([
                                \Filament\Forms\Components\Select::make('driver')
                                    ->options(Filesystem::drivers())
                                    ->required()
                                    ->disabled(fn (Get $get) => filled($get('scope_id')))
                                    ->hint('Driver used by the service.'),
                                \Filament\Forms\Components\TextInput::make('host')
                                    ->required(fn (Get $get) => $get('driver') !== Filesystem::DRIVER_LOCAL)
                                    ->disabled(fn (Get $get) => filled($get('scope_id')))
                                    ->maxLength(255),
                                \Filament\Forms\Components\TextInput::make('port')
                                    ->required(fn (Get $get) => $get('driver') !== Filesystem::DRIVER_LOCAL)
                                    ->numeric()
                                    ->disabled(fn (Get $get) => filled($get('scope_id')))
                                    ->default(22),
                                \Filament\Forms\Components\TextInput::make('root_path')
                                    ->label('Root path')
                                    ->default('~/')
                                    ->prefix(fn (Get $get) => $get('scope_id') ? Filesystem::find($get('scope_id'))?->root_path : null)
                                    ->required()
                                    ->hint('The root path for this SSH connection.')
                            ])
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\IconColumn::make('isInitialized')
                    ->label('Initialized?')
                    ->alignCenter()
                    ->boolean(),
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->label('Service Type')
                    ->formatStateUsing(fn (string $state) : string => Filesystem::types()[$state] ?? $state)
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('sshCredential.name')
                    ->label('SSH Credential')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('protocol')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('host')
                    ->sortable(),
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
            'index' => Pages\ListFilesystem::route('/'),
            'create' => Pages\CreateFilesystem::route('/create'),
            'edit' => Pages\EditFilesystem::route('/{record}/edit'),
            'activities' => Pages\FilesystemActivities::route('/{record}/activities'),
        ];
    }
}
