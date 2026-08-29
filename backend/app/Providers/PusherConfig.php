<?php

namespace App\Providers;

use App\Models\PusherCredentail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class PusherConfig extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->booted(function () {
            if ($this->app->environment('testing')) {
                return;
            }

            try {
                if (!Schema::hasTable('pusher_credentails')) {
                    return;
                }

                $pusher = PusherCredentail::first();
                if (!$pusher) {
                    return;
                }

                $pusherConfig = [
                    'driver' => 'pusher',
                    'key' => $pusher->app_key,
                    'secret' => $pusher->app_secret,
                    'app_id' => $pusher->app_id,
                    'options' => [
                        'cluster' => $pusher->app_cluster,
                        'useTLS' => true,
                        'encrypted' => true
                    ],
                ];

                config(['broadcasting.connections.pusher' => $pusherConfig]);
            } catch (\Throwable $exception) {
                return;
            }
        });

    }
}
