<?php

use App\Http\Controllers\Api\CashBoxController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CityDistrictController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ContractStatusController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\ContractStatusHistoryController;
use App\Http\Controllers\Api\DevControlController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\API\Common\CompanyLookupController;
use App\Http\Controllers\API\Finance\CashBoxController as FinanceCashBoxController;
use App\Http\Controllers\API\Finance\CashTransferController;
use App\Http\Controllers\API\Finance\DirectorController;
use App\Http\Controllers\Api\SaleTypeController;
use App\Http\Controllers\Api\SpendingFundController;
use App\Http\Controllers\Api\SpendingItemController;
use App\Http\Controllers\API\Finance\TransactionTypeController;
use App\Http\Controllers\API\Finance\PaymentMethodController;
use App\Http\Controllers\API\Finance\FundController;
use App\Http\Controllers\API\Finance\SpendingItemLookupController;
use App\Http\Controllers\API\Finance\CounterpartyLookupController;
use App\Http\Controllers\Api\Catalog\ProductBrandController;
use App\Http\Controllers\Api\Catalog\ProductCategoryController;
use App\Http\Controllers\Api\Catalog\ProductController;
use App\Http\Controllers\Api\Catalog\ProductKindController;
use App\Http\Controllers\Api\Catalog\ProductSubcategoryController;
use App\Http\Controllers\Api\Estimates\EstimateController;
use App\Http\Controllers\Api\Estimates\EstimateItemController;
use App\Http\Controllers\Api\Estimates\EstimatePublicController;
use App\Http\Controllers\Api\Estimates\EstimateTemplateMaterialController;
use App\Http\Controllers\Api\Estimates\EstimateTemplateSeptikController;
use App\Http\Controllers\Api\Estimates\EstimateTemplateController;
use App\Http\Controllers\API\Finance\ReceiptController;
use App\Http\Controllers\API\Finance\SpendingController;
use App\Http\Controllers\API\Finance\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);

Route::get('estimate/{randomId}', [EstimatePublicController::class, 'show']);
Route::get('estimate/{randomId}/montaj', [EstimatePublicController::class, 'montaj']);
Route::get('estimate/{randomId}/montaj-mnt', [EstimatePublicController::class, 'montaj']);

Route::middleware(['auth:sanctum', 'tenant.company'])->group(function (): void {
    Route::prefix('finance')->group(function (): void {
        Route::get('transactions', [TransactionController::class, 'index']);

        Route::get('cashboxes', [FinanceCashBoxController::class, 'index']);
        Route::get('cashboxes/{cashBoxId}/balance', [FinanceCashBoxController::class, 'balance']);

        Route::get('transaction-types', [TransactionTypeController::class, 'index']);
        Route::get('payment-methods', [PaymentMethodController::class, 'index']);
        Route::get('funds', [FundController::class, 'index']);
        Route::get('spending-items', [SpendingItemLookupController::class, 'index']);
        Route::get('counterparties', [CounterpartyLookupController::class, 'index']);

        Route::get('receipts', [ReceiptController::class, 'index']);
        Route::post('receipts/contract', [ReceiptController::class, 'storeContract']);
        Route::post('receipts/director-loan', [ReceiptController::class, 'storeDirectorLoan']);

        Route::get('spendings', [SpendingController::class, 'index']);
        Route::post('spendings', [SpendingController::class, 'store']);

        Route::post('director-withdrawal', [DirectorController::class, 'withdrawal']);

        Route::get('cash-transfers', [CashTransferController::class, 'index']);
        Route::post('cash-transfers', [CashTransferController::class, 'store']);
    });

    // Backwards-compatible aliases (to be deprecated) for legacy /finances/* consumers.
    Route::prefix('finances')->group(function (): void {
        Route::get('transactions', [TransactionController::class, 'index']);
        Route::get('receipts', [ReceiptController::class, 'index']);
        Route::get('spendings', [SpendingController::class, 'index']);
    });

    Route::prefix('common')->group(function (): void {
        Route::get('companies', [CompanyLookupController::class, 'index']);
    });

    Route::prefix('dev-control')->group(function (): void {
        Route::get('/', [DevControlController::class, 'index']);
        Route::patch('{id}', [DevControlController::class, 'update']);
        Route::post('sync-defaults', [DevControlController::class, 'syncDefaults']);
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

    Route::prefix('products')->group(function (): void {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('categories', [ProductCategoryController::class, 'index']);
        Route::get('subcategories', [ProductSubcategoryController::class, 'index']);
        Route::get('brands', [ProductBrandController::class, 'index']);
        Route::get('kinds', [ProductKindController::class, 'index']);
        Route::get('{product}', [ProductController::class, 'show']);
        Route::patch('{product}', [ProductController::class, 'update']);
    });

    Route::prefix('estimates')->group(function (): void {
        Route::get('/', [EstimateController::class, 'index']);
        Route::post('/', [EstimateController::class, 'store']);
        Route::get('{estimate}', [EstimateController::class, 'show']);
        Route::patch('{estimate}', [EstimateController::class, 'update']);
        Route::delete('{estimate}', [EstimateController::class, 'destroy']);
        Route::post('{estimate}/apply-template', [EstimateTemplateController::class, 'applyTemplate']);
        Route::post('{estimate}/items', [EstimateItemController::class, 'store']);
        Route::patch('{estimate}/items/{item}', [EstimateItemController::class, 'update']);
        Route::post('{estimate}/revoke-public', [EstimateController::class, 'revokePublic']);
    });

    Route::prefix('estimate-templates')->group(function (): void {
        Route::get('materials', [EstimateTemplateMaterialController::class, 'index']);
        Route::post('materials', [EstimateTemplateMaterialController::class, 'store']);
        Route::get('materials/{template}', [EstimateTemplateMaterialController::class, 'show']);
        Route::patch('materials/{template}', [EstimateTemplateMaterialController::class, 'update']);
        Route::delete('materials/{template}', [EstimateTemplateMaterialController::class, 'destroy']);

        Route::get('septiks', [EstimateTemplateSeptikController::class, 'index']);
        Route::post('septiks', [EstimateTemplateSeptikController::class, 'store']);
        Route::get('septiks/{template}', [EstimateTemplateSeptikController::class, 'show']);
        Route::patch('septiks/{template}', [EstimateTemplateSeptikController::class, 'update']);
        Route::delete('septiks/{template}', [EstimateTemplateSeptikController::class, 'destroy']);
    });

    Route::get('contracts', [ContractController::class, 'index']);
    Route::patch('contracts/{contract}/status', [ContractController::class, 'updateStatus']);
    Route::get('contracts/status-history', [ContractStatusHistoryController::class, 'index']);
    Route::get('contracts/{contract}', [ContractController::class, 'show'])->whereNumber('contract');
});
