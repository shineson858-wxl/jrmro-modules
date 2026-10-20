<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\DealingsController;
use Webkul\Admin\Http\Controllers\Catalog\AttributeController;
use Webkul\Admin\Http\Controllers\Catalog\AttributeFamilyController;
use Webkul\Admin\Http\Controllers\Catalog\CategoryController;
use Webkul\Admin\Http\Controllers\Catalog\Product\BundleController;
use Webkul\Admin\Http\Controllers\Catalog\Product\ConfigurableController;
use Webkul\Admin\Http\Controllers\Catalog\Product\DownloadableController;
use Webkul\Admin\Http\Controllers\Catalog\Product\GroupedController;
use Webkul\Admin\Http\Controllers\Catalog\Product\SimpleController;
use Webkul\Admin\Http\Controllers\Catalog\Product\VirtualController;
use Webkul\Admin\Http\Controllers\Catalog\ProductController;
use Webkul\Admin\Http\Controllers\Catalog\ProductImportController;
use Webkul\Admin\Http\Controllers\Catalog\RfqController;
use Webkul\Admin\Http\Controllers\Catalog\QuoteController;
use Webkul\Admin\Http\Controllers\Catalog\OrderController;
use Webkul\Admin\Http\Controllers\Customers\CompanyController;

/**
 * Catalog routes.
 */

/**
 * 单据中心概览（顶级菜单首页）.
 */
Route::get('dealings', [DealingsController::class, 'index'])->name('admin.dealings.index');

