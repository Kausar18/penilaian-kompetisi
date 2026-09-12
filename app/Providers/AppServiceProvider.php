<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Sejak '/' menjadi halaman publik, user yang sudah login dan membuka
        // /login tidak boleh mendarat di halaman publik — antar dia ke panelnya.
        RedirectIfAuthenticated::redirectUsing(function (Request $request) {
            /** @var User|null $pengguna */
            $pengguna = $request->user();

            return route($pengguna?->isReviewer() ? 'admin.penilaian.index' : 'admin.statistik');
        });
    }
}
