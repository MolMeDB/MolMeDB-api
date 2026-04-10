<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CdkDepict\CdkDepict;

class PredictionStructureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cdk = new CdkDepict();

        return [
            'id' => $this->id,
            'remote_id' => $this->remote_id,
            'remote_identifier' => $this->structure?->identifier,
            'canonical_smiles' => $this->canonical_smiles,
            'structure_2d_url' => $cdk->get2dStructureUrl($this->canonical_smiles),
            'structure_2d_url_big' => $cdk->get2dStructureUrl($this->canonical_smiles, 4),
            'total_conformers' => $this->total_conformers,
            'created_at' => $this->created_at->format('Y/m/d H:i:s'),
            'updated_at' => $this->updated_at->format('Y/m/d H:i:s'),
        ];
    }
}
