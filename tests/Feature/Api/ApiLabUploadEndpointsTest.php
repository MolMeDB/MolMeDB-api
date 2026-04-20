<?php

require_once __DIR__.'/api_test_helpers.php';

use App\Enums\PermissionEnums;
use App\Models\Dataset;
use App\Models\File;
use App\Models\Permission;
use App\Models\Publication;
use App\Models\UploadQueue;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    prepareApiEndpointTestEnvironment();
});

afterEach(function () {
    resetApiRouteCdkDepictState();
    resetApiRouteRdkitState();
});

test('lab upload selects endpoint returns filtered membranes methods and publications', function () {
    createApiMembrane([
        'name' => 'Dipalmitoyl phosphatidylcholine',
        'abbreviation' => 'DPPC',
    ]);
    createApiMethod([
        'name' => 'Parallel artificial membrane permeation assay',
        'abbreviation' => 'PAMPA',
    ]);
    createApiPublication([
        'citation' => 'Doe et al. Lipid transport.',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(apiRoutePath('api/lab/upload/membranes').'?query=dppc')
        ->assertOk()
        ->assertJsonFragment(['abbreviation' => 'DPPC']);

    $this->actingAs($user)
        ->getJson(apiRoutePath('api/lab/upload/methods').'?query=pampa')
        ->assertOk()
        ->assertJsonFragment(['abbreviation' => 'PAMPA']);

    $this->actingAs($user)
        ->getJson(apiRoutePath('api/lab/upload/publications').'?query=lipid')
        ->assertOk()
        ->assertJsonFragment(['citation' => 'Doe et al. Lipid transport.']);
});

test('lab upload publication lookup endpoint returns europe pmc PMID records', function () {
    putenv('EUROPE_PMC_ENDPOINT=https://europepmc.test');
    $_ENV['EUROPE_PMC_ENDPOINT'] = 'https://europepmc.test';

    Http::fake([
        'https://europepmc.test/search*' => Http::response([
            'hitCount' => 1,
            'resultList' => [
                'result' => [[
                    'id' => '4001',
                    'source' => 'MED',
                    'title' => 'EuropePMC membrane paper',
                    'doi' => '10.1000/eupmc',
                    'authorString' => 'Alpha B',
                    'journalInfo' => [
                        'journal' => ['title' => 'Membrane Journal'],
                        'yearOfPublication' => 2024,
                    ],
                ]],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(apiRoutePath('api/lab/upload/publications/lookup').'?query=membrane')
        ->assertOk()
        ->assertJsonFragment(['provider' => 'europe_pmc'])
        ->assertJsonFragment(['pmid' => '4001']);
});

test('lab upload endpoint stores dataset and upload queue and can create publication from pmid lookup', function () {
    putenv('EUROPE_PMC_ENDPOINT=https://europepmc.test');
    $_ENV['EUROPE_PMC_ENDPOINT'] = 'https://europepmc.test';

    Http::fake([
        'https://europepmc.test/article/MED/4001*' => Http::response([
            'result' => [
                'id' => '4001',
                'source' => 'MED',
                'title' => 'EuropePMC upload paper',
                'doi' => '10.1093/database/baz078',
                'authorString' => 'Alpha B',
                'journalInfo' => [
                    'journal' => ['title' => 'Europe Journal'],
                    'yearOfPublication' => 2024,
                ],
            ],
        ], 200),
    ]);

    config()->set('services.turnstile.enabled', false);
    Storage::fake('public');

    $user = User::factory()->create();
    $membrane = createApiMembrane();
    $method = createApiMethod();

    $response = $this->actingAs($user)
        ->post(apiRoutePath('api/lab/upload'), [
            'dataset_type' => Dataset::TYPE_PASSIVE,
            'method_id' => $method->id,
            'membrane_id' => $membrane->id,
            'publication_pmid' => '4001',
            'publication_lookup_provider' => 'europe_pmc',
            'publication_lookup_source' => 'MED',
            'turnstile_token' => 'test-token',
            'file' => UploadedFile::fake()->create('interactions.csv', 100, 'text/csv'),
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.publication_id', Publication::query()->first()->id);

    $record = UploadQueue::query()->first();
    expect($record->logs)->toHaveCount(1)
        ->and($record->logs->first()->type->value)->toBe('UPLOAD')
        ->and($record->logs->first()->state)->toBe(UploadQueue::STATE_UPLOADED);

    expect(Dataset::query()->count())->toBe(1)
        ->and(File::query()->count())->toBe(1)
        ->and(UploadQueue::query()->count())->toBe(1)
        ->and(Publication::query()->where('identifier', '4001')->where('identifier_source', 'MED')->exists())->toBeTrue();
});

test('lab upload my-uploads endpoint returns only authenticated user records', function () {
    config()->set('services.turnstile.enabled', false);
    Storage::fake('public');

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $membrane = createApiMembrane();
    $method = createApiMethod();
    $publication = createApiPublication();

    $datasetA = createApiDataset([
        'owner' => $userA,
        'membrane' => $membrane,
        'method' => $method,
        'publication' => $publication,
    ]);
    $datasetB = createApiDataset([
        'owner' => $userB,
        'membrane' => $membrane,
        'method' => $method,
        'publication' => $publication,
    ]);

    $fileA = File::query()->create([
        'path' => 'upload_queue/passive/a.csv',
        'name' => 'a.csv',
        'type' => File::TYPE_UPLOAD_PASSIVE,
        'storage' => 'public',
        'mime' => 'text/csv',
        'hash' => md5('a'),
    ]);
    $fileB = File::query()->create([
        'path' => 'upload_queue/passive/b.csv',
        'name' => 'b.csv',
        'type' => File::TYPE_UPLOAD_PASSIVE,
        'storage' => 'public',
        'mime' => 'text/csv',
        'hash' => md5('b'),
    ]);

    UploadQueue::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'state' => UploadQueue::STATE_PENDING,
        'file_id' => $fileA->id,
        'dataset_id' => $datasetA->id,
        'user_id' => $userA->id,
        'config' => [],
    ]);
    UploadQueue::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'state' => UploadQueue::STATE_ERROR,
        'file_id' => $fileB->id,
        'dataset_id' => $datasetB->id,
        'user_id' => $userB->id,
        'config' => [],
    ]);

    $this->actingAs($userA)
        ->getJson(apiRoutePath('api/lab/upload/my-uploads'))
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.data.0.state_label', 'Waiting in queue')
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    [
                        'logs' => [
                            '*' => ['message', 'context', 'type', 'state', 'timestamp'],
                        ],
                    ],
                ],
            ],
        ]);
});

test('lab upload reupload endpoint accepts replacement only for own error record', function () {
    config()->set('services.turnstile.enabled', false);
    Storage::fake('public');

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $dataset = createApiDataset([
        'owner' => $user,
    ]);

    $oldFile = File::query()->create([
        'path' => 'upload_queue/passive/old.csv',
        'name' => 'old.csv',
        'type' => File::TYPE_UPLOAD_PASSIVE,
        'storage' => 'public',
        'mime' => 'text/csv',
        'hash' => md5('old'),
    ]);

    $record = UploadQueue::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'state' => UploadQueue::STATE_ERROR,
        'file_id' => $oldFile->id,
        'dataset_id' => $dataset->id,
        'user_id' => $user->id,
        'config' => [
            'validated_rows' => 15,
        ],
    ]);

    $this->actingAs($otherUser)
        ->post(apiRoutePath("api/lab/upload/{$record->id}/reupload"), [
            'file' => UploadedFile::fake()->create('other.csv', 40, 'text/csv'),
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->post(apiRoutePath("api/lab/upload/{$record->id}/reupload"), [
            'file' => UploadedFile::fake()->create('fixed.csv', 40, 'text/csv'),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', UploadQueue::STATE_UPLOADED);

    $record->refresh();
    expect($record->state)->toBe(UploadQueue::STATE_UPLOADED)
        ->and($record->file_id)->not->toBe($oldFile->id);
});

test('lab upload configure preview and validate endpoints work for uploaded record', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $dataset = createApiDataset(['owner' => $user, 'type' => Dataset::TYPE_PASSIVE]);

    Storage::disk('public')->put('upload_queue/passive/config.csv', "SMILES,Gpen\nCCO,1.25\n");

    $file = File::query()->create([
        'path' => 'upload_queue/passive/config.csv',
        'name' => 'config.csv',
        'type' => File::TYPE_UPLOAD_PASSIVE,
        'storage' => 'public',
        'mime' => 'text/csv',
        'hash' => md5('config'),
    ]);

    $record = UploadQueue::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'state' => UploadQueue::STATE_UPLOADED,
        'file_id' => $file->id,
        'dataset_id' => $dataset->id,
        'user_id' => $user->id,
        'config' => [],
    ]);

    $this->actingAs($user)
        ->getJson(apiRoutePath("api/lab/upload/{$record->id}/configure/preview").'?separator=,&skip_first_row=1')
        ->assertOk()
        ->assertJsonPath('data.ok', true)
        ->assertJsonPath('data.preview_rows.0.0', 'CCO');

    $this->actingAs($user)
        ->postJson(apiRoutePath("api/lab/upload/{$record->id}/configure/validate"), [
            'separator' => ',',
            'skip_first_row' => 1,
            'attributes' => ['smiles', 'gpen'],
        ])
        ->assertOk()
        ->assertJsonPath('data.config.quick_validation_ok', true);
});

