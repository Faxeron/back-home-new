<?php

use App\Http\Controllers\Api\CashBoxController;
use App\Http\Controllers\Api\CashBoxLogoController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CityDistrictController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ContractStatusController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\ContractStatusHistoryController;
use App\Http\Controllers\Api\ContractHistoryController;
use App\Http\Controllers\Api\ContractTemplateController;
use App\Http\Controllers\Api\ContractDocumentController;
use App\Http\Controllers\Api\ContractTemplateFileController;
use App\Http\Controllers\Api\DevControlController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\API\Common\CompanyLookupController;
use App\Http\Controllers\API\Finance\CashBoxController as FinanceCashBoxController;
use App\Http\Controllers\API\Finance\CashTransferController;
use App\Http\Controllers\API\Finance\CashflowItemController;
use App\Http\Controllers\API\Finance\DirectorController;
use App\Http\Controllers\Api\SaleTypeController;
use App\Http\Controllers\Api\SpendingFundController;
use App\Http\Controllers\Api\SpendingItemController;
use App\Http\Controllers\Api\MarginSettingsController;
use App\Http\Controllers\Api\PayrollRuleController;
use App\Http\Controllers\Api\PayrollAccrualController;
use App\Http\Controllers\Api\PayrollPayoutController;
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
use App\Http\Controllers\Api\Catalog\ProductTypeController;
use App\Http\Controllers\Api\Catalog\PricebookController;
use App\Http\Controllers\Api\Estimates\EstimateController;
use App\Http\Controllers\Api\Estimates\EstimateContractController;
use App\Http\Controllers\Api\Estimates\EstimateItemController;
use App\Http\Controllers\Api\Estimates\EstimatePublicController;
use App\Http\Controllers\Api\Estimates\EstimateTemplateMaterialController;
use App\Http\Controllers\Api\Estimates\EstimateTemplateSeptikController;
use App\Http\Controllers\Api\Estimates\EstimateTemplateController;
use App\Http\Controllers\API\Finance\ReceiptController;
use App\Http\Controllers\API\Finance\SpendingController;
use App\Http\Controllers\API\Finance\TransactionController;
use App\Http\Controllers\Api\ContractPayrollController;
use App\Http\Controllers\Api\UserLookupController;
use App\Http\Controllers\Api\InstallationController;
use App\Http\Controllers\Api\Settings\RolesPermissionsController;
use App\Http\Controllers\Api\Knowledge\KnowledgeArticleController;
use App\Http\Controllers\Api\Knowledge\KnowledgeAttachmentController;
use App\Http\Controllers\Api\Knowledge\KnowledgeTagController;
use App\Http\Controllers\Api\Knowledge\KnowledgeTopicController;
use App\Http\Controllers\Api\Dashboards\EmployeeDashboardController;
use App\Http\Controllers\Api\Dashboards\NewDashboardController;
use App\Http\Controllers\Api\Reports\CashflowReportController;
use App\Modules\PublicApi\Controllers\PublicCityController;
use App\Modules\PublicApi\Controllers\PublicCompanyController;
use App\Modules\PublicApi\Controllers\PublicCatalogController;
use App\Modules\PublicApi\Controllers\PublicLeadController;
use App\Modules\PublicApi\Controllers\PublicProductController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);

Route::get('estimate/{randomId}mnt', [EstimatePublicController::class, 'montaj']);
Route::get('estimate/{randomId}', [EstimatePublicController::class, 'show']);

Route::prefix('public')->group(function (): void {
    Route::get('cities', [PublicCityController::class, 'index']);
    Route::get('companies', [PublicCompanyController::class, 'index']);
    Route::get('catalog/tree', [PublicCatalogController::class, 'tree']);
    Route::get('catalog/categories/{slug}', [PublicCatalogController::class, 'category']);
    Route::get('catalog/subcategories/{slug}', [PublicCatalogController::class, 'subcategory']);
    Route::get('catalog/brands/{slug}', [PublicCatalogController::class, 'brand']);
    Route::get('products', [PublicProductController::class, 'index']);
    Route::get('products/{slug}', [PublicProductController::class, 'show']);
    Route::post('leads', [PublicLeadController::class, 'store'])->middleware('throttle:30,1');
});

