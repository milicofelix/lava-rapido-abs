<?php

namespace App\Support\Access;

use App\Models\RolePermissionSetting;
use App\Models\User;

class AccessControl
{
    public const ACCESS_PRODUCT_ADMIN = 'product.admin.access';
    public const VIEW_DASHBOARD = 'dashboard.view';
    public const VIEW_KANBAN = 'kanban.view';
    public const VIEW_WASH_ORDERS = 'wash-orders.view';
    public const CREATE_WASH_ORDER = 'wash-orders.create';
    public const UPDATE_WASH_ORDER_STATUS = 'wash-orders.update-status';
    public const VIEW_OPERATIONAL_HISTORY = 'history.view';
    public const VIEW_SCHEDULE = 'schedule.view';
    public const MANAGE_CUSTOMERS = 'customers.manage';
    public const MANAGE_VEHICLES = 'vehicles.manage';
    public const MANAGE_SERVICES = 'services.manage';
    public const MANAGE_EMPLOYEES = 'employees.manage';
    public const VIEW_AUDIT_LOGS = 'audit-logs.view';
    public const VIEW_FINANCE = 'finance.view';
    public const REGISTER_PAYMENT = 'payments.register';
    public const MANAGE_CASH_REGISTER = 'cash-registers.manage';
    public const MANAGE_CREDIT_RECEIVABLES = 'credit-receivables.manage';
    public const MANAGE_SETTINGS = 'settings.manage';
    public const MANAGE_SUBSCRIPTION = 'subscription.manage';
    public const SEND_WASH_NOTIFICATIONS = 'wash-notifications.send';

