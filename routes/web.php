<?php

use App\Http\Controllers\App\CashRegisterController;
use App\Http\Controllers\App\CreditReceivableController;
use App\Http\Controllers\App\CustomerController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\EmployeeController;
use App\Http\Controllers\App\FinanceController;
use App\Http\Controllers\App\PaymentController;
use App\Http\Controllers\App\ServiceController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\App\VehicleController;
use App\Http\Controllers\App\WashHistoryController;
use App\Http\Controllers\App\WashKanbanController;
use App\Http\Controllers\App\WashLocationMapController;
use App\Http\Controllers\App\WashNotificationController;
use App\Http\Controllers\App\WashOrderController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PublicWashTrackingController;
use App\Models\User;
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
    Route::get('mapa', WashLocationMapController::class)->name('locations.map');
    Route::get('historico', [WashHistoryController::class, 'index'])->name('history.index');
    Route::get('historico/exportar', [WashHistoryController::class, 'export'])->name('history.export');

    Route::middleware('role:'.User::ROLE_ADMIN.','.User::ROLE_ATTENDANT)->group(function () {
        Route::get('lavagens/create', [WashOrderController::class, 'create'])->name('wash-orders.create');
        Route::post('lavagens', [WashOrderController::class, 'store'])->name('wash-orders.store');
        Route::post('lavagens/{wash_order}/pagamentos', [PaymentController::class, 'store'])->name('payments.store');

        Route::resource('clientes', CustomerController::class)->parameters(['clientes' => 'customer'])->names('customers')->except(['show', 'destroy']);
        Route::resource('veiculos', VehicleController::class)->parameters(['veiculos' => 'vehicle'])->names('vehicles')->except(['show', 'destroy']);
    });

    Route::get('lavagens', [WashOrderController::class, 'index'])->name('wash-orders.index');
    Route::get('lavagens/{wash_order}', [WashOrderController::class, 'show'])->name('wash-orders.show');

    Route::patch('lavagens/{wash_order}/status', [WashOrderController::class, 'updateStatus'])
        ->middleware('role:'.User::ROLE_ADMIN.','.User::ROLE_OPERATOR)
        ->name('wash-orders.update-status');

    Route::middleware('role:'.User::ROLE_ADMIN.','.User::ROLE_ATTENDANT.','.User::ROLE_OPERATOR)->group(function () {
        Route::post('lavagens/{wash_order}/notificacoes/whatsapp-manual', [WashNotificationController::class, 'store'])
            ->name('wash-orders.notifications.whatsapp-manual.store');
        Route::patch('lavagens/{wash_order}/notificacoes/{notification}/enviada-manualmente', [WashNotificationController::class, 'markAsSent'])
            ->name('wash-orders.notifications.mark-as-sent');
    });

    Route::middleware('role:'.User::ROLE_ADMIN)->group(function () {
        Route::get('financeiro', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('financeiro/exportar', [FinanceController::class, 'export'])->name('finance.export');
        Route::get('financeiro/caixa', [CashRegisterController::class, 'index'])->name('finance.cash-registers.index');
        Route::post('financeiro/caixa', [CashRegisterController::class, 'store'])->name('finance.cash-registers.store');
        Route::post('financeiro/caixa/{cashRegister}/movimentacoes', [CashRegisterController::class, 'movement'])->name('finance.cash-registers.movements.store');
        Route::patch('financeiro/caixa/{cashRegister}/fechar', [CashRegisterController::class, 'close'])->name('finance.cash-registers.close');
        Route::get('financeiro/fiado', [CreditReceivableController::class, 'index'])->name('finance.credit-receivables.index');
        Route::patch('financeiro/fiado/{washOrder}/receber', [CreditReceivableController::class, 'receive'])->name('finance.credit-receivables.receive');
        Route::get('configuracoes', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('configuracoes', [SettingsController::class, 'update'])->name('settings.update');
        Route::resource('servicos', ServiceController::class)->parameters(['servicos' => 'service'])->names('services')->except(['show', 'destroy']);
        Route::resource('funcionarios', EmployeeController::class)->parameters(['funcionarios' => 'employee'])->names('employees')->except(['show', 'destroy']);
    });
});
