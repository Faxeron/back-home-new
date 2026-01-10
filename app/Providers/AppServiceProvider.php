<?php

namespace App\Providers;

use App\Domain\CRM\Models\Contract;
use App\Policies\ContractPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

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
        if (class_exists(Sanctum::class)) {
            Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
        }

        Gate::policy(Contract::class, ContractPolicy::class);
    }
}
