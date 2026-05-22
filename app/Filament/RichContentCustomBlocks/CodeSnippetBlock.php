<?php

namespace App\Filament\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class CodeSnippetBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'code_snippet';
    }

    public static function getLabel(): string
    {
        return 'Code snippet';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure source code block')
            ->schema([
                Select::make('language')
                    ->required()
                    ->default('sparql')
                    ->options([
                        'sparql' => 'SPARQL',
                        'sql' => 'SQL',
                        'json' => 'JSON',
                    ]),
                Textarea::make('code')
                    ->required()
                    ->rows(12)
                    ->label('Source code'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        return static::render($config);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return static::render($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function render(array $config): string
    {
        $language = strtolower((string) ($config['language'] ?? 'sparql'));
        $language = in_array($language, ['sparql', 'sql', 'json'], true) ? $language : 'sparql';

        $code = (string) ($config['code'] ?? '');

        return sprintf(
            '<pre class="docs-code-block language-%s" data-language="%s"><code>%s</code></pre>',
            e($language),
            e($language),
            e($code),
        );
    }
}