Route::prefix('catalog')->group(function () {
    /**
     * Attributes routes.
     */
    Route::controller(AttributeController::class)->prefix('attributes')->group(function () {
        Route::get('', 'index')->name('admin.catalog.attributes.index');

        Route::get('{id}/options', 'getAttributeOptions')->name('admin.catalog.attributes.options');

        Route::get('create', 'create')->name('admin.catalog.attributes.create');

        Route::post('create', 'store')->name('admin.catalog.attributes.store');

        Route::get('edit/{id}', 'edit')->name('admin.catalog.attributes.edit');

        Route::put('edit/{id}', 'update')->name('admin.catalog.attributes.update');

        Route::delete('edit/{id}', 'destroy')->name('admin.catalog.attributes.delete');

        Route::post('mass-delete', 'massDestroy')->name('admin.catalog.attributes.mass_delete');
    });

    /**
     * Attribute families routes.
     */
    Route::controller(AttributeFamilyController::class)->prefix('families')->group(function () {
        Route::get('', 'index')->name('admin.catalog.families.index');

        Route::get('create', 'create')->name('admin.catalog.families.create');

        Route::post('create', 'store')->name('admin.catalog.families.store');

        Route::get('edit/{id}', 'edit')->name('admin.catalog.families.edit');

        Route::put('edit/{id}', 'update')->name('admin.catalog.families.update');

        Route::delete('edit/{id}', 'destroy')->name('admin.catalog.families.delete');
    });

    /**
     * Categories routes.
     */
    Route::controller(CategoryController::class)->prefix('categories')->group(function () {
        Route::get('', 'index')->name('admin.catalog.categories.index');

        Route::get('create', 'create')->name('admin.catalog.categories.create');

        Route::post('create', 'store')->name('admin.catalog.categories.store');

        Route::get('edit/{id}', 'edit')->name('admin.catalog.categories.edit');

        Route::put('edit/{id}', 'update')->name('admin.catalog.categories.update');

        Route::delete('edit/{id}', 'destroy')->name('admin.catalog.categories.delete');

        Route::post('mass-delete', 'massDestroy')->name('admin.catalog.categories.mass_delete');

        Route::post('mass-update', 'massUpdate')->name('admin.catalog.categories.mass_update');

        Route::get('search', 'search')->name('admin.catalog.categories.search');

        Route::get('tree', 'tree')->name('admin.catalog.categories.tree');
    });

    /**
     * Sync route.
     */
    Route::get('/sync', [ProductController::class, 'sync']);

    /**
     * Products routes.
     */
    Route::controller(ProductController::class)->prefix('products')->group(function () {
        Route::get('', 'index')->name('admin.catalog.products.index');

        Route::get('import', [ProductImportController::class, 'showImport'])->name('admin.catalog.products.import');

        Route::post('import', [ProductImportController::class, 'import'])->name('admin.catalog.products.import');

        Route::post('create', 'store')->name('admin.catalog.products.store');

        Route::post('copy/{id}', 'copy')->name('admin.catalog.products.copy');

        Route::get('edit/{id}', 'edit')->name('admin.catalog.products.edit');

        Route::put('edit/{id}', 'update')->name('admin.catalog.products.update');

        Route::delete('edit/{id}', 'destroy')->name('admin.catalog.products.delete');

        Route::put('edit/{id}/inventories', 'updateInventories')->name('admin.catalog.products.update_inventories');

        Route::post('upload-file/{id}', 'uploadLink')->name('admin.catalog.products.upload_link');

        Route::post('upload-sample/{id}', 'uploadSample')->name('admin.catalog.products.upload_sample');

        Route::post('mass-update', 'massUpdate')->name('admin.catalog.products.mass_update');

        Route::post('mass-delete', 'massDestroy')->name('admin.catalog.products.mass_delete');

        Route::post('toggle-status/{id}', 'toggleStatus')->name('admin.catalog.products.toggle-status');

        Route::post('mass-update-price', 'massUpdatePrice')->name('admin.catalog.products.mass_update_price');

        Route::post('mass-update-stock', 'massUpdateStock')->name('admin.catalog.products.mass_update_stock');

        Route::post('mass-update-category', 'massUpdateCategory')->name('admin.catalog.products.mass_update_category');

        Route::post('mass-copy', 'massCopy')->name('admin.catalog.products.mass_copy');

        Route::get('quick-edit/{id}', 'quickEdit')->name('admin.catalog.products.quick-edit');

        Route::post('quick-edit-update/{id}', 'quickEditUpdate')->name('admin.catalog.products.quick-edit-update');

        Route::match(['get', 'post'], 'batch-image-upload', 'batchImageUpload')->name('admin.catalog.products.batch-image-upload');

        Route::get('image-library', 'imageLibrary')->name('admin.catalog.products.image-library');

        Route::post('image-library-delete/{id}', 'imageLibraryDelete')->name('admin.catalog.products.image-library-delete');

        Route::post('image-library-mass-delete', 'imageLibraryMassDelete')->name('admin.catalog.products.image-library-mass-delete');

        Route::get('import-history', 'importHistory')->name('admin.catalog.products.import-history');
        Route::get('manual-maintain', 'manualMaintain')->name('admin.catalog.products.manual-maintain');
        Route::post('manual-maintain-store', 'manualMaintainStore')->name('admin.catalog.products.manual-maintain-store');

        Route::controller(SimpleController::class)->group(function () {
            Route::get('{id}/simple-customizable-options', 'customizableOptions')->name('admin.catalog.products.simple.customizable-options');
        });

        Route::controller(ConfigurableController::class)->group(function () {
            Route::get('{id}/configurable-options', 'options')->name('admin.catalog.products.configurable.options');
        });

        Route::controller(BundleController::class)->group(function () {
            Route::get('{id}/bundle-options', 'options')->name('admin.catalog.products.bundle.options');
        });

        Route::controller(GroupedController::class)->group(function () {
            Route::get('{id}/grouped-options', 'options')->name('admin.catalog.products.grouped.options');
        });

        Route::controller(DownloadableController::class)->group(function () {
            Route::get('{id}/downloadable-options', 'options')->name('admin.catalog.products.downloadable.options');
        });

        Route::controller(VirtualController::class)->group(function () {
            Route::get('{id}/virtual-customizable-options', 'customizableOptions')->name('admin.catalog.products.virtual.customizable-options');
        });

        Route::get('search', 'search')->name('admin.catalog.products.search');

        Route::get('{id}/{attribute_id}', 'download')->name('admin.catalog.products.file.download');
    });

    /**
     * RFQ 询价报价管理.
     */
    Route::controller(RfqController::class)->prefix('rfq')->name('admin.catalog.rfq.')->group(function () {
        Route::get('', 'index')->name('index');

        Route::get('{id}/edit', 'edit')->name('edit');

        Route::post('{id}/quote', 'quote')->name('quote');

        Route::post('bulk-quote', 'bulkQuote')->name('bulk-quote');
    });
});

/**
 * MRO 报价单（一级功能）。
 */
Route::prefix('quotes')->group(function () {
    Route::controller(QuoteController::class)->name('admin.quotes.')->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('store-defaults', 'storeDefaults')->name('store-defaults');

        Route::get('{id}/edit', 'edit')->name('edit');

        Route::post('{id}/update', 'update')->name('update');

        Route::get('{id}/print', 'printQuote')->name('print');

        Route::get('{id}/export-excel', 'exportExcel')->name('export-excel');

        Route::get('{id}/export-pdf', 'exportPdf')->name('export-pdf');

        Route::post('{id}/send-email', 'sendEmail')->name('send-email');
        Route::post('{id}/send', 'sendQuote')->name('send');
        Route::post('{id}/confirm', 'confirmQuote')->name('confirm');
        Route::post('{id}/cancel', 'cancelQuote')->name('cancel');
        Route::post('{id}/expire', 'expireQuote')->name('expire');

        Route::post('{id}/negotiate', 'negotiate')->name('negotiate');
    });
});

/**
 * 快速报价工具（K3）.
 */
Route::prefix('quotes/quick')->name('admin.quotes.quick.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\QuickQuoteController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('generate', 'generate')->name('generate');
    });
});

