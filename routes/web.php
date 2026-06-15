<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\AdminDashboard;
use App\Livewire\Admin\Config\SettingsPage;
use App\Livewire\Admin\HistoryPage;
use App\Livewire\Admin\RecommendationsPage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/', AdminDashboard::class)->name('admin.index');
        Route::get('/dashboard', AdminDashboard::class)->name('admin.dashboard');
        Route::get('/einstellungen', SettingsPage::class)->name('admin.settings');
        Route::get('/historie', HistoryPage::class)->name('admin.history');
        Route::get('/empfehlungen', RecommendationsPage::class)->name('admin.recommendations');
    });
});
