<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;

Route::get('/test-pdf-export', [ReportController::class, 'exportPdf']);

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {

    // Documents - accessible by all authenticated users
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');

    // Documents (Super Admin only)
    Route::middleware(['role:super_admin'])->group(function () {
        Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::put('/documents/{id}/dates', [DocumentController::class, 'updateDates'])->name('documents.updateDates');
        Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    });

    Route::get('/reports/export-pdf', [App\Http\Controllers\ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('/reports/export-excel', [App\Http\Controllers\ReportController::class, 'exportExcel'])->name('reports.export-excel');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Search (must be before {id} wildcard)
    Route::get('/documents/search', [DocumentController::class, 'search'])->name('documents.search');

    Route::get('/documents/{id}/editor', [DocumentController::class, 'editor'])->name('documents.editor');
    Route::get('/documents/{id}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{id}/export-pdf', [DocumentController::class, 'exportPdf'])->name('documents.export-pdf');
    Route::put('/documents/{id}/content', [DocumentController::class, 'updateContent'])->name('documents.updateContent');
    Route::put('/documents/{id}/status', [DocumentController::class, 'updateStatus'])->name('documents.updateStatus');
    Route::post('/documents/{id}/comments', [DocumentController::class, 'storeComment'])->name('documents.comments.store');
    Route::post('/documents/{id}/comments/{commentId}/resolve', [DocumentController::class, 'resolveComment'])->name('documents.comments.resolve');
    Route::post('/documents/{id}/sign', [DocumentController::class, 'signDocument'])->name('documents.sign');
    Route::post('/documents/{id}/reject', [DocumentController::class, 'rejectDocument'])->name('documents.reject');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');


    Route::get('/test-viewer', function (Illuminate\Http\Request $request) {
        $docUrl = $request->session()->get('viewer_url');
        return view('test-viewer', compact('docUrl'));
    })->name('test-viewer');

    Route::post('/test-viewer/upload', function (Illuminate\Http\Request $request) {
        $request->validate(['docx_file' => 'required|file|mimes:docx']);
        
        $file = $request->file('docx_file');
        $apiUrl = config('services.gotenberg.url');

        try {
            $gotenbergRequest = Gotenberg::libreOffice($apiUrl)
                ->convert(Stream::path($file->getRealPath()));
            
            $response = Gotenberg::send($gotenbergRequest);
            $pdfContent = $response->getBody()->getContents();
            
            // Simpan PDF sementara
            $pdfName = 'preview_' . time() . '.pdf';
            Storage::disk('public')->put('temp_viewer/' . $pdfName, $pdfContent);
            
            $url = asset('storage/temp_viewer/' . $pdfName);
            return redirect()->route('test-viewer')->with('viewer_url', $url);
        } catch (\Exception $e) {
            return redirect()->route('test-viewer')->withErrors(['docx_file' => 'Gagal konversi PDF: ' . $e->getMessage() . '. Pastikan Gotenberg API berjalan di ' . $apiUrl]);
        }
    })->name('test-viewer.upload');
});

require __DIR__.'/auth.php';