test('lab upload enqueue endpoint moves configured record to pending state', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $dataset = createApiDataset(['owner' => $user, 'type' => Dataset::TYPE_PASSIVE]);

    $file = File::query()->create([
        'path' => 'upload_queue/passive/queued.csv',
        'name' => 'queued.csv',
        'type' => File::TYPE_UPLOAD_PASSIVE,
        'storage' => 'public',
        'mime' => 'text/csv',
        'hash' => md5('queued'),
    ]);

    $record = UploadQueue::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'state' => UploadQueue::STATE_CONFIGURED,
        'file_id' => $file->id,
        'dataset_id' => $dataset->id,
        'user_id' => $user->id,
        'config' => [
            'separator' => ',',
            'skip_first_row' => 1,
            'attributes' => ['smiles', 'gpen'],
            'quick_validation_ok' => true,
        ],
    ]);

    $this->actingAs($user)
        ->postJson(apiRoutePath("api/lab/upload/{$record->id}/enqueue"))
        ->assertOk()
        ->assertJsonPath('data.state', UploadQueue::STATE_PENDING);

    $record->refresh();
    expect($record->state)->toBe(UploadQueue::STATE_PENDING);
});

test('lab upload cancel endpoint removes uploaded file and marks own record as canceled', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $dataset = createApiDataset(['owner' => $user, 'type' => Dataset::TYPE_PASSIVE]);
    Storage::disk('public')->put('upload_queue/passive/cancel.csv', "SMILES,Gpen\nCCO,1.23\n");

    $file = File::query()->create([
        'path' => 'upload_queue/passive/cancel.csv',
        'name' => 'cancel.csv',
        'type' => File::TYPE_UPLOAD_PASSIVE,
        'storage' => 'public',
        'mime' => 'text/csv',
        'hash' => md5('cancel'),
    ]);

    $record = UploadQueue::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'state' => UploadQueue::STATE_UPLOADED,
        'file_id' => $file->id,
        'dataset_id' => $dataset->id,
        'user_id' => $user->id,
        'config' => [],
    ]);

    $this->actingAs($otherUser)
        ->postJson(apiRoutePath("api/lab/upload/{$record->id}/cancel"))
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(apiRoutePath("api/lab/upload/{$record->id}/cancel"))
        ->assertOk()
        ->assertJsonPath('data.state', UploadQueue::STATE_CANCELED);

    $record->refresh();
    expect($record->state)->toBe(UploadQueue::STATE_CANCELED)
        ->and(Storage::disk('public')->exists('upload_queue/passive/cancel.csv'))->toBeFalse()
        ->and($record->config['uploaded_file_deleted'] ?? false)->toBeTrue()
        ->and($record->logs->last()->message)->toBe('Record was canceled by user and uploaded file was deleted.');
});

