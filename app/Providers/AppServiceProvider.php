<?php

namespace App\Providers;

use App\Models\User;
use App\Models\document;
use App\Policies\RolePolicy;
use App\Policies\userPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\DocumentPolicy;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

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
        Model::unguard();

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(user::class, userPolicy::class);
        Gate::policy(document::class, DocumentPolicy::class);

        // Deteksi N+1 query di environment lokal:
        // Uncomment baris di bawah ini untuk menemukan lazy load yang tersisa,
        // tapi pastikan semua relasi sudah di-eager load di Repeater terlebih dahulu.
        // if (app()->environment('local')) {
        //     Model::preventLazyLoading();
        // }

        // Log query yang lambat (lebih dari 500ms) untuk debugging
        DB::whenQueryingForLongerThan(500, function (DB $db, $event) {
            Log::warning('Slow query detected!', [
                'sql'  => $event->sql,
                'time' => $event->time . 'ms',
            ]);
        });
    }
}
