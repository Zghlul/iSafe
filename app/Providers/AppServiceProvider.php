<?php

namespace App\Providers;

use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.app', function ($view): void {
            if (! auth()->check()) {
                return;
            }

            $target = (int) (Setting::query()->where('key', 'monthly_target')->value('value') ?? 0);
            $revenue = (int) Sale::query()
                ->whereBetween('sale_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('selling_price');
            $storeLogo = Setting::query()->where('key', 'store_logo')->value('value');
            $lossSales = Sale::query()
                ->whereBetween('sale_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->where('profit', '<', 0)
                ->latest('sale_date')
                ->limit(5)
                ->get(['id', 'model', 'buyer_name', 'profit']);

            $view->with([
                'sidebarMonthlyTarget' => $target,
                'sidebarMonthlyRevenue' => $revenue,
                'sidebarTargetProgress' => $target > 0 ? min(100, (int) round($revenue * 100 / $target)) : 0,
                'sidebarStoreLogo' => $storeLogo,
                'sidebarLossSales' => $lossSales,
            ]);
        });
    }
}
