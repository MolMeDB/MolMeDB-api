<?php

namespace App\Filament\RichContentCustomBlocks;

class SuccessInfoboxBlock extends InfoboxCustomBlock
{
    public static function getId(): string
    {
        return 'infobox_success';
    }

    protected static function getVariantKey(): string
    {
        return 'success';
    }

    protected static function getVariantLabel(): string
    {
        return 'Success';
    }
}
