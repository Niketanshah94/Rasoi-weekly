<?php

use App\Http\Controllers\App\AccountController;
use App\Http\Controllers\App\CookController;
use App\Http\Controllers\App\DishesController;
use App\Http\Controllers\App\GroceriesController;
use App\Http\Controllers\App\PlanController;
use App\Http\Controllers\Household\OnboardingController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('legal/{page}', [LegalController::class, 'show'])->whereIn('page', LegalController::PAGES)->name('legal');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware(['auth', 'verified', 'household'])->group(function () {
    // The starter kit's dashboard is now the Plan tab.
    Route::redirect('dashboard', '/plan')->name('dashboard');

    Route::get('plan', [PlanController::class, 'show'])->name('plan');
    Route::post('plan/swap', [PlanController::class, 'swap'])->name('plan.swap');
    Route::post('plan/cooked', [PlanController::class, 'cooked'])->name('plan.cooked');
    Route::put('plan/style', [PlanController::class, 'style'])->name('plan.style');
    Route::get('dishes', [DishesController::class, 'index'])->name('dishes');
    Route::get('groceries', [GroceriesController::class, 'show'])->name('groceries');
    Route::post('groceries/tick', [GroceriesController::class, 'tick'])->name('groceries.tick');
    Route::get('cook', [CookController::class, 'show'])->name('cook');
    Route::get('account', [AccountController::class, 'show'])->name('account');
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
