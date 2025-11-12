<?php

use App\Models\Filesystem;
use App\Models\SshCredential;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ssh_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // $table->string('host');
            // $table->unsignedSmallInteger('port')->default(22);
            $table->string('username');
            $table->enum('type', [SshCredential::AUTH_TYPE_PASSWORD, SshCredential::AUTH_TYPE_KEY])->default(SshCredential::AUTH_TYPE_KEY)->nullable();
            $table->text('password')->nullable();    
            $table->longText('private_key')->nullable(); 
            $table->text('passphrase')->nullable();  
            // $table->string('root')->default('/' ); 
            // $table->unsignedSmallInteger('timeout')->default(10);
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('filesystems', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('scope_id')->nullable();
            $table->enum('driver', Filesystem::drivers())->default(Filesystem::DRIVER_LOCAL)->nullable();
            $table->string('host')->nullable();
            $table->integer('port')->default(22)->nullable();
            $table->string('root_path')->nullable(); 
            $table->foreignId('ssh_credential_id')->nullable()->constrained('ssh_credentials')->nullOnDelete();
            $table->timestamps();
        });

        // Create default filesystems
        $public = Filesystem::create([
            'type' => FileSystem::TYPE_PUBLIC,
            'name' => 'Public (read-only)',
            'description' => 'Public filesystem - cannot be reconfigured in UI.',
            'root_path' => 'storage/public',
            'driver' => Filesystem::DRIVER_LOCAL,
        ]);

        Filesystem::create([
            'type' => FileSystem::TYPE_PRIVATE,
            'name' => 'Private (read-only)',
            'description' => 'Private filesystem - cannot be reconfigured in UI.',
            'root_path' => 'storage/private',
            'driver' => Filesystem::DRIVER_LOCAL,
        ]);

        Filesystem::create([
            'type' => Filesystem::TYPE_EXPORTS,
            'scope_id' => $public->id,
            'name' => 'Export Location',
            'description' => 'Locations for saving dataset exports (membrane/method/publication interactions, ...)',
            'root_path' => '/download'
        ]);

        Filesystem::create([
            'type' => Filesystem::TYPE_PREDICTIONS_METACENTRUM,
            'name' => 'MetaCentrum predictions',
            'description' => 'Where are stored predictions (runtime) data?',
            'host' => 'zuphux.metacentrum.cz',
            'port' => 22,
            'driver' => Filesystem::DRIVER_SFTP,
            'root_path' => '~/.MolMeDB/'
        ]);

        $backup = Filesystem::create([
            'type' => Filesystem::TYPE_BACKUPS,
            'name' => 'Backups',
            'description' => 'Where to store all backups?',
            'host' => 'example.molmedb.upol.cz',
            'port' => 22,
            'driver' => Filesystem::DRIVER_SFTP,
            'root_path' => '~/molmedb/backups'
        ]);

        Filesystem::create([
            'type' => Filesystem::TYPE_UPLOAD_STORAGE,
            'name' => 'Backups - upload',
            'description' => 'Where to backup uploaded files?',
            'scope_id' => $backup->id,
            'root_path' => '/upload'
        ]);

        Filesystem::create([
            'type' => Filesystem::TYPE_STRUCTURE_STORAGE,
            'name' => 'Backups - structures',
            'description' => 'Where to backup structure files, like SDFs?',
            'scope_id' => $backup->id,
            'root_path' => '/structures'
        ]);

        Filesystem::create([
            'type' => Filesystem::TYPE_PREDICTIONS_STORAGE,
            'name' => 'Backups - predictions',
            'description' => 'Where to backup prediction results?',
            'scope_id' => $backup->id,
            'root_path' => '/predictions'
        ]);

        Filesystem::create([
            'type' => Filesystem::TYPE_RDF_STORAGE,
            'name' => 'RDF-related files',
            'description' => 'Where to store RDF-related files?',
            'scope_id' => $backup->id,
            'root_path' => '/rdf'
        ]);
    }

    public function down(): void {
        Schema::dropIfExists('filesystems');
        Schema::dropIfExists('ssh_credentials');
    }
};