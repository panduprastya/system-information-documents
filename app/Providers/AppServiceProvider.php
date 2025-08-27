<?php

namespace App\Providers;

use App\Models\User;
use App\Models\document;
use App\Policies\RolePolicy;
use App\Policies\userPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\DocumentPolicy;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
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
    }
}
