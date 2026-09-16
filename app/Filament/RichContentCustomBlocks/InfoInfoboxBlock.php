<?php

namespace App\Filament\RichContentCustomBlocks;

class InfoInfoboxBlock extends InfoboxCustomBlock
{
    public static function getId(): string
    {
        return 'infobox_info';
    }

    protected static function getVariantKey(): string
    {
        return 'info';
    }

    protected static function getVariantLabel(): string
    {
        return 'Info';
    }
}
