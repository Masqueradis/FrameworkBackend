<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Repositories\CartRepository;
use App\Repositories\CommentRepository;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Services\Gateways\Contracts\PaymentGatewayInterface;
use App\Services\Gateways\FakePaymentGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->bind(
            CartRepositoryInterface::class,
            CartRepository::class,


        );

        $this->app->bind(
            OrderRepositoryInterface::class,
            OrderRepository::class
        );

        $this->app->bind(
            CommentRepositoryInterface::class,
            CommentRepository::class
        );

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::define('access-panel', function (User $user) {
            return $user->hasRole(['admin', 'seller']);
        });

        Gate::define('manage-categories', function (User $user) {
            return $user->hasRole(['admin']);
        });
    }
}
