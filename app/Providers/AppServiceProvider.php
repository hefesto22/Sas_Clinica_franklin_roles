<?php

namespace App\Providers;

use App\Models\CambioEvento;
use App\Observers\CambioEventoObserver;
use App\Policies\ActivityPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

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
        CambioEvento::observe(CambioEventoObserver::class);

        // El modelo Activity vive en el vendor: la policy se registra manualmente.
        // Solo super_admin (vía Gate::before de Shield) puede ver la auditoría.
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
