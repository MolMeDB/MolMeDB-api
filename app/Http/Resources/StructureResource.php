<?php

namespace App\Http\Resources;

use App\Helpers\MMdbUrl;
use App\Libraries\CdkDepict;
use App\Models\Structure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StructureResource extends JsonResource
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
            'name' => $this->nameIdentifier()->first()->value,
            'canonical_smiles' => $this->canonical_smiles,
            'charge' => $this->charge,
            'inchi' => $this->inchi,
            'inchikey' => $this->inchikey,
            'molecular_weight' => $this->molecular_weight,
            'logp' => $this->logp,
            'structure_3d_url' => MMdbUrl::structure3D(Structure::find($this->id)),
            'structure_2d_url' => $cdk->get2dStructureUrl($this->canonical_smiles),
            'structure_2d_url_big' => $cdk->get2dStructureUrl($this->canonical_smiles, 4),
            'identifier' => $this->identifier,
            'identifiers' => IdentifierResource::collection($this->identifiers),
        ];
    }
}
