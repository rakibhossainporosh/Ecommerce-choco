<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Initial Permission Catalog (29 permissions across 9 domains)
        $permissions = [
            // Dashboard
            'dashboard.view',

            // Categories
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            // Brands
            'brands.view',
            'brands.create',
            'brands.update',
            'brands.delete',

            // Units
            'units.view',
            'units.create',
            'units.update',
            'units.delete',

            // Products
            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            // Customers
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',

            // Orders
            'orders.view',
            'orders.create',
            'orders.update',
            'orders.cancel',
            'orders.refund',

            // Inventory
            'inventory.view',
            'inventory.adjust',

            // Reports
            'reports.view',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // 2. Roles (exactly 3 roles using the 'web' guard)
        $adminRole = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        $managerRole = Role::firstOrCreate([
            'name' => 'Manager',
            'guard_name' => 'web',
        ]);

        $staffRole = Role::firstOrCreate([
            'name' => 'Staff',
            'guard_name' => 'web',
        ]);

        // 3. Manager Permissions (23 permissions - operational management, no destructive deletes or financial refunds)
        $managerPermissions = [
            'dashboard.view',

            'categories.view',
            'categories.create',
            'categories.update',

            'brands.view',
            'brands.create',
            'brands.update',

            'units.view',
            'units.create',
            'units.update',

            'products.view',
            'products.create',
            'products.update',

            'customers.view',
            'customers.create',
            'customers.update',

            'orders.view',
            'orders.create',
            'orders.update',
            'orders.cancel',

            'inventory.view',
            'inventory.adjust',

            'reports.view',
        ];

        $managerRole->syncPermissions($managerPermissions);

        // 4. Staff Permissions (9 permissions - front-line order & customer fulfillment)
        $staffPermissions = [
            'dashboard.view',

            'products.view',

            'customers.view',
            'customers.create',
            'customers.update',

            'orders.view',
            'orders.create',
            'orders.update',

            'inventory.view',
        ];

        $staffRole->syncPermissions($staffPermissions);

        // 5. Admin Permissions:
        // Admin intentionally has NO permissions assigned directly in the database.
        // Full access is granted centrally via Gate::before in AppServiceProvider.
        $adminRole->syncPermissions([]);

        // Re-flush cache after seeding to ensure application observes new permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
