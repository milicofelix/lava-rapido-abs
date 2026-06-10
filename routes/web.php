<?php

use App\Http\Controllers\App\CustomerController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\ServiceController;
use App\Http\Controllers\App\VehicleController;
use App\Http\Controllers\App\WashKanbanController;
use App\Http\Controllers\App\WashOrderController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PublicWashTrackingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::get('/lavagens/acompanhamento/{code}', PublicWashTrackingController::class)->name('tracking.show');
Route::get('/lavagens/acompanhamento/{code}/feed', [PublicWashTrackingController::class, 'feed'])->name('tracking.feed');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('kanban', WashKanbanController::class)->name('kanban');
    Route::get('kanban/feed', [WashKanbanController::class, 'feed'])->name('kanban.feed');
    Route::patch('lavagens/{wash_order}/status', [WashOrderController::class, 'updateStatus'])->name('wash-orders.update-status');
    Route::resource('lavagens', WashOrderController::class)->parameters(['lavagens' => 'wash_order'])->names('wash-orders')->only(['index', 'create', 'store', 'show']);
    Route::resource('clientes', CustomerController::class)->parameters(['clientes' => 'customer'])->names('customers')->except(['show', 'destroy']);
    Route::resource('veiculos', VehicleController::class)->parameters(['veiculos' => 'vehicle'])->names('vehicles')->except(['show', 'destroy']);
    Route::resource('servicos', ServiceController::class)->parameters(['servicos' => 'service'])->names('services')->except(['show', 'destroy']);
});
