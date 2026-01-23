<?php
namespace App\Http\Controllers\Export;

use App\Console\Commands\Database\BackupDbIdsm;
use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\Filesystem;
use Illuminate\Support\Facades\Storage;

class ExportIdsmController extends Controller
{
    /**
     * Returns info about last backup for IDSM database.
     */
    public function info()
    {
        $filesystem = Filesystem::where('type', Filesystem::TYPE_DB_PUBLIC_BACKUP)->first();

        $fail = false;

        if(!$filesystem || !$filesystem->isDiskConnected())
        {
            $fail = true;
        }

        $last_update = Config::get('db_backup_idsm_last', null);

        if($last_update)
        {
            $last_update = date('Y-m-d', strtotime($last_update));
            $filename = BackupDbIdsm::datePath().'backup-idsm-'.$last_update.'.sql.gz';
        }

        if($fail || !isset($filename))
        {
            return response()->json([
                'last_backup' => null,
                'is_available' => false
            ], 404);
        }

        $disk = Storage::disk($filesystem->systemName);

        if(!$disk->exists($filename))
        {
            return response()->json([
                'last_backup' => $last_update,
                'is_available' => false
            ], 404);
        }

        return response()->json([
            'last_backup' => $last_update,
            'is_available' => true,
            'download_url' => route('export.dump.idsm.download')
        ], 200);
    }

    /**
     * Download last backup for IDSM database.
     */
    public function download()
    {
        $filesystem = Filesystem::where('type', Filesystem::TYPE_DB_PUBLIC_BACKUP)->first();

        $fail = false;

        if(!$filesystem || !$filesystem->isDiskConnected())
        {
            $fail = true;
        }

        $last_update = Config::get('db_backup_idsm_last', null);

        if($last_update)
        {
            $last_update = date('Y-m-d', strtotime($last_update));
            $filename = BackupDbIdsm::datePath().'backup-idsm-'.$last_update.'.sql.gz';
        }

        abort_unless(!$fail || isset($filename), 404);

        $disk = Storage::disk($filesystem->systemName);

        abort_unless($disk->exists($filename), 404);

        return response()->streamDownload(function () use ($disk, $filename) {
            $stream = $disk->readStream($filename);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, basename($filename),
        [
            'Content-Type'        => 'application/gzip',
            'Content-Encoding'    => 'identity', 
            'Content-Disposition' => 'attachment; filename="'.basename($filename).'"',
        ]);
    }
}
