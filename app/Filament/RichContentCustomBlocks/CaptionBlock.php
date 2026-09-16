<?php

namespace App\Filament\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class CaptionBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'caption';
    }

    public static function getLabel(): string
    {
        return 'Caption';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Add a caption below an image or table.')
            ->schema([
                Select::make('type')
                    ->required()
                    ->default('figure')
                    ->options([
                        'figure' => 'Figure',
                        'table' => 'Table',
                    ]),
                TextInput::make('number')
                    ->label('Number')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
                RichEditor::make('caption')
                    ->required()
                    ->label('Caption text')
                    ->toolbarButtons([
                        ['bold', 'underline', 'link'],
                        ['undo', 'redo'],
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        return static::render($config, isPreview: true);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return static::render($config, isPreview: false);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function render(array $config, bool $isPreview): string
    {
        $type = (string) ($config['type'] ?? 'figure');
        $type = in_array($type, ['figure', 'table'], true) ? $type : 'figure';
        $label = $type === 'table' ? 'Table' : 'Figure';
        $number = trim((string) ($config['number'] ?? ''));
        $label = trim("{$label} {$number}");
        $caption = trim((string) ($config['caption'] ?? ''));
        $captionHtml = static::renderCaption($caption);
        $previewStyle = $isPreview
            ? ' style="color: #71717a; font-size: 0.875rem; font-style: italic; line-height: 1.625; margin: 0.5rem 0 1.5rem;"'
            : '';

        return sprintf(
            '<p class="docs-caption docs-caption--%s"%s><span class="docs-caption__label">%s:</span> <span class="docs-caption__content">%s</span></p>',
            e($type),
            $previewStyle,
            e($label),
            $captionHtml,
        );
    }

    protected static function renderCaption(string $caption): string
    {
        if ($caption === '') {
            return '';
        }

        if (! str_contains($caption, '<')) {
            return e($caption);
        }

        return static::toInlineHtml(RichContentRenderer::make($caption)->toHtml());
    }

    protected static function toInlineHtml(string $html): string
    {
        $html = trim($html);
        $html = (string) preg_replace('/<\/p>\s*<p[^>]*>/i', ' ', $html);
        $html = (string) preg_replace('/<\/?p[^>]*>/i', '', $html);

        return trim($html);
    }
}