Route::prefix('orders')->group(function () {
    Route::controller(OrderController::class)->name('admin.orders.')->group(function () {
        Route::get('', 'index')->name('index');

        Route::get('{id}/edit', 'edit')->name('edit');

        Route::post('{id}/sign', 'sign')->name('sign');

        Route::post('{id}/sign-customer', 'signCustomer')->name('sign-customer');

        Route::post('{id}/complete', 'complete')->name('complete');

        Route::post('{id}/cancel', 'cancel')->name('cancel');

        Route::post('{id}/verify', 'verify')->name('verify');

        Route::post('{id}/billing', 'billing')->name('billing');
    });
});

/**
 * 配送 / 自提点（F1/F2）.
 */
Route::prefix('pickups')->name('admin.catalog.pickups.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\PickupPointController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('', 'store')->name('store');

        Route::post('{id}', 'update')->name('update');

        Route::delete('{id}', 'destroy')->name('destroy');
    });
});

/**
 * 分级协议价（H2）.
 */
Route::prefix('price-tiers')->name('admin.catalog.price-tiers.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\PriceTierController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('', 'store')->name('store');

        Route::post('{id}', 'update')->name('update');

        Route::delete('{id}', 'destroy')->name('destroy');
    });
});

/**
 * 到货通知（B4）.
 */
Route::prefix('stock-notify')->name('admin.catalog.stock-notify.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\StockNotifyController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('{id}/mark', 'markNotified')->name('mark');
    });
});

/**
 * 对账报表（J3）.
 */
Route::prefix('reports')->name('admin.catalog.reports.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\ReportController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::get('export', 'export')->name('export');
    });
});

/**
 * 客户 360° 视图（K2）.
 */
Route::prefix('customer360')->name('admin.customer360.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\Customer360Controller::class)->group(function () {
        Route::get('', 'index')->name('index');
    });
});

/**
 * 经营看板（L1）.
 */
Route::prefix('opsdashboard')->name('admin.catalog.opsdashboard.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\OpsDashboardController::class)->group(function () {
        Route::get('', 'index')->name('index');
    });
});

/**
 * 销售分析（L2）.
 */
Route::prefix('salesanalysis')->name('admin.catalog.salesanalysis.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\SalesAnalysisController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::get('export', 'export')->name('export');
    });
});

/**
 * 企业版销售合同模板（M3）.
 */
Route::prefix('terms')->name('admin.catalog.terms.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\TermsController::class)->group(function () {
        Route::get('', 'index')->name('index');
    });
});

/**
 * 商品技术资料 / 替代关联（A5·A3）.
 */
Route::prefix('productassets')->name('admin.catalog.productassets.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\ProductAssetsController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('store-doc', 'storeDoc')->name('storeDoc');

        Route::delete('doc/{id}', 'destroyDoc')->name('destroyDoc');

        Route::post('store-alt', 'storeAlt')->name('storeAlt');

        Route::delete('alt/{id}', 'destroyAlt')->name('destroyAlt');
    });
});

/**
 * 公司成员 / 多采购员（H3）.
 */
Route::prefix('companymembers')->name('admin.catalog.companymembers.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\CompanyMembersController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('store', 'store')->name('store');

        Route::delete('{id}', 'destroy')->name('destroy');
    });
});

/**
 * 进价 / 毛利（K4）.
 */
Route::prefix('productcost')->name('admin.catalog.productcost.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\ProductCostController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('update', 'update')->name('update');
    });
});

/**
 * 库存预警 / 补货建议（E4）.
 */
Route::prefix('stockalert')->name('admin.catalog.stockalert.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\StockAlertController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('update-threshold', 'updateThreshold')->name('updateThreshold');
    });
});

/**
 * 易耗品周期采购提醒（L3）.
 */
Route::prefix('replenish')->name('admin.catalog.replenish.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\ReplenishController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('update', 'update')->name('update');
    });
});

/**
 * 补货计划单（F4）.
 */
Route::prefix('replenishplan')->name('admin.catalog.replenishplan.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\ReplenishPlanController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::get('print', 'print')->name('print');
    });
});

/**
 * 上架审核（导入默认草稿，复核后上架）.
 */
Route::prefix('publishreview')->name('admin.catalog.publishreview.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\PublishReviewController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('bulk-update', 'bulkUpdate')->name('bulkUpdate');
    });
});

/**
 * 业务员管理（K1）.
 */
Route::prefix('salesrep')->name('admin.salesrep.')->group(function () {
    Route::controller(\Webkul\Admin\Http\Controllers\Catalog\SalesRepController::class)->group(function () {
        Route::get('', 'index')->name('index');

        Route::post('assign', 'assign')->name('assign');
    });
});

/**
 * 企业客户（H1）.
 */
Route::prefix('customers/companies')->group(function () {
    Route::controller(CompanyController::class)->name('admin.customers.companies.')->group(function () {
        Route::get('', 'index')->name('index');

        Route::get('{id}/edit', 'edit')->name('edit');

        Route::post('{id}/update', 'update')->name('update');

        Route::post('{id}/approve', 'approve')->name('approve');

        Route::post('{id}/reject', 'reject')->name('reject');
    });
});
