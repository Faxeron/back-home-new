<?php

namespace App\Providers;

use App\Domain\CRM\Models\Contract;
use App\Policies\ContractPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Gate::policy(Contract::class, ContractPolicy::class);
    }
}
