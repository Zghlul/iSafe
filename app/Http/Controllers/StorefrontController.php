<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function __invoke(): View
    {
        $products = Stock::query()
            ->available()
            ->with('phoneModel:id,name')
            ->orderBy('phone_model_id')
            ->orderBy('storage')
            ->orderBy('color')
            ->get([
                'phone_model_id',
                'storage',
                'color',
                'condition',
                'battery_health',
                'variant',
                'physical_grade',
                'source_name',
            ])
            ->groupBy(fn (Stock $stock): string => implode('|', [
                $stock->phone_model_id,
                $stock->storage,
                $stock->color,
                $stock->condition,
                $stock->variant ?? '',
                $stock->physical_grade ?? '',
                $stock->battery_health ?? '',
                $stock->source_name ?? '',
            ]));

        $demoUnitCount = $products->sum(
            fn ($units): int => $units->first()->source_name === 'Demo katalog' ? $units->count() : 0,
        );

        return view('welcome', [
            'products' => $products,
            'demoUnitCount' => $demoUnitCount,
            'whatsappNumber' => '6285183660490',
        ]);
    }
}
