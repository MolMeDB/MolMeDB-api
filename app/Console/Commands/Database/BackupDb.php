<?php

namespace App\Console\Commands\Database;

use App\Models\Filesystem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class BackupDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:dump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a whole-database backup and saves it to the config specified location.';

    public function datePath()
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
        $this->info('Making database backup...');

        $db = config('database.connections.pgsql.database');
        $user = config('database.connections.pgsql.username');
        $pass = config('database.connections.pgsql.password');
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port');

        $filesystem = Filesystem::where('type', Filesystem::TYPE_DB_FULL_BACKUP)->first();

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

        $filename = $this->datePath().'backup-base-'.date('Y-m-d').'.sql';

        $tmpFile = TemporaryDirectory::make()->path($filename);

        // ---------------------------------------------------------
        // 1) CREATE ANONYMIZATION VIEW
        // ---------------------------------------------------------
        DB::statement('DROP TABLE IF EXISTS ssh_credentials_export');

        DB::statement("
            CREATE TABLE ssh_credentials_export AS
            SELECT 
                id,
                name,
                username,
                type,
                NULL::text AS password,
                NULL::text AS private_key,
                NULL::text AS passphrase,
                NULL as created_by,
                created_at,
                updated_at
            FROM ssh_credentials
        ");

        $this->info('# Anonymization table created.');

        $cmd = "PGPASSWORD=\"$pass\" pg_dump -U $user -p $port -h $host $db " .
           "--exclude-table-data=public.ssh_credentials " .
           "--exclude-table=public.ssh_credentials_export" .
           " > $tmpFile";

        exec($cmd);

        $tmpFile2 = TemporaryDirectory::make()->path('secured.sql');

        $cmd = "PGPASSWORD=\"$pass\" pg_dump -U $user -p $port -h $host $db " .
           "--data-only " .
           "--table=public.ssh_credentials_export " .
           " > $tmpFile2";

        exec($cmd);

        $renameCmd = "perl -pi -e 's/ssh_credentials_export/ssh_credentials/g' \"$tmpFile2\"";
        exec($renameCmd);

        DB::statement("DROP TABLE ssh_credentials_export;");

        $this->info('# Dump created.');

        $cmd = "cat $tmpFile2 >> $tmpFile";
        exec($cmd);

        $this->info('# Dump extended.');
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
        unlink($tmpFile2);

        $this->info('Backup created and uploaded.');
    }
}
