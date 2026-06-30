<?php

use App\Models\Filesystem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $backup = Filesystem::where('type', Filesystem::TYPE_BACKUPS)->first();

        Filesystem::create([
            'type' => Filesystem::TYPE_DB_PREDICTIONS_BACKUP,
            'name' => 'Backups - predictions database',
            'description' => 'Where to backup predictions database dumps?',
            'scope_id' => $backup->id,
            'root_path' => '/db/predictions',
        ]);
    }

    public function down(): void
    {
        Filesystem::where('type', Filesystem::TYPE_DB_PREDICTIONS_BACKUP)->delete();
    }
};
