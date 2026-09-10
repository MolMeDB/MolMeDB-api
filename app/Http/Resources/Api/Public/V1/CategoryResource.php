<?php

namespace App\Http\Resources\Api\Public\V1;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single category assigned to a membrane/method, including its full
 * root-to-leaf path so clients don't have to walk the tree themselves.
 */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'breadcrumb' => $this->breadcrumb(),
        ];
    }

    private function breadcrumb(): array
    {
        $chain = [];

        /** @var Category|null $node */
        $node = $this->resource;

        while ($node) {
            array_unshift($chain, ['id' => $node->id, 'title' => $node->title]);
            $node = $node->parent;
        }

        return $chain;
    }
}
