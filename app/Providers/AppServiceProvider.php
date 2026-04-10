<?php

namespace App\Providers;

use App\Models\Filesystem;
use App\Models\SshCredential;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        if ($this->app->environment('production')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        $this->app->booted(function () {
            $this->registerDynamicServices();
        });
    }

    public function registerDynamicServices() : void
    {
        /** @var Filesystem[] $filesystems */
        $filesystems = Filesystem::orderBy('scope_id', 'desc')->get();

        $invalid = [];

        foreach($filesystems as $filesystem)
        {
            if($filesystem->type < 0)
            {
                // Do not register default drivers
                continue;
            }

            if(!$filesystem->isConfigured())
            {
                $invalid[] = $filesystem->id;
                continue;

            }

            if(in_array($filesystem->scope_id, $invalid))
            {
                $invalid[] = $filesystem->id;
                continue;
            }

            if(!$filesystem->scope()->exists())
            {
                Config::set('filesystems.disks.' . $filesystem->systemName, [
                    'driver' => $filesystem->driver,
                    'host' => $filesystem->host,
                    'port' => $filesystem->port,
                    'username' => $filesystem->sshCredential->username,
                    'password' => $filesystem->sshCredential->type == SshCredential::AUTH_TYPE_PASSWORD ? $filesystem->sshCredential->password : null,
                    'privateKey' => $filesystem->sshCredential->type == SshCredential::AUTH_TYPE_KEY ? $filesystem->sshCredential->private_key : null,
                    'passphrase' => $filesystem->sshCredential->type == SshCredential::AUTH_TYPE_KEY ? $filesystem->sshCredential->passphrase : null,
                    'root' => $filesystem->root_path,
                    'timeout' => 30,
                ]);
            }
            else
            {
                Config::set('filesystems.disks.' . $filesystem->systemName, [
                    'driver' => 'scoped',
                    'disk' => $filesystem->scope->systemName,
                    'prefix' => trim($filesystem->root_path, '/'),
                ]);
            }

            Storage::forgetDisk($filesystem->systemName);
        }
    }
}
