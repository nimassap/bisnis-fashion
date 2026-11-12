<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\CashbookExportController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\MediaController;
use App\Livewire\Budgets\CollectionBudgetPage;
use App\Livewire\Cashbook\CashbookIndex;
use App\Livewire\Collections\CollectionShow;
use App\Livewire\Collections\CollectionsIndex;
use App\Livewire\Dashboard\DashboardPage;
use App\Livewire\Fabrics\FabricsIndex;
use App\Livewire\Payments\PaymentsIndex;
use App\Livewire\Phases\PhaseShow;
use App\Livewire\Pricing\CollectionPricing;
use App\Livewire\Pricing\PricingIndex;
use App\Livewire\Products\ProductShow;
use App\Livewire\Products\ProductsIndex;
use App\Livewire\Promotions\PromotionsIndex;
use App\Livewire\Sales\SalesIndex;
use App\Livewire\Settings\AppearancePage;
use App\Livewire\Settings\BusinessPage;
use App\Livewire\Settings\ProfilePage;
use App\Livewire\Vendors\VendorsIndex;
use Illuminate\Support\Facades\Route;

// Public: a phone reads it before anyone signs in, to offer the install.
Route::get('manifest.webmanifest', ManifestController::class)->name('manifest');

Route::middleware('guest')->group(function (): void {
    Route::get('setup', [SetupController::class, 'create'])->name('setup');
    Route::post('setup', [SetupController::class, 'store'])->middleware('throttle:5,1');

    Route::middleware('setup.complete')->group(function (): void {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/dashboard');
    Route::get('dashboard', DashboardPage::class)->name('dashboard');

    // The brand phase, done once for the whole business.
    Route::get('brand', PhaseShow::class)->name('brand.show');

    // Collections: one couple set per production run, each with its own phases.
    Route::get('collections', CollectionsIndex::class)->name('collections.index');
    Route::get('collections/{collection}', CollectionShow::class)->whereNumber('collection')->name('collections.show');
    Route::get('collections/{collection}/phases/{key}', PhaseShow::class)->whereNumber('collection')->name('collections.phase');

    // The women's and men's model of each couple set.
    Route::get('products', ProductsIndex::class)->name('products.index');
    Route::get('products/{product}', ProductShow::class)->whereNumber('product')->name('products.show');

    // HPP and selling prices: shared settings, then per couple set.
    Route::get('pricing', PricingIndex::class)->name('pricing.index');
    Route::get('collections/{collection}/pricing', CollectionPricing::class)->whereNumber('collection')->name('collections.pricing');

    // Money in and out, budgets per collection, and payments owed to vendors.
    Route::get('cashbook', CashbookIndex::class)->name('cashbook.index');
    Route::get('cashbook/export/{format}', CashbookExportController::class)->name('cashbook.export');
    Route::get('payments', PaymentsIndex::class)->name('payments.index');
    Route::get('collections/{collection}/budget', CollectionBudgetPage::class)->whereNumber('collection')->name('collections.budget');

    // Orders, stock and what sold; campaigns and the content calendar.
    Route::get('sales', SalesIndex::class)->name('sales.index');
    Route::get('promotions', PromotionsIndex::class)->name('promotions.index');

    Route::get('fabrics', FabricsIndex::class)->name('fabrics.index');
    Route::get('vendors', VendorsIndex::class)->name('vendors.index');

    // Photos and swatches, served only to the business they belong to.
    Route::get('media/{path}', MediaController::class)->where('path', '.*')->name('media.show');

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::redirect('/', '/settings/profile');
        Route::get('profile', ProfilePage::class)->name('profile');
        Route::get('business', BusinessPage::class)->name('business');
        Route::get('appearance', AppearancePage::class)->name('appearance');
    });
});
