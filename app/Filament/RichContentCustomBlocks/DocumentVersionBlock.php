<?php

namespace App\Filament\RichContentCustomBlocks;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Throwable;

class DocumentVersionBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'document_version';
    }

    public static function getLabel(): string
    {
        return 'Document version';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure article publication metadata.')
            ->schema([
                DatePicker::make('published_at')
                    ->label('Published')
                    ->maxDate(today()->toDateString())
                    ->helperText('Leave empty to use the article creation date.'),
                DatePicker::make('updated_at')
                    ->label('Last update')
                    ->minDate(fn ($get): mixed => $get('published_at') ?: null)
                    ->maxDate(today()->toDateString())
                    ->helperText('Leave empty to use the article last update date.'),
                TextInput::make('author_name')
                    ->label('Author')
                    ->required()
                    ->maxLength(255),
                TextInput::make('author_url')
                    ->label('Author URL')
                    ->url()
                    ->nullable()
                    ->maxLength(255),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        return static::render($config, []);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return static::render($config, $data);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    protected static function render(array $config, array $data): string
    {
        $publishedAt = static::formatDate((string) (($config['published_at'] ?? '') ?: ($data['published_at'] ?? '')));
        $updatedAt = static::formatDate((string) (($config['updated_at'] ?? '') ?: ($data['updated_at'] ?? '')));
        $authorName = trim((string) ($config['author_name'] ?? ''));
        $authorUrl = trim((string) ($config['author_url'] ?? ''));
        $authorHtml = static::renderAuthor($authorName, $authorUrl);
        $blockStyle = 'font-size: 0.875em; line-height: 1.55; opacity: 0.72; margin: 0 0 1.5rem;';
        $rowStyle = 'margin: 0; padding: 0;';
        $labelStyle = 'display: inline; font-weight: 600;';
        $valueStyle = 'display: inline;';

        return <<<HTML
<aside class="docs-version-meta" style="{$blockStyle}">
    <div class="docs-version-meta__row" style="{$rowStyle}"><span class="docs-version-meta__label" style="{$labelStyle}">Published:</span> <span class="docs-version-meta__value" style="{$valueStyle}">{$publishedAt}</span></div>
    <div class="docs-version-meta__row" style="{$rowStyle}"><span class="docs-version-meta__label" style="{$labelStyle}">Last update:</span> <span class="docs-version-meta__value" style="{$valueStyle}">{$updatedAt}</span></div>
    <div class="docs-version-meta__row" style="{$rowStyle}"><span class="docs-version-meta__label" style="{$labelStyle}">Author:</span> <span class="docs-version-meta__value" style="{$valueStyle}">{$authorHtml}</span></div>
</aside>
HTML;
    }

    protected static function formatDate(string $date): string
    {
        if ($date === '') {
            return '';
        }

        try {
            return Carbon::parse($date)->format('F j, Y');
        } catch (Throwable) {
            return e($date);
        }
    }

    protected static function renderAuthor(string $authorName, string $authorUrl): string
    {
        if ($authorName === '') {
            return '';
        }

        if ($authorUrl === '') {
            return e($authorName);
        }

        return sprintf(
            '<a href="%s" class="docs-version-meta__author-link" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline; text-underline-offset: 0.12em;">%s</a>',
            e($authorUrl),
            e($authorName),
        );
    }
}