test('lab upload cancel endpoint allows foreign record for user with upload_queue.manage.all permission', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $dataset = createApiDataset(['owner' => $owner, 'type' => Dataset::TYPE_PASSIVE]);

    $permission = Permission::query()->firstOrCreate(
        [
            'name' => PermissionEnums::UPLOAD_QUEUE_MANAGE_ALL->value,
            'guard_name' => 'web',
        ],
        [
            'description' => PermissionEnums::UPLOAD_QUEUE_MANAGE_ALL->description(),
        ],
    );
    $manager->givePermissionTo($permission);

    Storage::disk('public')->put('upload_queue/passive/cancel-by-manager.csv', "SMILES,Gpen\nCCO,1.23\n");

    $file = File::query()->create([
        'path' => 'upload_queue/passive/cancel-by-manager.csv',
        'name' => 'cancel-by-manager.csv',
        'type' => File::TYPE_UPLOAD_PASSIVE,
        'storage' => 'public',
        'mime' => 'text/csv',
        'hash' => md5('cancel-by-manager'),
    ]);

    $record = UploadQueue::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'state' => UploadQueue::STATE_UPLOADED,
        'file_id' => $file->id,
        'dataset_id' => $dataset->id,
        'user_id' => $owner->id,
        'config' => [],
    ]);

    $this->actingAs($manager)
        ->postJson(apiRoutePath("api/lab/upload/{$record->id}/cancel"))
        ->assertOk()
        ->assertJsonPath('data.state', UploadQueue::STATE_CANCELED);

    $record->refresh();
    expect($record->state)->toBe(UploadQueue::STATE_CANCELED)
        ->and(Storage::disk('public')->exists('upload_queue/passive/cancel-by-manager.csv'))->toBeFalse();
});

test('lab upload revert endpoint moves pending record back to configured state', function () {
    $user = User::factory()->create();
    $dataset = createApiDataset(['owner' => $user, 'type' => Dataset::TYPE_PASSIVE]);

    $file = File::query()->create([
        'path' => 'upload_queue/passive/revert.csv',
        'name' => 'revert.csv',
        'type' => File::TYPE_UPLOAD_PASSIVE,
        'storage' => 'public',
        'mime' => 'text/csv',
        'hash' => md5('revert'),
    ]);

    $record = UploadQueue::query()->create([
        'type' => Dataset::TYPE_PASSIVE,
        'state' => UploadQueue::STATE_PENDING,
        'file_id' => $file->id,
        'dataset_id' => $dataset->id,
        'user_id' => $user->id,
        'config' => [
            'quick_validation_ok' => true,
            'detailed_validation_ok' => false,
        ],
    ]);

    $this->actingAs($user)
        ->postJson(apiRoutePath("api/lab/upload/{$record->id}/revert"))
        ->assertOk()
        ->assertJsonPath('data.state', UploadQueue::STATE_CONFIGURED);

    $record->refresh();
    expect($record->state)->toBe(UploadQueue::STATE_CONFIGURED);
});
