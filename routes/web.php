<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Documents (Super Admin only)
    Route::middleware(['role:super_admin'])->group(function () {
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');

        Route::get('/tracking', function () {
            return view('tracking.index');
        })->name('tracking.index');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');

        Route::get('/users', function () {
            return view('users.index');
        })->name('users.index');
    });

    Route::get('/documents/{id}/editor', [DocumentController::class, 'editor'])->name('documents.editor');
    Route::put('/documents/{id}/content', [DocumentController::class, 'updateContent'])->name('documents.updateContent');
    Route::put('/documents/{id}/status', [DocumentController::class, 'updateStatus'])->name('documents.updateStatus');
    Route::post('/documents/{id}/comments', [DocumentController::class, 'storeComment'])->name('documents.comments.store');
    Route::post('/documents/{id}/sign', [DocumentController::class, 'signDocument'])->name('documents.sign');
});

require __DIR__.'/auth.php';
