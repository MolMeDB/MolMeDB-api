<?php

namespace App\Filament\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

abstract class InfoboxCustomBlock extends RichContentCustomBlock
{
    abstract protected static function getVariantKey(): string;

    abstract protected static function getVariantLabel(): string;

    public static function getLabel(): string
    {
        return static::getVariantLabel().' infobox';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure infobox content')
            ->schema([
                RichEditor::make('body')
                    ->label('Content')
                    ->required()
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'link'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        return static::renderHtml($config, isPreview: true);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return static::renderHtml($config, isPreview: false);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function renderHtml(array $config, bool $isPreview): string
    {
        $contentHtml = static::renderContent((string) ($config['body'] ?? ''), $isPreview);

        $variant = static::getVariantKey();
        $previewStyle = $isPreview ? ' style="'.static::getPreviewInlineStyle($variant).'"' : '';

        return <<<HTML
<div class="docs-infobox docs-infobox--{$variant}"{$previewStyle}>
    <div class="docs-infobox__content">{$contentHtml}</div>
</div>
HTML;
    }

    protected static function renderContent(string $content, bool $isPreview): string
    {
        if ($content === '') {
            return '<p>Add infobox content.</p>';
        }

        if ($isPreview) {
            return RichContentRenderer::make($content)->toHtml();
        }

        return RichContentRenderer::make($content)->toHtml();
    }

    protected static function getPreviewInlineStyle(string $variant): string
    {
        return match ($variant) {
            'warning' => 'border-left: 5px solid #d97706; background: #fffbeb; padding: 12px 14px;',
            'error' => 'border-left: 5px solid #be123c; background: #fff1f2; padding: 12px 14px;',
            'success' => 'border-left: 5px solid #15803d; background: #f0fdf4; padding: 12px 14px;',
            default => 'border-left: 5px solid #2563eb; background: #eff6ff; padding: 12px 14px;',
        };
    }
}
