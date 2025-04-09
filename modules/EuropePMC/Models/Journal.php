<?php 
namespace Modules\EuropePMC\Models;

class Journal {
    public function __construct(
        /** Name of journal which the article belongs to */
        public ?string $title,
        /** Issue of journal */
        public ?string $issue,
        /** Volume of journal */
        public ?string $volume,
        /** Date of publication */
        public ?string $dateOfPublication,
        /** Month of publication */
        public ?string $monthOfPublication,
        /** Year of publication */
        public ?string $yearOfPublication,
        /** ISSN */
        public ?string $issn,
        /** ESSN */
        public ?string $essn
    )
    {}
}