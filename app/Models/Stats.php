<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \App\DTO\Stats\Counts $content_counts
 */
class Stats extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => 'integer',
        'content' => 'array',
    ];

    const TYPE_COUNTS = 1;
    const TYPE_INTERACTION_SUBSTANCE_HISTORY = 2;
    const TYPE_DATABASES_BAR_COUNTS = 3;
    const TYPE_PROTEIN_BAR_COUNTS = 4;

    public static function getByType($type) : Stats
    {
        $obj = self::where('type', $type)->first();
        if(!$obj) {
            $obj = new self();
            $obj->type = $type;
            $obj->content = [];
            $obj->save();
        }
        return $obj;
    }

    public static function getCountStats() : \App\DTO\Stats\Counts
    {
        $d = self::getByType(self::TYPE_COUNTS);
        return \App\DTO\Stats\Counts::from($d->content);
    }

    public static function setCountStats(\App\DTO\Stats\Counts $counts) : void
    {
        $d = self::getByType(self::TYPE_COUNTS);
        $d->content = $counts->toArray();
        $d->save();
    }

    public static function getInteractionSubstanceHistory() : \App\DTO\Stats\LineChart
    {
        $d = self::getByType(self::TYPE_INTERACTION_SUBSTANCE_HISTORY);
        return \App\DTO\Stats\LineChart::from($d->content);
    }

    public static function setInteractionSubstanceHistory(\App\DTO\Stats\LineChart $data) : void
    {
        $d = self::getByType(self::TYPE_INTERACTION_SUBSTANCE_HISTORY);
        $d->content = $data->toArray();
        $d->save();
    }

    public static function getDatabasesBarData() : \App\DTO\Stats\BarChart
    {
        $d = self::getByType(self::TYPE_DATABASES_BAR_COUNTS);
        return \App\DTO\Stats\BarChart::from($d->content);
    }

    public static function setDatabasesBarData(\App\DTO\Stats\BarChart $data) : void
    {
        $d = self::getByType(self::TYPE_DATABASES_BAR_COUNTS);
        $d->content = $data->toArray();
        $d->save();
    }

    public static function getProteinBarData() : \App\DTO\Stats\BarChart
    {
        $d = self::getByType(self::TYPE_PROTEIN_BAR_COUNTS);
        return \App\DTO\Stats\BarChart::from($d->content);
    }

    public static function setProteinBarData(\App\DTO\Stats\BarChart $data) : void
    {
        $d = self::getByType(self::TYPE_PROTEIN_BAR_COUNTS);
        $d->content = $data->toArray();
        $d->save();
    }
}
