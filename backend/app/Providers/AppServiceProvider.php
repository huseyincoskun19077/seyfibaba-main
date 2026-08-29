<?php

namespace App\Providers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Notifications\Channels\FcmChannel;
use App\Observers\CouponObserver;
use App\Observers\OrderObserver;
use App\Observers\OrderProductObserver;
use App\Observers\ProductObserver;
use App\Services\NetgsmService;
use App\Services\SmsServiceInterface;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(SmsServiceInterface::class, function ($app) {
            return new NetgsmService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        foreach ([
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ] as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }

        Notification::extend('fcm', function ($app) {
            return new FcmChannel();
        });

        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
        OrderProduct::observe(OrderProductObserver::class);
        Coupon::observe(CouponObserver::class);
    }
}
