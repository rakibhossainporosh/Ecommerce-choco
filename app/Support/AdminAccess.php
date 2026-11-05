<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminAccess
{
    /**
     * Built-in roles stay in place. Admin access is granted in AppServiceProvider, not by this matrix.
     *
     * @var list<string>
     */
    public const SYSTEM_ROLES = ['Admin', 'Manager', 'Staff'];

    /**
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    public static function permissionGroups(): array
    {
        $labels = self::moduleLabels();

        return self::assignablePermissions()
            ->groupBy(fn (string $name): string => strstr($name, '.', true) ?: $name)
            ->map(function (Collection $names, string $prefix) use ($labels): array {
                return [
                    'label' => $labels[$prefix] ?? str($prefix)->replace('-', ' ')->headline()->toString(),
                    'options' => $names
                        ->mapWithKeys(fn (string $name): array => [$name => self::actionLabel($name)])
                        ->all(),
                ];
            })
            ->all();
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    public static function onlyAssignable(array $names): array
    {
        $allowed = array_flip(self::assignablePermissions()->all());

        $selected = [];

        foreach ($names as $name) {
            if (isset($allowed[$name])) {
                $selected[] = $name;
            }
        }

        return array_values(array_unique($selected));
    }

    public static function collect(mixed $groups): array
    {
        if (! is_array($groups)) {
            return [];
        }

        $names = [];

        $walk = function (array $items) use (&$names, &$walk): void {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $walk($item);

                    continue;
                }

                if (is_string($item)) {
                    $names[] = $item;
                }
            }
        };

        $walk($groups);

        return self::onlyAssignable($names);
    }

    public static function stateKey(string $prefix): string
    {
        return str_replace('-', '_', $prefix);
    }

    public static function permissionLabel(string $permission): string
    {
        $prefix = strstr($permission, '.', true) ?: $permission;
        $labels = self::moduleLabels();
        $module = $labels[$prefix] ?? str($prefix)->replace('-', ' ')->headline()->toString();

        return $module.' · '.self::actionLabel($permission);
    }

    public static function roleIsDeletable(Role $role): bool
    {
        if (in_array($role->name, self::SYSTEM_ROLES, true)) {
            return false;
        }

        $assigned = array_key_exists('users_count', $role->getAttributes())
            ? (int) $role->users_count
            : $role->users()->count();

        return $assigned === 0;
    }

    public static function userIsDeletable(User $user): bool
    {
        if ($user->is(auth()->user())) {
            return false;
        }

        if ($user->hasRole('Admin') && User::role('Admin')->count() <= 1) {
            return false;
        }

        return true;
    }

    public static function actionLabel(string $permission): string
    {
        $action = str_contains($permission, '.')
            ? substr($permission, (int) strpos($permission, '.') + 1)
            : $permission;

        return match ($action) {
            'view' => UiText::line('View'),
            'create' => UiText::line('Create'),
            'update' => UiText::line('Edit'),
            'delete' => UiText::line('Delete'),
            'cancel' => UiText::line('Cancel'),
            'refund' => UiText::line('Refund'),
            'adjust' => UiText::line('Adjust'),
            'history' => UiText::line('History'),
            'confirm' => UiText::line('Confirm'),
            'process' => UiText::line('Process'),
            'ship' => UiText::line('Ship'),
            'deliver' => UiText::line('Deliver'),
            default => str($action)->headline()->toString(),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function moduleLabels(): array
    {
        return [
            'dashboard' => UiText::line('Dashboard'),
            'categories' => UiText::line('Categories'),
            'brands' => UiText::line('Brands'),
            'units' => UiText::line('Units'),
            'products' => UiText::line('Products'),
            'customers' => UiText::line('Customers'),
            'orders' => UiText::line('Orders'),
            'inventory' => UiText::line('Inventory'),
            'reports' => UiText::line('Reports'),
            'payments' => UiText::line('Payments'),
            'shipping' => UiText::line('Shipping'),
            'couriers' => UiText::line('Couriers'),
            'courier-shipments' => UiText::line('Courier shipments'),
            'coupons' => UiText::line('Coupons'),
            'coupon-usages' => UiText::line('Coupon usage'),
            'reviews' => UiText::line('Reviews'),
            'returns' => UiText::line('Returns'),
            'settings' => UiText::line('Settings'),
            'activities' => UiText::line('Activities'),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public static function assignablePermissions(): Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'not like', 'roles.%')
            ->where('name', 'not like', 'users.%')
            ->orderBy('name')
            ->pluck('name');
    }
}