Route::middleware(['auth:sanctum', 'tenant.company'])->group(function (): void {
    Route::prefix('dashboards')->group(function (): void {
        Route::get('employee/summary', [EmployeeDashboardController::class, 'summary'])->middleware('permission:view,dashboard.employee');
        Route::get('new/earning-reports', [NewDashboardController::class, 'earningReports'])->middleware('permission:view,finance');
    });

    Route::prefix('finance')->group(function (): void {
        Route::get('transactions', [TransactionController::class, 'index'])->middleware('permission:view,finance');
        Route::get('transactions/summary', [TransactionController::class, 'summary'])->middleware('permission:view,finance');
        Route::get('transactions/cashflow-series', [TransactionController::class, 'cashflowSeries'])->middleware('permission:view,finance');
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->middleware('permission:delete,finance');

        Route::get('cashboxes', [FinanceCashBoxController::class, 'index'])->middleware('permission:view,finance');
        Route::get('cashboxes/{cashBoxId}/balance', [FinanceCashBoxController::class, 'balance'])->middleware('permission:view,finance');

        Route::get('transaction-types', [TransactionTypeController::class, 'index'])->middleware('permission:view,finance');
        Route::get('payment-methods', [PaymentMethodController::class, 'index'])->middleware('permission:view,finance');
        Route::get('funds', [FundController::class, 'index'])->middleware('permission:view,finance');
        Route::get('spending-items', [SpendingItemLookupController::class, 'index'])->middleware('permission:view,finance');
        Route::get('counterparties', [CounterpartyLookupController::class, 'index'])->middleware('permission:view,clients');
        Route::get('counterparties/{counterparty}', [CounterpartyLookupController::class, 'show'])->middleware('permission:view,clients');

        Route::get('receipts', [ReceiptController::class, 'index'])->middleware('permission:view,finance');
        Route::post('receipts/contract', [ReceiptController::class, 'storeContract'])->middleware('permission:create,finance');
        Route::post('receipts/director-loan', [ReceiptController::class, 'storeDirectorLoan'])->middleware('permission:create,finance');

        Route::get('spendings', [SpendingController::class, 'index'])->middleware('permission:view,finance');
        Route::post('spendings', [SpendingController::class, 'store'])->middleware('permission:create,finance');
        Route::delete('spendings/{spending}', [SpendingController::class, 'destroy'])->middleware('permission:delete,finance');

        Route::post('director-withdrawal', [DirectorController::class, 'withdrawal'])->middleware('permission:create,finance');

        Route::get('cash-transfers', [CashTransferController::class, 'index'])->middleware('permission:view,finance');
        Route::post('cash-transfers', [CashTransferController::class, 'store'])->middleware('permission:create,finance');
    });

    Route::get('cashflow-items', [CashflowItemController::class, 'index'])->middleware('permission:view,finance');
    Route::post('cashflow-items', [CashflowItemController::class, 'store'])->middleware('permission:create,finance');
    Route::put('cashflow-items/{cashflowItem}', [CashflowItemController::class, 'update'])->middleware('permission:edit,finance');
    Route::delete('cashflow-items/{cashflowItem}', [CashflowItemController::class, 'destroy'])->middleware('permission:delete,finance');

    Route::get('reports/cashflow', [CashflowReportController::class, 'show'])->middleware('permission:view,finance');

    // Backwards-compatible aliases (to be deprecated) for legacy /finances/* consumers.
    Route::prefix('finances')->group(function (): void {
        Route::get('transactions', [TransactionController::class, 'index'])->middleware('permission:view,finance');
        Route::get('receipts', [ReceiptController::class, 'index'])->middleware('permission:view,finance');
        Route::get('spendings', [SpendingController::class, 'index'])->middleware('permission:view,finance');
    });

    Route::prefix('common')->group(function (): void {
        Route::get('companies', [CompanyLookupController::class, 'index'])->middleware('permission:view,settings.companies');
    });

    Route::prefix('dev-control')->group(function (): void {
        Route::get('/', [DevControlController::class, 'index'])->middleware('permission:view,dev_control');
        Route::patch('{id}', [DevControlController::class, 'update'])->middleware('permission:edit,dev_control');
        Route::post('sync-defaults', [DevControlController::class, 'syncDefaults'])->middleware('permission:edit,dev_control');
    });

    Route::prefix('settings')->group(function (): void {
        Route::apiResource('contract-statuses', ContractStatusController::class)
            ->parameter('contract-statuses', 'contractStatus')
            ->middlewareFor('index', 'permission:view,settings.contract_statuses')
            ->middlewareFor('show', 'permission:view,settings.contract_statuses')
            ->middlewareFor('store', 'permission:create,settings.contract_statuses')
            ->middlewareFor('update', 'permission:edit,settings.contract_statuses')
            ->middlewareFor('destroy', 'permission:delete,settings.contract_statuses');

        Route::apiResource('cash-boxes', CashBoxController::class)
            ->parameter('cash-boxes', 'cashBox')
            ->middlewareFor('index', 'permission:view,settings.cash_boxes')
            ->middlewareFor('show', 'permission:view,settings.cash_boxes')
            ->middlewareFor('store', 'permission:create,settings.cash_boxes')
            ->middlewareFor('update', 'permission:edit,settings.cash_boxes')
            ->middlewareFor('destroy', 'permission:delete,settings.cash_boxes');

        Route::get('cashbox-logos', [CashBoxLogoController::class, 'index'])->middleware('permission:view,settings.cash_boxes');
        Route::post('cashbox-logos', [CashBoxLogoController::class, 'store'])->middleware('permission:create,settings.cash_boxes');
        Route::patch('cashbox-logos/{cashboxLogo}', [CashBoxLogoController::class, 'update'])
            ->middleware('permission:edit,settings.cash_boxes')
            ->whereNumber('cashboxLogo');
        Route::delete('cashbox-logos/{cashboxLogo}', [CashBoxLogoController::class, 'destroy'])
            ->middleware('permission:delete,settings.cash_boxes')
            ->whereNumber('cashboxLogo');
        Route::apiResource('companies', CompanyController::class)
            ->middlewareFor('index', 'permission:view,settings.companies')
            ->middlewareFor('show', 'permission:view,settings.companies')
            ->middlewareFor('store', 'permission:create,settings.companies')
            ->middlewareFor('update', 'permission:edit,settings.companies')
            ->middlewareFor('destroy', 'permission:delete,settings.companies');

        Route::apiResource('spending-funds', SpendingFundController::class)
            ->parameter('spending-funds', 'spendingFund')
            ->middlewareFor('index', 'permission:view,settings.spending_funds')
            ->middlewareFor('show', 'permission:view,settings.spending_funds')
            ->middlewareFor('store', 'permission:create,settings.spending_funds')
            ->middlewareFor('update', 'permission:edit,settings.spending_funds')
            ->middlewareFor('destroy', 'permission:delete,settings.spending_funds');

        Route::apiResource('spending-items', SpendingItemController::class)
            ->parameter('spending-items', 'spendingItem')
            ->middlewareFor('index', 'permission:view,settings.spending_items')
            ->middlewareFor('show', 'permission:view,settings.spending_items')
            ->middlewareFor('store', 'permission:create,settings.spending_items')
            ->middlewareFor('update', 'permission:edit,settings.spending_items')
            ->middlewareFor('destroy', 'permission:delete,settings.spending_items');

        Route::get('users', [UserLookupController::class, 'index'])->middleware('permission:view,settings.roles');

        Route::apiResource('payroll-rules', PayrollRuleController::class)
            ->middlewareFor('index', 'permission:view,settings.payroll')
            ->middlewareFor('show', 'permission:view,settings.payroll')
            ->middlewareFor('store', 'permission:create,settings.payroll')
            ->middlewareFor('update', 'permission:edit,settings.payroll')
            ->middlewareFor('destroy', 'permission:delete,settings.payroll');

        Route::get('payroll-accruals', [PayrollAccrualController::class, 'index'])->middleware('permission:view,payroll');
        Route::get('payroll-payouts', [PayrollPayoutController::class, 'index'])->middleware('permission:view,payroll');
        Route::post('payroll-payouts', [PayrollPayoutController::class, 'store'])->middleware('permission:create,payroll');
        Route::delete('payroll-payouts/{payout}', [PayrollPayoutController::class, 'destroy'])
            ->middleware('permission:delete,payroll')
            ->whereNumber('payout');
        Route::get('margin', [MarginSettingsController::class, 'show'])->middleware('permission:view,settings.margin');
        Route::put('margin', [MarginSettingsController::class, 'update'])->middleware('permission:edit,settings.margin');
        Route::get('cities', [CityController::class, 'index'])->middleware('permission:view,settings.cities');
        Route::get('cities-districts', [CityDistrictController::class, 'index'])->middleware('permission:view,settings.districts');

        Route::apiResource('sale-types', SaleTypeController::class)
            ->middlewareFor('index', 'permission:view,settings.sale_types')
            ->middlewareFor('show', 'permission:view,settings.sale_types')
            ->middlewareFor('store', 'permission:create,settings.sale_types')
            ->middlewareFor('update', 'permission:edit,settings.sale_types')
            ->middlewareFor('destroy', 'permission:delete,settings.sale_types');

        Route::get('tenants', [TenantController::class, 'index'])->middleware('permission:view,settings.companies');
        Route::get('roles-permissions', [RolesPermissionsController::class, 'index'])->middleware('permission:view,settings.roles');
        Route::patch('roles-permissions/roles/{role}', [RolesPermissionsController::class, 'updateRole'])
            ->middleware('permission:edit,settings.roles')
            ->whereNumber('role');
        Route::patch('roles-permissions/users/{user}', [RolesPermissionsController::class, 'updateUserRoles'])
            ->middleware('permission:edit,settings.roles')
            ->whereNumber('user');
    });

    Route::prefix('products')->group(function (): void {
        Route::get('/', [ProductController::class, 'index'])->middleware('permission:view,products');
        Route::get('pricebook/export', [PricebookController::class, 'export'])->middleware('permission:export,pricebook');
        Route::get('pricebook/template', [PricebookController::class, 'template'])->middleware('permission:export,pricebook');
        Route::post('pricebook/import', [PricebookController::class, 'import'])->middleware('permission:create,pricebook');
        Route::get('categories', [ProductCategoryController::class, 'index'])->middleware('permission:view,products');
        Route::get('subcategories', [ProductSubcategoryController::class, 'index'])->middleware('permission:view,products');
        Route::get('brands', [ProductBrandController::class, 'index'])->middleware('permission:view,products');
        Route::get('kinds', [ProductKindController::class, 'index'])->middleware('permission:view,products');
        Route::get('types', [ProductTypeController::class, 'index'])->middleware('permission:view,products');
        Route::get('{product}', [ProductController::class, 'show'])->middleware('permission:view,products');
        Route::patch('{product}', [ProductController::class, 'update'])->middleware('permission:edit,products');
    });

    Route::prefix('estimates')->group(function (): void {
        Route::get('/', [EstimateController::class, 'index'])->middleware('permission:view,estimates');
        Route::post('/', [EstimateController::class, 'store'])->middleware('permission:create,estimates');
        Route::get('{estimate}', [EstimateController::class, 'show'])->middleware('permission:view,estimates');
        Route::patch('{estimate}', [EstimateController::class, 'update'])->middleware('permission:edit,estimates');
        Route::delete('{estimate}', [EstimateController::class, 'destroy'])->middleware('permission:delete,estimates');
        Route::post('{estimate}/contracts', [EstimateContractController::class, 'store'])->middleware('permission:create,contracts');
        Route::post('{estimate}/apply-template', [EstimateTemplateController::class, 'applyTemplate'])->middleware('permission:edit,estimates');
        Route::post('{estimate}/items', [EstimateItemController::class, 'store'])->middleware('permission:edit,estimates');
        Route::patch('{estimate}/items/{item}', [EstimateItemController::class, 'update'])->middleware('permission:edit,estimates');
        Route::post('{estimate}/revoke-public', [EstimateController::class, 'revokePublic'])->middleware('permission:edit,estimates');
    });

    Route::prefix('estimate-templates')->group(function (): void {
        Route::get('materials', [EstimateTemplateMaterialController::class, 'index'])->middleware('permission:view,estimate_templates');
        Route::post('materials', [EstimateTemplateMaterialController::class, 'store'])->middleware('permission:create,estimate_templates');
        Route::get('materials/{template}', [EstimateTemplateMaterialController::class, 'show'])->middleware('permission:view,estimate_templates');
        Route::patch('materials/{template}', [EstimateTemplateMaterialController::class, 'update'])->middleware('permission:edit,estimate_templates');
        Route::delete('materials/{template}', [EstimateTemplateMaterialController::class, 'destroy'])->middleware('permission:delete,estimate_templates');

        Route::get('septiks', [EstimateTemplateSeptikController::class, 'index'])->middleware('permission:view,estimate_templates');
        Route::post('septiks', [EstimateTemplateSeptikController::class, 'store'])->middleware('permission:create,estimate_templates');
        Route::get('septiks/{template}', [EstimateTemplateSeptikController::class, 'show'])->middleware('permission:view,estimate_templates');
        Route::patch('septiks/{template}', [EstimateTemplateSeptikController::class, 'update'])->middleware('permission:edit,estimate_templates');
        Route::delete('septiks/{template}', [EstimateTemplateSeptikController::class, 'destroy'])->middleware('permission:delete,estimate_templates');
    });

    Route::prefix('knowledge')->group(function (): void {
        Route::get('articles', [KnowledgeArticleController::class, 'index'])->middleware('permission:view,knowledge');
        Route::post('articles', [KnowledgeArticleController::class, 'store'])->middleware('permission:create,knowledge');
        Route::get('articles/{article}', [KnowledgeArticleController::class, 'show'])->whereNumber('article')->middleware('permission:view,knowledge');
        Route::patch('articles/{article}', [KnowledgeArticleController::class, 'update'])->whereNumber('article')->middleware('permission:edit,knowledge');
        Route::delete('articles/{article}', [KnowledgeArticleController::class, 'destroy'])->whereNumber('article')->middleware('permission:delete,knowledge');

        Route::get('topics', [KnowledgeTopicController::class, 'index'])->middleware('permission:view,knowledge');
        Route::post('topics', [KnowledgeTopicController::class, 'store'])->middleware('permission:create,knowledge');

        Route::get('tags', [KnowledgeTagController::class, 'index'])->middleware('permission:view,knowledge');
        Route::post('tags', [KnowledgeTagController::class, 'store'])->middleware('permission:create,knowledge');

        Route::post('articles/{article}/attachments', [KnowledgeAttachmentController::class, 'store'])
            ->whereNumber('article')
            ->middleware('permission:edit,knowledge');
        Route::delete('attachments/{attachment}', [KnowledgeAttachmentController::class, 'destroy'])
            ->whereNumber('attachment')
            ->middleware('permission:delete,knowledge');
        Route::get('attachments/{attachment}/download', [KnowledgeAttachmentController::class, 'download'])
            ->whereNumber('attachment')
            ->middleware('permission:view,knowledge');
    });

    Route::get('contracts', [ContractController::class, 'index'])->middleware('permission:view,contracts');
    Route::patch('contracts/{contract}', [ContractController::class, 'update'])->whereNumber('contract')->middleware('permission:edit,contracts');
    Route::patch('contracts/{contract}/status', [ContractController::class, 'updateStatus'])->middleware('permission:edit,contracts');
    Route::get('contracts/status-history', [ContractStatusHistoryController::class, 'index'])->middleware('permission:view,contracts');
    Route::get('contracts/{contract}/history', [ContractHistoryController::class, 'index'])->whereNumber('contract')->middleware('permission:view,contracts');
    Route::get('contracts/{contract}', [ContractController::class, 'show'])->whereNumber('contract')->middleware('permission:view,contracts');
    Route::get('contracts/{contract}/analysis', [ContractController::class, 'analysis'])->whereNumber('contract')->middleware('permission:view,contracts');
    Route::delete('contracts/{contract}', [ContractController::class, 'destroy'])->whereNumber('contract')->middleware('permission:delete,contracts');
    Route::get('contracts/{contract}/payroll', [ContractPayrollController::class, 'index'])->whereNumber('contract')->middleware('permission:view,payroll');
    Route::post('contracts/{contract}/payroll/manual', [ContractPayrollController::class, 'storeManual'])->whereNumber('contract')->middleware('permission:create,payroll');
    Route::post('contracts/{contract}/payroll/recalculate', [ContractPayrollController::class, 'recalc'])->whereNumber('contract')->middleware('permission:edit,payroll');
    Route::get('contracts/{contract}/documents', [ContractDocumentController::class, 'index'])->whereNumber('contract')->middleware('permission:view,contracts');
    Route::post('contracts/{contract}/documents', [ContractDocumentController::class, 'store'])->whereNumber('contract')->middleware('permission:create,contracts');
    Route::delete('contracts/{contract}/documents/{document}', [ContractDocumentController::class, 'destroy'])
        ->whereNumber('contract')
        ->whereNumber('document')
        ->middleware('permission:delete,contracts');
    Route::get('contracts/{contract}/documents/{document}/download', [ContractDocumentController::class, 'download'])
        ->whereNumber('contract')
        ->whereNumber('document')
        ->middleware('permission:view,contracts');
    Route::get('contract-templates/files', [ContractTemplateFileController::class, 'index'])->middleware('permission:view,contract_templates');
    Route::post('contract-templates/files', [ContractTemplateFileController::class, 'store'])->middleware('permission:create,contract_templates');
    Route::apiResource('contract-templates', ContractTemplateController::class)
        ->middlewareFor('index', 'permission:view,contract_templates')
        ->middlewareFor('show', 'permission:view,contract_templates')
        ->middlewareFor('store', 'permission:create,contract_templates')
        ->middlewareFor('update', 'permission:edit,contract_templates')
        ->middlewareFor('destroy', 'permission:delete,contract_templates');

    Route::get('installations', [InstallationController::class, 'index'])->middleware('permission:view,installations');
    Route::patch('installations/{contract}', [InstallationController::class, 'update'])
        ->whereNumber('contract')
        ->middleware('permission:assign,installations');

    // Financial Reports - Read-Only Endpoints (CEO-level, finance permission required)
    Route::prefix('reports')->name('reports.')->middleware('permission:view,finance')->group(function () {
        Route::get('cashflow/daily', [ReportsController::class, 'cashflowDaily'])->name('cashflow.daily');
        Route::get('cashflow/monthly-summary', [ReportsController::class, 'cashflowMonthlySummary'])->name('cashflow.monthly-summary');
        Route::get('pnl/monthly', [ReportsController::class, 'pnlMonthly'])->name('pnl.monthly');
        Route::get('debts/daily', [ReportsController::class, 'debtsDaily'])->name('debts.daily');
        Route::get('debts/summary', [ReportsController::class, 'debtsSummary'])->name('debts.summary');
    });
});
