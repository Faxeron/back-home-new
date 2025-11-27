<?php

use App\Http\Controllers\Api\CashBoxController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CityDistrictController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ContractStatusController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SaleTypeController;
use App\Http\Controllers\Api\SpendingFundController;
use App\Http\Controllers\Api\SpendingItemController;
use App\Http\Controllers\API\Finance\ReceiptController;
use App\Http\Controllers\API\Finance\SpendingController;
use App\Http\Controllers\API\Finance\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);

Route::prefix('finances')->group(function (): void {
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::get('receipts', [ReceiptController::class, 'index']);
    Route::get('spendings', [SpendingController::class, 'index']);
});

Route::prefix('settings')->group(function (): void {
    Route::apiResource('contract-statuses', ContractStatusController::class)->parameter('contract-statuses', 'contractStatus');
    Route::apiResource('cash-boxes', CashBoxController::class)->parameter('cash-boxes', 'cashBox');
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('spending-funds', SpendingFundController::class)->parameter('spending-funds', 'spendingFund');
    Route::apiResource('spending-items', SpendingItemController::class)->parameter('spending-items', 'spendingItem');
    Route::get('cities', [CityController::class, 'index']);
    Route::get('cities-districts', [CityDistrictController::class, 'index']);
    Route::apiResource('sale-types', SaleTypeController::class);
    Route::get('tenants', [TenantController::class, 'index']);
});

Route::get('contracts', [ContractController::class, 'index']);
