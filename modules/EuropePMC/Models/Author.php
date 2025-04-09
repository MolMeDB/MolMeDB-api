<?php 
namespace Modules\EuropePMC\Models;

class Author {
    public function __construct(
        public ?string $fullName,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $initials,
        /** @var string[]|null */
        public ?array $affiliations,
    )
    {}
}