<?php

use Modules\References\EuropePMC\Enums\Sources;
use Modules\References\Models\Author;
use Modules\References\Models\Journal;
use Modules\References\Models\Record;

test('record get value resolves nested array paths', function () {
    $data = [
        'journalInfo' => [
            'journal' => [
                'title' => 'Journal of Testing',
            ],
        ],
    ];

    expect(Record::getValue($data, 'journalInfo.journal.title'))->toBe('Journal of Testing')
        ->and(Record::getValue($data, 'journalInfo.journal.missing', 'fallback'))->toBe('fallback');
});

test('record citation uses author fallback string built from authors', function () {
    $record = new Record(
        id: '1',
        source: Sources::MED,
        pmid: null,
        pmcid: null,
        journal: new Journal(
            title: 'Journal of Testing',
            issue: '4',
            volume: '12',
            dateOfPublication: '2025-04-11',
            monthOfPublication: '04',
            yearOfPublication: '2025',
            issn: null,
            essn: null,
        ),
        title: 'Example title',
        authorString: null,
        authors: [
            new Author('Jane Doe', 'Jane', 'Doe', 'J', ['Lab A']),
            new Author('John Smith', 'John', 'Smith', 'J', ['Lab B']),
        ],
        doi: '10.1000/test',
        isOpenAccess: true,
        inEPMC: true,
        hasPDF: true,
        hasBook: false,
        hasSuppl: false,
        abstractText: null,
        affiliation: null,
        citedByCount: 3,
        hasReferences: true,
        keywords: ['membrane'],
        fullTextUrls: ['https://example.test/fulltext'],
        pageInfo: '10-15',
    );

    expect($record->getAuthorString())->toBe('Doe J., Smith J.')
        ->and($record->citation())->toBe('Doe J., Smith J.: Example title. Journal of Testing, Volume 12 (4), 10-15, 2025');
});
