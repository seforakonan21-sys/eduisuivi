<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/connexion');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'show'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')   // 5 tentatives par minute max
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');

    // Exemple de zone réservée : sert aussi à tester le middleware de rôle
    Route::view('/administration', 'administration')
        ->middleware('role:admin_general')
        ->name('administration');
});