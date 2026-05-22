<?php

namespace App\Filament\RichContentCustomBlocks;

class WarningInfoboxBlock extends InfoboxCustomBlock
{
    public static function getId(): string
    {
        return 'infobox_warning';
    }

    protected static function getVariantKey(): string
    {
        return 'warning';
    }

    protected static function getVariantLabel(): string
    {
        return 'Warning';
    }
}
