<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view): void {
            static $appTheme = null;

            if ($appTheme === null) {
                $appTheme = 'dark';

                if (Schema::hasTable('app_settings')) {
                    $appTheme = AppSetting::getValue('app_theme', 'dark') ?? 'dark';
                }
            }

            $view->with('appTheme', in_array($appTheme, ['dark', 'light'], true) ? $appTheme : 'dark');
        });
    }
}
