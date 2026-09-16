<?php

namespace App\Http\Resources\Api\Public\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

/**
 * Category tree for the public API, restricted to the fields safe for
 * public consumption (no internal `content`/`order`).
 */
class CategoryTreeCollection extends ResourceCollection
{
    private function __construct($resource, private readonly string $relation, private readonly \Closure $itemMapper)
    {
        parent::__construct($resource);
    }

    public static function forMembranes($resource): self
    {
        return new self($resource, 'membranes', fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'abbreviation' => $item->abbreviation,
        ]);
    }

    public static function forMethods($resource): self
    {
        return new self($resource, 'methods', fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'abbreviation' => $item->abbreviation,
        ]);
    }

    public static function forProteins($resource): self
    {
        return new self($resource, 'proteins', fn ($item) => [
            'id' => $item->id,
            'uniprot_id' => $item->uniprot_id,
        ]);
    }

    public function toArray(Request $request): array
    {
        $categories = $this->collection->groupBy('parent_id');

        return $categories->get(-1, collect())
            ->map(fn ($category) => $this->formatCategory($category, $categories))
            ->values()
            ->all();
    }

    private function formatCategory($category, Collection $categories): array
    {
        return [
            'id' => $category->id,
            'title' => $category->title,
            'children' => $categories->get($category->id, collect())
                ->map(fn ($child) => $this->formatCategory($child, $categories))
                ->values()
                ->all(),
            'items' => $category->{$this->relation}->map($this->itemMapper)->values()->all(),
        ];
    }
}