    /**
     * @return array<string, array<int, string>>
     */
    public static function rolePermissions(): array
    {
        return [
            User::ROLE_SUPER_ADMIN => [
                self::ACCESS_PRODUCT_ADMIN,
                self::VIEW_DASHBOARD,
            ],
            User::ROLE_OWNER => [
                self::VIEW_DASHBOARD,
                self::VIEW_KANBAN,
                self::VIEW_WASH_ORDERS,
                self::CREATE_WASH_ORDER,
                self::UPDATE_WASH_ORDER_STATUS,
                self::VIEW_OPERATIONAL_HISTORY,
                self::VIEW_SCHEDULE,
                self::MANAGE_CUSTOMERS,
                self::MANAGE_VEHICLES,
                self::MANAGE_SERVICES,
                self::MANAGE_EMPLOYEES,
                self::VIEW_AUDIT_LOGS,
                self::VIEW_FINANCE,
                self::REGISTER_PAYMENT,
                self::MANAGE_CASH_REGISTER,
                self::MANAGE_CREDIT_RECEIVABLES,
                self::MANAGE_SETTINGS,
                self::MANAGE_SUBSCRIPTION,
                self::SEND_WASH_NOTIFICATIONS,
            ],
            User::ROLE_ADMIN => [
                self::VIEW_DASHBOARD,
                self::VIEW_KANBAN,
                self::VIEW_WASH_ORDERS,
                self::CREATE_WASH_ORDER,
                self::UPDATE_WASH_ORDER_STATUS,
                self::VIEW_OPERATIONAL_HISTORY,
                self::VIEW_SCHEDULE,
                self::MANAGE_CUSTOMERS,
                self::MANAGE_VEHICLES,
                self::MANAGE_SERVICES,
                self::MANAGE_EMPLOYEES,
                self::VIEW_AUDIT_LOGS,
                self::VIEW_FINANCE,
                self::REGISTER_PAYMENT,
                self::MANAGE_CASH_REGISTER,
                self::MANAGE_CREDIT_RECEIVABLES,
                self::MANAGE_SETTINGS,
                self::SEND_WASH_NOTIFICATIONS,
            ],
            User::ROLE_ATTENDANT => [
                self::VIEW_DASHBOARD,
                self::VIEW_KANBAN,
                self::VIEW_WASH_ORDERS,
                self::CREATE_WASH_ORDER,
                self::VIEW_OPERATIONAL_HISTORY,
                self::VIEW_SCHEDULE,
                self::MANAGE_CUSTOMERS,
                self::MANAGE_VEHICLES,
                self::REGISTER_PAYMENT,
                self::SEND_WASH_NOTIFICATIONS,
            ],
            User::ROLE_OPERATOR => [
                self::VIEW_KANBAN,
                self::UPDATE_WASH_ORDER_STATUS,
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function configurableRolePermissions(): array
    {
        return [
            User::ROLE_OPERATOR => [
                self::VIEW_WASH_ORDERS,
                self::CREATE_WASH_ORDER,
                self::SEND_WASH_NOTIFICATIONS,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function permissionLabels(): array
    {
        return [
            self::ACCESS_PRODUCT_ADMIN => 'Acessar Admin Produto',
            self::VIEW_DASHBOARD => 'Visualizar painel',
            self::VIEW_KANBAN => 'Visualizar Kanban',
            self::VIEW_WASH_ORDERS => 'Visualizar detalhes da lavagem',
            self::CREATE_WASH_ORDER => 'Abrir e listar lavagens',
            self::UPDATE_WASH_ORDER_STATUS => 'Avançar status da lavagem',
            self::VIEW_OPERATIONAL_HISTORY => 'Visualizar histórico operacional',
            self::VIEW_SCHEDULE => 'Visualizar agenda',
            self::MANAGE_CUSTOMERS => 'Gerenciar clientes',
            self::MANAGE_VEHICLES => 'Gerenciar veículos',
            self::MANAGE_SERVICES => 'Gerenciar serviços',
            self::MANAGE_EMPLOYEES => 'Gerenciar equipe',
            self::VIEW_AUDIT_LOGS => 'Visualizar auditoria',
            self::VIEW_FINANCE => 'Visualizar financeiro',
            self::REGISTER_PAYMENT => 'Registrar pagamentos',
            self::MANAGE_CASH_REGISTER => 'Gerenciar caixa',
            self::MANAGE_CREDIT_RECEIVABLES => 'Gerenciar fiado',
            self::MANAGE_SETTINGS => 'Gerenciar configurações',
            self::MANAGE_SUBSCRIPTION => 'Gerenciar assinatura',
            self::SEND_WASH_NOTIFICATIONS => 'Enviar notificações manuais ao cliente',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function permissionDescriptions(): array
    {
        return [
            self::ACCESS_PRODUCT_ADMIN => 'Permite acessar solicitações, unidades e planos do produto.',
            self::VIEW_DASHBOARD => 'Permite enxergar indicadores operacionais e executivos.',
            self::VIEW_KANBAN => 'Permite acompanhar o fluxo de lavagens no quadro.',
            self::VIEW_WASH_ORDERS => 'Permite entrar na tela da lavagem pelo Kanban ou link interno.',
            self::CREATE_WASH_ORDER => 'Permite acessar a listagem e cadastrar novas lavagens.',
            self::UPDATE_WASH_ORDER_STATUS => 'Permite avançar etapas da lavagem conforme regras de equipe e pagamento.',
            self::VIEW_OPERATIONAL_HISTORY => 'Permite consultar histórico operacional e exportações.',
            self::VIEW_SCHEDULE => 'Permite visualizar lavagens agendadas quando o módulo Agenda está habilitado.',
            self::MANAGE_CUSTOMERS => 'Permite criar e editar clientes e acessar fidelidade do cliente.',
            self::MANAGE_VEHICLES => 'Permite criar e editar veículos vinculados aos clientes.',
            self::MANAGE_SERVICES => 'Permite criar e editar serviços da unidade.',
            self::MANAGE_EMPLOYEES => 'Permite criar, editar e desativar colaboradores.',
            self::VIEW_AUDIT_LOGS => 'Permite consultar ações registradas pelos usuários da unidade.',
            self::VIEW_FINANCE => 'Permite visualizar indicadores e relatórios financeiros.',
            self::REGISTER_PAYMENT => 'Permite registrar recebimentos nas lavagens.',
            self::MANAGE_CASH_REGISTER => 'Permite abrir, movimentar e fechar caixa.',
            self::MANAGE_CREDIT_RECEIVABLES => 'Permite consultar e baixar contas em fiado.',
            self::MANAGE_SETTINGS => 'Permite alterar perfil, módulos, tema, fidelidade e permissões da unidade.',
            self::MANAGE_SUBSCRIPTION => 'Permite acessar assinatura e escolher plano.',
            self::SEND_WASH_NOTIFICATIONS => 'Permite usar os modelos de WhatsApp na tela da lavagem.',
        ];
    }

    public static function allows(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if (self::isConfigurableForRole($user->role, $permission) && $user->wash_location_id) {
            $override = RolePermissionSetting::allowedFor((int) $user->wash_location_id, $user->role, $permission);

            if ($override !== null) {
                return $override;
            }
        }

        return in_array($permission, self::rolePermissions()[$user->role] ?? [], true);
    }

    /**
     * @return array<int, string>
     */
    public static function effectivePermissionsFor(User $user): array
    {
        $permissions = self::basePermissionsForRole($user->role);

        foreach (self::configurablePermissionsForRole($user->role) as $permission) {
            if (self::allows($user, $permission)) {
                $permissions[] = $permission;
            }
        }

        return collect($permissions)->unique()->values()->all();
    }

    /**
     * @return array<int, string>
     */
    public static function basePermissionsForRole(string $role): array
    {
        return self::rolePermissions()[$role] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function configurablePermissionsForRole(string $role): array
    {
        return self::configurableRolePermissions()[$role] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function enabledConfigurablePermissionsFor(User $user): array
    {
        return collect(self::configurablePermissionsForRole($user->role))
            ->filter(fn (string $permission): bool => self::allows($user, $permission))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function blockedConfigurablePermissionsFor(User $user): array
    {
        return collect(self::configurablePermissionsForRole($user->role))
            ->reject(fn (string $permission): bool => self::allows($user, $permission))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function rolesFor(string $permission): array
    {
        return collect(self::rolePermissions())
            ->filter(fn (array $permissions) => in_array($permission, $permissions, true))
            ->keys()
            ->values()
            ->all();
    }

    private static function isConfigurableForRole(string $role, string $permission): bool
    {
        return in_array($permission, self::configurableRolePermissions()[$role] ?? [], true);
    }
}
