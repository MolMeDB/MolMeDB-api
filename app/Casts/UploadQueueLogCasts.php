<?php

namespace App\Casts;

use App\Enums\UploadQueueLogContextEnums;
use App\ValueObjects\UploadQueueLog;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Collection;

class UploadQueueLogCasts implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): Collection
    {
        $logs = (array)json_decode($value);

        if(!UploadQueueLog::hasValidListStructure($logs))
        {
            $logs = UploadQueueLog::remapOldLogs($logs);
        }

        return new Collection(collect(
            array_map(fn ($row) => new UploadQueueLog($row->message, $row->context, $row->timestamp, $row->user_id), $logs ?? [])
        ));
    }

    public function set($model, string $key, $value, array $attributes): string
    {
        // $value je pravděpodobně Collection|array UploadQueueLog instancí
        $array = collect($value)->map(function ($item) {
            if ($item instanceof UploadQueueLog) {
                return $item->jsonSerialize(); // nebo $item->toArray()
            }

            return (array) $item; // fallback
        });

        return json_encode($array);
    }
}
