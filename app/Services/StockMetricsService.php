<?php

namespace App\Services;

use App\Models\PhoneModel;
use App\Models\Setting;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Builder;

class StockMetricsService
{
    public function lowStockModels(): Builder
    {
        $default = Setting::value(Setting::DEFAULT_MIN_STOCK);

        if ($default === null || $default === '') {
            return PhoneModel::query()
                ->whereNotNull('min_stock')
                ->whereRaw('(SELECT COUNT(*) FROM stocks WHERE stocks.phone_model_id = phone_models.id AND stocks.status = ? AND stocks.deleted_at IS NULL) < phone_models.min_stock', [Stock::STATUS_AVAILABLE]);
        }

        return PhoneModel::query()
            ->whereRaw('(SELECT COUNT(*) FROM stocks WHERE stocks.phone_model_id = phone_models.id AND stocks.status = ? AND stocks.deleted_at IS NULL) < COALESCE(phone_models.min_stock, ?)', [Stock::STATUS_AVAILABLE, (int) $default])
            ->whereRaw('COALESCE(phone_models.min_stock, ?) > 0', [(int) $default]);
    }

    public function overview(): array
    {
        $available = Stock::available();
        $days = (int) Setting::value(Setting::OLD_STOCK_DAYS, 30);

        return [
            'available_units' => (clone $available)->count(),
            'stock_value' => (int) (clone $available)->sum('cost_price'),
            'old_units' => (clone $available)->whereDate('purchase_date', '<', now()->subDays($days)->toDateString())->count(),
            'low_stock_models' => $this->lowStockModels()->count(),
            'old_stock_days' => $days,
        ];
    }
}
