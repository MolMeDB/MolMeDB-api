<?php 
namespace Modules\References\Models;

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

    public function getFullName() {
        return $this->lastName . ' ' . ($this->lastName ? substr($this->firstName, 0, 1) . '.' : '');
    }
}