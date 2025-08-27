<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Filament\Facades\Filament;

class FilamentMetaServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Add custom meta tags to Filament pages
        Filament::serving(function () {
            // Add custom meta tags
            Filament::registerRenderHook(
                'head.start',
                fn (): string => view('filament.meta-tags')->render()
            );
        });
    }
}
