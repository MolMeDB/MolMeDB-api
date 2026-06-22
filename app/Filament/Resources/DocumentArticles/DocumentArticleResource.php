<?php

namespace App\Filament\Resources\DocumentArticles;

use App\Enums\IconEnums;
use App\Filament\Resources\DocumentArticles\Pages\CreateDocumentArticle;
use App\Filament\Resources\DocumentArticles\Pages\EditDocumentArticle;
use App\Filament\Resources\DocumentArticles\Pages\ListDocumentArticles;
use App\Filament\RichContentCustomBlocks\CaptionBlock;
use App\Filament\RichContentCustomBlocks\CodeSnippetBlock;
use App\Filament\RichContentCustomBlocks\ErrorInfoboxBlock;
use App\Filament\RichContentCustomBlocks\InfoInfoboxBlock;
use App\Filament\RichContentCustomBlocks\SuccessInfoboxBlock;
use App\Filament\RichContentCustomBlocks\WarningInfoboxBlock;
use App\Models\DocumentArticle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class DocumentArticleResource extends Resource
{
    protected static ?string $model = DocumentArticle::class;

    protected static string|\BackedEnum|null $navigationIcon = IconEnums::FILE_DOCUMENT->value;

    protected static ?string $navigationLabel = 'Documentation';

    protected static ?int $navigationSort = 30;

    protected static ?string $label = 'Articles';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent article')
                    ->options(fn (?DocumentArticle $record) => DocumentArticle::query()
                        ->whereNull('parent_id')
                        ->when($record?->id, fn (Builder $query) => $query->where('id', '!=', $record->id))
                        ->orderBy('position')
                        ->orderBy('title')
                        ->pluck('title', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Only one nested level is supported.')
                    ->validationAttribute('parent article')
                    ->afterStateHydrated(function (?DocumentArticle $record, $state, $component): void {
                        if ($state && $record?->parent?->parent_id !== null) {
                            $component->state(null);
                        }
                    }),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set, $get): void {
                        if (blank($get('slug')) && is_string($state)) {
                            $set('slug', str($state)->slug()->toString());
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->rule('alpha_dash')
                    ->unique(
                        ignorable: fn (?DocumentArticle $record) => $record,
                        modifyRuleUsing: fn (Unique $rule, $get) => $rule->where(
                            'parent_id',
                            $get('parent_id') ?: null,
                        ),
                    )
                    ->helperText('Slug is unique within the same parent level.'),
                TextInput::make('position')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_published')
                    ->default(true)
                    ->required(),
                RichEditor::make('content')
                    ->columnSpanFull()
                    ->required()
                    ->customBlocks([
                        'Infoboxes' => [
                            InfoInfoboxBlock::class,
                            WarningInfoboxBlock::class,
                            ErrorInfoboxBlock::class,
                            SuccessInfoboxBlock::class,
                        ],
                        'Source code' => [
                            CodeSnippetBlock::class,
                        ],
                        'Media' => [
                            CaptionBlock::class,
                        ],
                    ])
                    ->activePanel('customBlocks')
                    ->resizableImages()
                    ->fileAttachmentsDirectory('documentation/attachments')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsVisibility('public')
                    ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg'])
                    ->fileAttachmentsMaxSize(5120), // 5 MB,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.title')
                    ->label('Parent')
                    ->default('Top level')
                    ->badge()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('position')
                    ->sortable()
                    ->alignCenter(),
                IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),
                TextColumn::make('updated_at')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Published'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('parent');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentArticles::route('/'),
            'create' => CreateDocumentArticle::route('/create'),
            'edit' => EditDocumentArticle::route('/{record}/edit'),
        ];
    }
}
