<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PhoneModelController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/transactions', [SalesController::class, 'index'])->name('sales.index');
    Route::post('/transactions', [SalesController::class, 'store'])->name('sales.store');
    Route::get('/transactions/export', [SalesController::class, 'export'])->name('sales.export');
    Route::get('/transactions/imei-check', [SalesController::class, 'checkImei'])->name('sales.imei-check');
    Route::put('/transactions/{sale}', [SalesController::class, 'update'])->name('sales.update');
    Route::delete('/transactions/{sale}', [SalesController::class, 'destroy'])->name('sales.destroy');
    Route::post('/transactions/{sale}/restore', [SalesController::class, 'restore'])->name('sales.restore');
    Route::post('/transactions/bulk-delete', [SalesController::class, 'bulkDelete'])->name('sales.bulk-delete');
    Route::post('/transactions/bulk-restore', [SalesController::class, 'bulkRestore'])->name('sales.bulk-restore');

    Route::get('/stock', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/stock/export', [StockController::class, 'export'])->name('stock-reports.export');
    Route::get('/stock/reports/{report}', [StockReportController::class, 'show'])->name('stock-reports.show');
    Route::post('/stock', [StockController::class, 'store'])->name('stocks.store');
    Route::get('/stock/imei-check', [StockController::class, 'checkImei'])->name('stocks.imei-check');
    Route::get('/stock/available-search', [StockController::class, 'searchAvailable'])->name('stocks.available-search');
    Route::post('/stock/bulk-delete', [StockController::class, 'bulkDelete'])->name('stocks.bulk-delete');
    Route::post('/stock/{stock}/restore', [StockController::class, 'restore'])->name('stocks.restore');
    Route::get('/stock/{stock}', [StockController::class, 'show'])->name('stocks.show');
    Route::put('/stock/{stock}', [StockController::class, 'update'])->name('stocks.update');
    Route::delete('/stock/{stock}', [StockController::class, 'destroy'])->name('stocks.destroy');

    Route::get('/models', [PhoneModelController::class, 'index'])->name('models.index');
    Route::post('/models', [PhoneModelController::class, 'store'])->name('models.store');
    Route::put('/models/{phoneModel}', [PhoneModelController::class, 'update'])->name('models.update');
    Route::delete('/models/{phoneModel}', [PhoneModelController::class, 'destroy'])->name('models.destroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/export', [ExportController::class, 'index'])->name('exports.index');
    Route::get('/export/transactions', [ExportController::class, 'transactions'])->name('exports.transactions');
});

require __DIR__.'/auth.php';
