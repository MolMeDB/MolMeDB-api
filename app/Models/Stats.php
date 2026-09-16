<?php

namespace App\Models;

use App\DTO\Stats\Counts;
use App\DTO\Stats\LineChart;
use App\DTO\Stats\BarChart;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Counts $content_counts
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
    const TYPE_PUBLICATIONS_BY_YEAR_STATS = 5;
    const TYPE_PUBLICATIONS_BY_JOURNAL_STATS = 6;

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

    public static function getCountStats() : Counts
    {
        $d = self::getByType(self::TYPE_COUNTS);
        return Counts::from($d->content);
    }

    public static function setCountStats(Counts $counts) : void
    {
        $d = self::getByType(self::TYPE_COUNTS);
        $d->content = $counts->toArray();
        $d->save();
    }

    public static function getInteractionSubstanceHistory() : LineChart
    {
        $d = self::getByType(self::TYPE_INTERACTION_SUBSTANCE_HISTORY);
        return LineChart::from($d->content);
    }

    public static function setInteractionSubstanceHistory(LineChart $data) : void
    {
        $d = self::getByType(self::TYPE_INTERACTION_SUBSTANCE_HISTORY);
        $d->content = $data->toArray();
        $d->save();
    }

    public static function getDatabasesBarData() : BarChart
    {
        $d = self::getByType(self::TYPE_DATABASES_BAR_COUNTS);
        return BarChart::from($d->content);
    }

    public static function setDatabasesBarData(BarChart $data) : void
    {
        $d = self::getByType(self::TYPE_DATABASES_BAR_COUNTS);
        $d->content = $data->toArray();
        $d->save();
    }

    public static function getProteinBarData() : BarChart
    {
        $d = self::getByType(self::TYPE_PROTEIN_BAR_COUNTS);
        return BarChart::from($d->content);
    }

    public static function setProteinBarData(BarChart $data) : void
    {
        $d = self::getByType(self::TYPE_PROTEIN_BAR_COUNTS);
        $d->content = $data->toArray();
        $d->save();
    }

    public static function getPublicationByYearStatsData() : LineChart
    {
        $d = self::getByType(self::TYPE_PUBLICATIONS_BY_YEAR_STATS);
        return LineChart::from($d->content);
    }

    public static function setPublicationByYearStatsData(LineChart $data) : void
    {
        $d = self::getByType(self::TYPE_PUBLICATIONS_BY_YEAR_STATS);
        $d->content = $data->toArray();
        $d->save();
    }

    public static function getPublicationByJournalStatsData() : BarChart
    {
        $d = self::getByType(self::TYPE_PUBLICATIONS_BY_JOURNAL_STATS);
        return BarChart::from($d->content);
    }

    public static function setPublicationByJournalStatsData(BarChart $data) : void
    {
        $d = self::getByType(self::TYPE_PUBLICATIONS_BY_JOURNAL_STATS);
        $d->content = $data->toArray();
        $d->save();
    }
}
