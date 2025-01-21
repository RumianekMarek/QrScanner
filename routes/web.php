<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\FairController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ScannerController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Auth/Login', [
            'canLogin' => Route::has('login'),
        ]);
    });
});


Route::middleware('auth')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
    
    Route::get('/scanner/{mode?}', [ScannerController::class, 'create'])->name('scanner.create');
    Route::post('/scanner', [ScannerController::class, 'store'])->name('scanner.store');
    Route::get('/scanner/list/{id}', [ScannerController::class, 'list'])->name('scanner.list');
    Route::post('/scanner/{id}/download', [ScannerController::class, 'download'])->name('scanner.download');
    Route::post('/scanner/send', [ScannerController::class, 'send'])->name('scanner.send');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/register', function(){
        return Inertia::render('Auth/Register');
    })->name('admin.register');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'update'])->name('users.update');
    Route::get('/users/{id}/details', [UserController::class, 'details'])->name('users.details');
    Route::post('/users/{id}/details', [UserController::class, 'store'])->name('users.details.store');
    Route::post('/users/{id}/details/status/{status}', [UserController::class, 'status'])->name('users.details.status');
    Route::post('/users/token/{id}', [UserController::class, 'token'])->name('users.token');
    
    Route::get('/fairs', [FairController::class, 'index'])->name('fairs.index');
    Route::post('/fairs', [FairController::class, 'store'])->name('fairs.store');
});

Route::post('/merge-pdf', [PdfController::class, 'mergePdf'])->name('pdf.mergePdf');
Route::get('/merge-pdf-form', function () {
    return view('merge-pdf');
})->name('pdf.mergePdfForm');

require __DIR__.'/auth.php';