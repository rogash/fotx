<?php

namespace App\Providers;

use Aws\Rekognition\RekognitionClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RekognitionClient::class, fn (): RekognitionClient => new RekognitionClient(array_filter([
            'version' => '2016-06-27',
            'region' => config('fotx.rekognition_region'),
            // Sem chaves no .env, o SDK usa a cadeia padrão de credenciais (ex.: IAM role).
            'credentials' => filled(config('fotx.rekognition_access_key_id')) ? [
                'key' => config('fotx.rekognition_access_key_id'),
                'secret' => config('fotx.rekognition_secret_access_key'),
            ] : null,
        ])));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale((string) config('app.locale'));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
