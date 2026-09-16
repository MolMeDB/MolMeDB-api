<?php

namespace App\Filament\RichContentCustomBlocks;

class ErrorInfoboxBlock extends InfoboxCustomBlock
{
    public static function getId(): string
    {
        return 'infobox_error';
    }

    protected static function getVariantKey(): string
    {
        return 'error';
    }

    protected static function getVariantLabel(): string
    {
        return 'Error';
    }
}
