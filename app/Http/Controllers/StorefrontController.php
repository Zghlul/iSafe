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
            ])
            ->groupBy(fn (Stock $stock): string => implode('|', [
                $stock->phone_model_id,
                $stock->storage,
                $stock->color,
                $stock->condition,
                $stock->variant ?? '',
                $stock->physical_grade ?? '',
                $stock->battery_health ?? '',
            ]));

        return view('welcome', [
            'products' => $products,
            'whatsappNumber' => '6285183660490',
        ]);
    }
}
