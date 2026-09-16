<?php

namespace App\Services\External\Protein;

class UniprotRecord
{
    public ?string $entryType;
    public ?string $id;
    public ?UniprotProteinDescription $proteinDescription;
    /** @var UniprotProteinComment[]*/
    public $comments = [];

    public static function fromApiResponse(array $data): self
    {
        $record = new self();
        $record->entryType = $data['entryType'] ?? null;
        $record->id = $data['primaryAccession'] ?? null;
        if(isset($data['proteinDescription'])) {
            $record->proteinDescription = UniprotProteinDescription::fromRawData($data['proteinDescription']);
        }
        if(isset($data['comments']) && is_array($data['comments'])) {
            foreach($data['comments'] as $commentData) {
                $record->comments[] = UniprotProteinComment::fromRawData($commentData);
            }
        }
        return $record;
    }

    public function isActive(): bool
    {
        return $this->entryType !== 'Inactive';
    }

}