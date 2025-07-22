
<div>
    <div class="flex flex-col gap-4">
        <x-filament::section
            heading="0) Basic information"
            {{-- description="Test" --}}
        >
            <div> <label class="font-bold">Filename:</label> {{ $record->file->name }} </div>
            <div> <label class="font-bold">Dataset:</label> {{ $record->dataset?->name }} </div>
            @if($record->dataset?->type == \App\Models\Dataset::TYPE_PASSIVE)
            <div> <label class="font-bold">Method:</label> {{ $record->dataset?->method?->name }} </div>
            <div> <label class="font-bold">Membrane:</label> {{ $record->dataset?->membrane?->name }} </div>
            @endif
        </x-filament::section>
        <x-filament::section
            heading="1) Import settings"
            description="Changes are immediately applied"
        >
            <div class="flex flex-row gap-2">
                <div class="w-1/2 flex flex-col gap-1 text-sm"> <label class="">Separator</label> 
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="separator" id="separator" name="separator">
                            @foreach ($this->validSeparators as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div class="w-1/2 flex flex-col gap-1 text-sm"> <label class="">Skip the first row</label> 
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="skipFirstRow">
                            @foreach ([1 => 'Yes', 0 => 'No'] as $key => $option)
                                <option value="{{ $key }}">{{ $option }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>
        <x-filament::section
            heading="2) Column mapping"
            description="Only previewed rows are shown. Total rows in file: {{ $totalRows }}"
        >
            <div class="flex flex-col gap-2 overflow-x-auto">
                <div>
                    <table class="table-fixed border-collapse border border-gray-400">
                        <thead>
                            <th class="max-w-16">
                                Line
                            </th>
                            @foreach ($columnMapping as $key => $value)
                                <th class="max-w-32 ">
                                    <x-filament::input.wrapper>
                                        <x-filament::input.select wire:model.live="columnMapping.{{ $key }}"> 
                                            @foreach ($columnValidTypes[$key] as $k => $label)
                                                <option value="{{ $k }}" {{ $k == $value ? 'selected' : '' }} >{{ $label }}</option>
                                            @endforeach
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper> 
                                </th>
                            @endforeach
                        </thead>
                        <tbody>
                            @foreach ($previewRows as $index => $row)
                                <tr>
                                    <td class="p-2 border border-gray-300 max-w-16 min-w-16">
                                        {{ $index + $startLine }}
                                    </td>
                                    @foreach ($row as $key => $value)
                                        <td class="p-2 border border-gray-300 max-w-32 min-w-32"><div class="text-wrap line-clamp-1 ">{{ $value }}</div></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::section>
        <div class="flex flex-col gap-1">
            @foreach ($errorMessages as $error)
                <div class="p-4 bg-red-100 text-red-600">
                    <span class="ml-4 text-gray-500 font-bold float-right text-[22px] leading-[20px] cursor-pointer transition duration-300 hover:text-black" onclick="this.parentElement.style.display='none';">&times;</span>
                    {{ $error }}
                </div>
            @endforeach
            @foreach ($warningMessages as $warning)
                <div class="p-4 bg-orange-100 text-orange-600">
                    <span class="ml-4 text-gray-500 font-bold float-right text-[22px] leading-[20px] cursor-pointer transition duration-300 hover:text-black" onclick="this.parentElement.style.display='none';">&times;</span>
                    {{ $warning }}
                </div>
            @endforeach
        </div>
        <div class="max-w-64 gap-2 flex flex-row justify-start items-end">
            <x-filament::button 
                type="button" 
                :disabled="$isValidated" 
                wire:click="validateColumns" 
                wire:target="validateColumns"
                wire:loading.attr="disabled"
                color="warning">
                    <span>Validate</span>
                    <span wire:loading wire:target="validateColumns">
                        <x-filament::loading-indicator size="sm" />
                    </span>
            </x-filament::button>
            <x-filament::button type="button" :disabled="!$isValidated" wire:click="save">Save</x-filament::button>
        </div>
    </div>
</div>
