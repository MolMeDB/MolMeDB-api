<?php
namespace App\Http\Resources\Search;

use App\Libraries\CdkDepict;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'title' => $this->nameIdentifier()->first()?->value ?? $this->identifier,
            'subtitle' => $this->identifier,
            'description' => null,
            'link' => "/mol/$this->identifier",
            'imageUrl' => $cdk->get2dStructureUrl($this->canonical_smiles, 1)
        );
    }
}
