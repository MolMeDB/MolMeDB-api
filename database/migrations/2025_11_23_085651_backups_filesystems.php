<?php

use App\Models\Filesystem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void 
    {
        $backup = Filesystem::where(
            'type', Filesystem::TYPE_BACKUPS)->first();

        Filesystem::create([
            'type' => Filesystem::TYPE_DB_FULL_BACKUP,
            'name' => 'Backups - database (full)',
            'description' => 'Where to backup database dumps?',
            'scope_id' => $backup->id,
            'root_path' => '/db/full'
        ]);

        Filesystem::create([
            'type' => Filesystem::TYPE_DB_PUBLIC_BACKUP,
            'name' => 'Backups - database (public dumps)',
            'description' => 'Where to backup database public dumps?',
            'scope_id' => $backup->id,
            'root_path' => '/db/public'
        ]);
    }

    public function down(): void 
    {
        Filesystem::where('type', Filesystem::TYPE_DB_FULL_BACKUP)->delete();
        Filesystem::where('type', Filesystem::TYPE_DB_PUBLIC_BACKUP)->delete();
    }
};