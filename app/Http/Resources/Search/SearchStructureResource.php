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
        $cdk = new CdkDepict();

        return array 
        (
            'title' => $this->name ?? $this->identifier,
            'subtitle' => $this->identifier,
            'description' => null,
            'link' => "/mol/$this->identifier",
            'imageUrl' => $cdk->get2dStructureUrl($this->canonical_smiles, 1)
        );
    }
}
