<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Repositories\CartRepository;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
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
