<?php

use App\Http\Controllers\App\CashRegisterController;
use App\Http\Controllers\App\CancelLoyaltyCouponController;
use App\Http\Controllers\App\CreditReceivableController;
use App\Http\Controllers\App\CustomerController;
use App\Http\Controllers\App\AuditLogController;
use App\Http\Controllers\App\ApplyLoyaltyCouponController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\EmployeeController;
use App\Http\Controllers\App\FinanceController;
use App\Http\Controllers\App\LoyaltyCouponController;
use App\Http\Controllers\App\LoyaltyReportController;
use App\Http\Controllers\App\OwnerSubscriptionController;
use App\Http\Controllers\App\PaymentController;
use App\Http\Controllers\App\RemoveLoyaltyCouponController;
use App\Http\Controllers\App\ScheduleController;
use App\Http\Controllers\App\ServiceController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\App\SubscriptionBlockedController;
use App\Http\Controllers\App\SuperAdmin\PlanController as SuperAdminPlanController;
use App\Http\Controllers\App\SuperAdmin\WashLocationManagementController as SuperAdminWashLocationManagementController;
use App\Http\Controllers\App\SuperAdmin\WashLocationRequestController as SuperAdminWashLocationRequestController;
use App\Http\Controllers\App\VehicleController;
use App\Http\Controllers\App\WashHistoryController;
use App\Http\Controllers\App\WashKanbanController;
use App\Http\Controllers\App\WashNotificationController;
use App\Http\Controllers\App\WashOrderController;
use App\Http\Controllers\App\WashOrderReceiptController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\PublicWashLocationMapController;
use App\Http\Controllers\PublicWashLocationRequestController;
use App\Http\Controllers\PublicWashTrackingController;
use App\Models\User;
use App\Support\Access\AccessControl;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/lava-rapidos');
Route::get('/quero-cadastrar-meu-lava-rapido', [PublicWashLocationRequestController::class, 'create'])->name('public.location-requests.create');
Route::post('/quero-cadastrar-meu-lava-rapido', [PublicWashLocationRequestController::class, 'store'])->name('public.location-requests.store');
Route::get('/cadastro-enviado', [PublicWashLocationRequestController::class, 'thankYou'])->name('public.location-requests.thank-you');
Route::get('/lava-rapidos', PublicWashLocationMapController::class)->name('public.locations.index');
Route::get('/lava-rapidos/{location:slug}', [PublicWashLocationMapController::class, 'show'])->name('public.locations.show');
Route::redirect('/unidades', '/lava-rapidos');
Route::get('/lavagens/acompanhamento/{code}', PublicWashTrackingController::class)->name('tracking.show');
Route::get('/lavagens/acompanhamento/{code}/feed', [PublicWashTrackingController::class, 'feed'])->name('tracking.feed');
Route::post('/webhooks/mercado-pago', MercadoPagoWebhookController::class)->name('webhooks.mercado-pago');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/assinatura/bloqueada', SubscriptionBlockedController::class)->name('subscription.blocked');

    Route::middleware('role:'.User::ROLE_SUPER_ADMIN)->prefix('admin-produto')->name('super-admin.')->group(function () {
        Route::get('solicitacoes-lava-rapidos', [SuperAdminWashLocationRequestController::class, 'index'])->name('location-requests.index');
        Route::get('solicitacoes-lava-rapidos/{locationRequest}', [SuperAdminWashLocationRequestController::class, 'show'])->name('location-requests.show');
        Route::post('solicitacoes-lava-rapidos/{locationRequest}/geocodificar', [SuperAdminWashLocationRequestController::class, 'geocode'])->name('location-requests.geocode');
        Route::patch('solicitacoes-lava-rapidos/{locationRequest}/aprovar', [SuperAdminWashLocationRequestController::class, 'approve'])->name('location-requests.approve');
        Route::patch('solicitacoes-lava-rapidos/{locationRequest}/rejeitar', [SuperAdminWashLocationRequestController::class, 'reject'])->name('location-requests.reject');

        Route::get('unidades', [SuperAdminWashLocationManagementController::class, 'index'])->name('locations.index');
        Route::patch('unidades/{washLocation:id}/prorrogar-trial', [SuperAdminWashLocationManagementController::class, 'extendTrial'])->name('locations.extend-trial');
        Route::patch('unidades/{washLocation:id}/ativar-assinatura', [SuperAdminWashLocationManagementController::class, 'activateSubscription'])->name('locations.activate-subscription');
        Route::patch('unidades/{washLocation:id}/suspender', [SuperAdminWashLocationManagementController::class, 'suspend'])->name('locations.suspend');
        Route::patch('unidades/{washLocation:id}/reativar', [SuperAdminWashLocationManagementController::class, 'reactivate'])->name('locations.reactivate');

        Route::get('planos', [SuperAdminPlanController::class, 'index'])->name('plans.index');
        Route::post('planos', [SuperAdminPlanController::class, 'store'])->name('plans.store');
        Route::put('planos/{plan}', [SuperAdminPlanController::class, 'update'])->name('plans.update');
        Route::patch('planos/{plan}/desativar', [SuperAdminPlanController::class, 'deactivate'])->name('plans.deactivate');
    });

    Route::middleware('role:'.User::ROLE_OWNER)->group(function () {
        Route::get('configuracoes/assinatura', [OwnerSubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::post('configuracoes/assinatura/escolher-plano', [OwnerSubscriptionController::class, 'choose'])->name('subscriptions.choose');
        Route::patch('configuracoes/assinatura/cancelar-pendente', [OwnerSubscriptionController::class, 'cancelPending'])->name('subscriptions.cancel-pending');
    });

    Route::middleware('active.subscription')->group(function () {
        Route::get('/dashboard', DashboardController::class)
            ->middleware('permission:'.AccessControl::VIEW_DASHBOARD)
            ->name('dashboard');
        Route::get('kanban', WashKanbanController::class)
            ->middleware('permission:'.AccessControl::VIEW_KANBAN)
            ->name('kanban');
        Route::get('kanban/feed', [WashKanbanController::class, 'feed'])
            ->middleware('permission:'.AccessControl::VIEW_KANBAN)
            ->name('kanban.feed');
        Route::get('historico', [WashHistoryController::class, 'index'])
            ->middleware('permission:'.AccessControl::VIEW_OPERATIONAL_HISTORY)
            ->name('history.index');
        Route::get('historico/exportar', [WashHistoryController::class, 'export'])
            ->middleware('permission:'.AccessControl::VIEW_OPERATIONAL_HISTORY)
            ->name('history.export');
        Route::get('agenda', ScheduleController::class)
            ->middleware('permission:'.AccessControl::VIEW_SCHEDULE)
            ->name('schedule.index');

        Route::middleware('permission:'.AccessControl::CREATE_WASH_ORDER)->group(function () {
            Route::get('lavagens/create', [WashOrderController::class, 'create'])->name('wash-orders.create');
            Route::post('lavagens', [WashOrderController::class, 'store'])->name('wash-orders.store');
        });

        Route::middleware('permission:'.AccessControl::REGISTER_PAYMENT)->group(function () {
            Route::post('lavagens/{wash_order}/pagamentos', [PaymentController::class, 'store'])->name('payments.store');
            Route::post('lavagens/{wash_order}/cupom-fidelidade', ApplyLoyaltyCouponController::class)->name('wash-orders.loyalty-coupons.apply');
            Route::delete('lavagens/{wash_order}/cupom-fidelidade', RemoveLoyaltyCouponController::class)->name('wash-orders.loyalty-coupons.remove');
        });

        Route::middleware('permission:'.AccessControl::MANAGE_CUSTOMERS)->group(function () {
            Route::get('fidelidade', [LoyaltyReportController::class, 'index'])->name('loyalty-reports.index');
            Route::get('fidelidade/exportar', [LoyaltyReportController::class, 'export'])->name('loyalty-reports.export');
            Route::resource('clientes', CustomerController::class)->parameters(['clientes' => 'customer'])->names('customers')->except(['show', 'destroy']);
            Route::get('cupons-fidelidade/{loyaltyCoupon}', LoyaltyCouponController::class)->name('loyalty-coupons.show');
            Route::patch('cupons-fidelidade/{loyaltyCoupon}/cancelar', CancelLoyaltyCouponController::class)->name('loyalty-coupons.cancel');
        });

        Route::middleware('permission:'.AccessControl::MANAGE_VEHICLES)->group(function () {
            Route::resource('veiculos', VehicleController::class)->parameters(['veiculos' => 'vehicle'])->names('vehicles')->except(['show', 'destroy']);
        });

        Route::get('lavagens', [WashOrderController::class, 'index'])
            ->middleware('permission:'.AccessControl::CREATE_WASH_ORDER)
            ->name('wash-orders.index');
        Route::get('lavagens/{wash_order}', [WashOrderController::class, 'show'])
            ->middleware('permission:'.AccessControl::VIEW_WASH_ORDERS)
            ->name('wash-orders.show');
        Route::get('lavagens/{wash_order}/recibo', WashOrderReceiptController::class)
            ->middleware('permission:'.AccessControl::REGISTER_PAYMENT)
            ->name('wash-orders.receipt');

        Route::patch('lavagens/{wash_order}/status', [WashOrderController::class, 'updateStatus'])
            ->middleware('permission:'.AccessControl::UPDATE_WASH_ORDER_STATUS)
            ->name('wash-orders.update-status');

        Route::middleware('permission:'.AccessControl::SEND_WASH_NOTIFICATIONS)->group(function () {
            Route::post('lavagens/{wash_order}/notificacoes/whatsapp-manual', [WashNotificationController::class, 'store'])
                ->name('wash-orders.notifications.whatsapp-manual.store');
            Route::patch('lavagens/{wash_order}/notificacoes/{notification}/enviada-manualmente', [WashNotificationController::class, 'markAsSent'])
                ->name('wash-orders.notifications.mark-as-sent');
        });

        Route::middleware('permission:'.AccessControl::VIEW_AUDIT_LOGS)->group(function () {
            Route::get('auditoria', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });

        Route::middleware('permission:'.AccessControl::VIEW_FINANCE)->group(function () {
            Route::get('financeiro', [FinanceController::class, 'index'])->name('finance.index');
            Route::get('financeiro/exportar', [FinanceController::class, 'export'])->name('finance.export');
        });

        Route::middleware('permission:'.AccessControl::MANAGE_CASH_REGISTER)->group(function () {
            Route::get('financeiro/caixa', [CashRegisterController::class, 'index'])->name('finance.cash-registers.index');
            Route::post('financeiro/caixa', [CashRegisterController::class, 'store'])->name('finance.cash-registers.store');
            Route::post('financeiro/caixa/{cashRegister}/movimentacoes', [CashRegisterController::class, 'movement'])->name('finance.cash-registers.movements.store');
            Route::patch('financeiro/caixa/{cashRegister}/fechar', [CashRegisterController::class, 'close'])->name('finance.cash-registers.close');
        });

        Route::middleware('permission:'.AccessControl::MANAGE_CREDIT_RECEIVABLES)->group(function () {
            Route::get('financeiro/fiado', [CreditReceivableController::class, 'index'])->name('finance.credit-receivables.index');
            Route::patch('financeiro/fiado/{washOrder}/receber', [CreditReceivableController::class, 'receive'])->name('finance.credit-receivables.receive');
        });

        Route::middleware('permission:'.AccessControl::MANAGE_SETTINGS)->group(function () {
            Route::get('configuracoes', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('configuracoes', [SettingsController::class, 'update'])->name('settings.update');
        });

        Route::middleware('permission:'.AccessControl::MANAGE_SERVICES)->group(function () {
            Route::resource('servicos', ServiceController::class)->parameters(['servicos' => 'service'])->names('services')->except(['show', 'destroy']);
        });

        Route::middleware('permission:'.AccessControl::MANAGE_EMPLOYEES)->group(function () {
            Route::resource('equipe', EmployeeController::class)->parameters(['equipe' => 'employee'])->names('employees')->except(['show']);
        });
    });
});
