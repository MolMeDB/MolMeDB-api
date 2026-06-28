<?php

namespace App\Providers;

use App\Listeners\RunPredictionsMigrationsAfterDefaultMigrate;
use App\Models\Config as ConfigModel;
use App\Models\Filesystem;
use App\Models\SshCredential;
use App\Policies\ConfigPolicy;
use App\Policies\PredictionDatasetPolicy;
use Exception;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Modules\PredictionWorkers\Models\PredictionDataset;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ConfigModel::class, ConfigPolicy::class);
        Gate::policy(PredictionDataset::class, PredictionDatasetPolicy::class);

        RateLimiter::for('remote-prediction-status', fn (): Limit => Limit::perMinute(
            max(1, (int) config('prediction-workers.remote.worker.max_status_requests_per_minute', 30)),
        )->by('remote-prediction-status'));

        Event::listen(CommandFinished::class, RunPredictionsMigrationsAfterDefaultMigrate::class);

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/{$token}?email=".urlencode($notifiable->getEmailForPasswordReset());
        });

        VerifyEmail::createUrlUsing(function (object $notifiable) {
            $url = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                absolute: false,
            );

            $query = parse_url($url, PHP_URL_QUERY);

            return config('app.frontend_url')."/verify-email/{$notifiable->getKey()}/".sha1($notifiable->getEmailForVerification()).($query ? "?{$query}" : '');
        });

        if ($this->app->environment('production')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        $this->app->booted(function () {
            $this->registerDynamicServices();
        });
    }

    public function registerDynamicServices(): void
    {
        if ($this->isTestRuntime()) {
            return;
        }

        try {
            if (! Schema::hasTable('filesystems')) {
                return;
            }
        } catch (Exception) {
            return;
        }

        /** @var Filesystem[] $filesystems */
        $filesystems = Filesystem::orderBy('scope_id', 'desc')->get();

        $invalid = [];

        foreach ($filesystems as $filesystem) {
            if ($filesystem->type < 0) {
                // Do not register default drivers
                continue;
            }

            if (! $filesystem->isConfigured()) {
                $invalid[] = $filesystem->id;

                continue;
            }

            if (in_array($filesystem->scope_id, $invalid)) {
                $invalid[] = $filesystem->id;

                continue;
            }

            if (! $filesystem->scope()->exists()) {
                Config::set('filesystems.disks.'.$filesystem->systemName, [
                    'driver' => $filesystem->driver,
                    'host' => $filesystem->host,
                    'port' => $filesystem->port,
                    'username' => $filesystem->sshCredential->username,
                    'password' => $filesystem->sshCredential->type == SshCredential::AUTH_TYPE_PASSWORD ? $filesystem->sshCredential->password : null,
                    'privateKey' => $filesystem->sshCredential->type == SshCredential::AUTH_TYPE_KEY ? $filesystem->sshCredential->private_key : null,
                    'passphrase' => $filesystem->sshCredential->type == SshCredential::AUTH_TYPE_KEY ? $filesystem->sshCredential->passphrase : null,
                    'visibility' => 'public',
                    'directoryPerm' => '0755',
                    'permPublic' => '0644',
                    'permPrivate' => '0644',
                    'root' => $filesystem->root_path,
                    'timeout' => 30,
                ]);
            } else {
                Config::set('filesystems.disks.'.$filesystem->systemName, [
                    'driver' => 'scoped',
                    'disk' => $filesystem->scope->systemName,
                    'prefix' => trim($filesystem->root_path, '/'),
                ]);
            }

            Storage::forgetDisk($filesystem->systemName);
        }
    }

    private function isTestRuntime(): bool
    {
        $consoleArguments = $_SERVER['argv'] ?? [];

        if ($this->app->runningUnitTests() || $this->app->environment('testing')) {
            return true;
        }

        if (! $this->app->runningInConsole()) {
            return false;
        }

        return in_array('test', $consoleArguments, true)
            || in_array('phpunit', $consoleArguments, true)
            || in_array('pest', $consoleArguments, true);
    }
}
