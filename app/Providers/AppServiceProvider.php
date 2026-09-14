<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void
    {
        Auth::provider('legacy-compatible-eloquent', fn($app, array $config) => new \App\Auth\LegacyUserProvider($app['hash'], $config['model']));
        Gate::define('admin', fn(User $user) => $user->isAdmin());
    }
}