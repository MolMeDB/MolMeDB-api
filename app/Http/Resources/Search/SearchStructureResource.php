<?php

namespace App\Http\Resources\Search;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CdkDepict\CdkDepict;

class SearchStructureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cdk = new CdkDepict;
        $isAvailable = filled($this->identifier);

        return [
            'title' => $this->matched_identifier ?? $this->identifier,
            'subtitle' => $this->identifier,
            'description' => null,
            'link' => $isAvailable ? "/mol/$this->identifier" : null,
            'imageUrl' => $cdk->get2dStructureUrl($this->canonical_smiles, 1),
            'isAvailable' => $isAvailable,
            'availabilityMessage' => $isAvailable ? null : 'This molecule record is being prepared.',
        ];
    }
}
