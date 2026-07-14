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
            self::VIEW_WASH_ORDERS => 'Visualizar detalhes da lavagem',
            self::CREATE_WASH_ORDER => 'Abrir e listar lavagens',
            self::SEND_WASH_NOTIFICATIONS => 'Enviar notificacoes manuais ao cliente',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function permissionDescriptions(): array
    {
        return [
            self::VIEW_WASH_ORDERS => 'Permite entrar na tela da lavagem pelo Kanban ou link interno.',
            self::CREATE_WASH_ORDER => 'Permite acessar a listagem e cadastrar novas lavagens.',
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
