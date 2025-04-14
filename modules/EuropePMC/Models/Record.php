<?php 
namespace Modules\EuropePMC\Models;

use Modules\EuropePMC\Enums\Sources;

class Record {
    public function __construct(
        /** EuropePMC Identifier of the Article */
        public ?string $id,
        /** Source of article */
        public ?Sources $source,
        /** Pubmed Central identifier - only if full text is available in EuropePMC */
        public ?string $pmid,
        /** Title of article */
        public ?string $pmcid,
        /** Journal info */
        public ?Journal $journal,
        /** Pubmed identifier */
        public ?string $title,
        /** Comma separated list of authors  */
        public ?string $authorString,
        /** List of authors 
         * @var Author[]
        */
        public ?array $authors,
        /** Digital object identifier */
        public ?string $doi,
        /** Whether the article is open access */
        public ?bool $isOpenAccess,
        /** Whether the article is available as full-text in EuropePMC */
        public ?bool $inEPMC,
        /** Whether the publisher provided a PDF */
        public ?bool $hasPDF,
        /** Whether the full text book is available */
        public ?bool $hasBook,
        /** Whether the article has supplimentary material */
        public ?bool $hasSuppl,
        /** Abstract text */
        public ?string $abstractText,
        /** Affilitaion of the article */
        public ?string $affiliation,
        /** Number of times the article has been cited by articles in PMC */
        public ?int $citedByCount,
        /** Whether the article has references list - only for full-text articles */
        public ?bool $hasReferences,
        /** Keywords */
        public ?array $keywords,
        /** Full text URLs */
        public ?array $fullTextUrls,
        /** Page info */
        public ?string $pageInfo
    ){}

    public static function getValue($data, $key, $default = null)
    {
        if(!is_array($data)) return null;

        $keys = explode('.', $key);
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $data = $data[$key] ?? [];
            }
            else
            {
                return $default;
            }
        }
        return $data ?? $default;
    }

    public function citation() 
    {
        return "$this->authorString: $this->title " .
            $this->journal?->title . ', ' .
            ($this->journal?->volume ? 'Volume ' . $this->journal->volume . (
                $this->journal?->issue ? ' (' . $this->journal->issue . ')' : ''
            ) : '') . ', ' . 
            ($this->pageInfo ? $this->pageInfo . ', ' : '') . 
            $this->journal?->yearOfPublication;
    }

    public static function from(array $data) 
    {
        return new self(
            self::getValue($data, 'id'),
            Sources::tryFrom(self::getValue($data, 'source')),
            self::getValue($data, 'pmid'),
            self::getValue($data, 'pmcid'),
            new Journal(
                self::getValue($data, 'journalInfo.journal.title') ?? self::getValue($data, 'journalTitle'),
                self::getValue($data, 'journalInfo.issue') ?? self::getValue($data, 'issue'),
                self::getValue($data, 'journalInfo.volume') ?? self::getValue($data, 'journalVolume') ?? self::getValue($data, 'volume'),
                self::getValue($data, 'journalInfo.dateOfPublication') ?? self::getValue($data, 'firstPublicationDate'),
                self::getValue($data, 'journalInfo.monthOfPublication'),
                self::getValue($data, 'journalInfo.yearOfPublication') ?? self::getValue($data, 'pubYear'),
                self::getValue($data, 'journalInfo.jorunal.issn') ?? self::getValue($data, 'journalIssn') ?? self::getValue($data, 'issn'),
                self::getValue($data, 'journalInfo.journal.essn') ?? self::getValue($data, 'journalEssn') ?? self::getValue($data, 'issn')
            ),
            self::getValue($data, 'title'),
            self::getValue($data, 'authorString'),
            array_map(fn($author) => new Author(
                self::getValue($author,'fullName'),
                self::getValue($author,'firstName'),
                self::getValue($author,'lastName'),
                self::getValue($author,'initials'),
                array_map(fn($affiliation) => self::getValue($affiliation, 'affiliation'), self::getValue($author,'authorAffiliationDetailsList.authorAffiliation') ?? [])
            ), self::getValue($data, 'authorList.author') ?? []),
            self::getValue($data, 'doi'),
            self::getValue($data, 'isOpenAccess') ? self::getValue($data, 'isOpenAccess') == "Y" : null,
            self::getValue($data, 'inEPMC') ? self::getValue($data, 'inEPMC') == "Y" : null,
            self::getValue($data, 'hasPDF') ? self::getValue($data, 'hasPDF') == "Y" : null,
            self::getValue($data, 'hasBook') ? self::getValue($data, 'hasBook') == "Y" : null,
            self::getValue($data, 'hasSuppl') ? self::getValue($data, 'hasSuppl') == "Y" : null,
            self::getValue($data, 'abstractText'),
            self::getValue($data, 'affiliation'),
            self::getValue($data, 'citedByCount'),
            self::getValue($data, 'hasReferences') ? self::getValue($data, 'hasReferences') == "Y" : null,
            self::getValue($data, 'keywordList.keyword'),
            array_map(fn($fullTextUrl) => self::getValue($fullTextUrl, 'url'), self::getValue($data, 'fullTextUrlList.fullTextUrl') ?? []),
            self::getValue($data, 'pageInfo')
        );
    }
}