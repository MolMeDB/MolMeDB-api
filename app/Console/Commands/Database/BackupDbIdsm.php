<?php

namespace App\Console\Commands\Database;

use App\Models\Config;
use App\Models\Filesystem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class BackupDbIdsm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:dump-idsm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a safe database backup for IDSM and saves it to the config specified location.';

    private $exludedTables = [
        'public.activity_log',
        'public.authors',
        'public.cache',
        'public.cache_locks',
        'public.configs',
        'public.dataset_groups',
        'public.failed_jobs',
        'public.files',
        'public.filesystems',
        'public.job_batches',
        'public.jobs',
        'public.migrations',
        'public.model_has_files',
        'public.model_has_permissions',
        'public.model_has_roles',
        'public.password_reset_tokens',
        'public.permissions',
        'public.personal_access_tokens',
        'public.publication_has_authors',
        'public.role_has_permissions',
        'public.roles',
        'public.sessions',
        'public.ssh_credentials',
        'public.stats',
        'public.upload_queue',
        'public.users'
    ];

    public static function datePath()
    {
        return date('Y').'/'.date('m').'/'.date('d').'/';
    }   

    public function make_folder_structure($disk)
    {
        if($disk)
        {
            // Make basic directories if not exists
            $disk->makeDirectory($this->datePath());
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Making IDSM database backup...');

        $db = config('database.connections.pgsql.database');
        $user = config('database.connections.pgsql.username');
        $pass = config('database.connections.pgsql.password');
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port');

        $filesystem = Filesystem::where('type', Filesystem::TYPE_DB_PUBLIC_BACKUP)->first();

        if(!$filesystem)
        {
            $this->error('No backup filesystem configured! Aborting.');
            return 1;
        }

        if(!$filesystem->isDiskConnected())
        {
            $this->error('Could not access backup filesystem "'.$filesystem->systemName.'"! Aborting.');
            return 1;
        }

        $disk = Storage::disk($filesystem->systemName);

        // Make basic directories if not exists
        $this->make_folder_structure($disk);

        $filename = self::datePath().'backup-idsm-'.date('Y-m-d').'.sql';

        $tmpFile = TemporaryDirectory::make()->path($filename);

        $cmd = "PGPASSWORD=\"$pass\" pg_dump -U $user -p $port -h $host $db ";

        foreach($this->exludedTables as $table)
        {
            $cmd .= "--exclude-table=$table* ";
        }

        $cmd .= " > $tmpFile";

        exec($cmd);

        $this->info('# Dump created.');
        $this->info('# Archiving...');

        $archiveCmd = "gzip $tmpFile";
        $tmpFile .= '.gz';
        $filename .= '.gz';
        exec($archiveCmd);

        $this->info('# Dump archived. Uploading...');

        // Delete old backup if exists
        if($disk->exists($filename))
        {
            $disk->delete($filename);
            $this->warn('# Old backup deleted.');
        }

        $stream = fopen($tmpFile, 'r+');
        $disk->put($filename, $stream);
        fclose($stream);

        unlink($tmpFile);

        Config::set('db_backup_idsm_last', date('Y-m-d H:i:s'));
        
        $this->info('Backup created and uploaded.');
    }
}
