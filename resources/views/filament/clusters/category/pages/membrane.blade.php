<div>
    <x-filament::card>
        <!-- Zobrazení formuláře -->
        @php
            $form = \Filament\Forms\Form::make('form')->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->disabled()
                    ->default($record->name),
            ]);
        @endphp

        {{ $form }}
    </x-filament::card>

    <!-- Zobrazení RelationManager -->
    <div>
        @livewire('filament.resources.relation-manager', [
            'owner' => $record,
        ])
    </div>
</div>