<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $categories = $this->collection->groupBy('parent_id');

        return $categories->get(-1, collect())->mapWithKeys(function ($category) use ($categories) {
            return [$category->id => $this->formatCategory($category, $categories)];
        })->all();
    }

     /**
     * Format a single category with its children.
     *
     * @param mixed $category
     * @param \Illuminate\Support\Collection $categories
     * @return array
     */
    private function formatCategory($category, $categories): array
    {
        return [
            'id' => $category->id,
            'order' => $category->order,
            'title' => $category->title,
            'children' => $categories->get($category->id, collect())->map(function ($child) use ($categories) {
                return $this->formatCategory($child, $categories);
            })->all(),
            'membranes' => $category->whenLoaded('membranes', $category->membranes->map(function ($membrane) {
                return [
                    'id' => $membrane->id,
                    'name' => $membrane->abbreviation,
                ];
            })),
            'methods' => $category->whenLoaded('methods', $category->methods->map(function ($membrane) {
                return [
                    'id' => $membrane->id,
                    'name' => $membrane->abbreviation,
                ];
            }))
        ];
    }
}
