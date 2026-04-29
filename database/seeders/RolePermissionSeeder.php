<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // Document permissions
            'view documents',
            'create documents',
            'edit documents',
            'delete documents',
            'approve documents',
            'sign documents',

            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Reports
            'view reports',
            'export reports',

            // Tracking
            'view tracking',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $unitPengusul = Role::firstOrCreate(['name' => 'unit_pengusul']);
        $unitPengusul->givePermissionTo([
            'view documents',
            'edit documents',
            'sign documents',
        ]);

        $client = Role::firstOrCreate(['name' => 'client']);
        $client->givePermissionTo([
            'view documents',
            'edit documents',
            'sign documents',
        ]);

        // Create default users
        $admin = User::firstOrCreate(
            ['email' => 'admin@univ.ac.id'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('super_admin');

        $unitTI = User::firstOrCreate(
            ['email' => 'unit_ti@univ.ac.id'],
            [
                'name' => 'Unit TI',
                'password' => bcrypt('password'),
            ]
        );
        $unitTI->assignRole('unit_pengusul');

        $ptTech = User::firstOrCreate(
            ['email' => 'pt_tech@mitra.com'],
            [
                'name' => 'PT Teknologi Maju',
                'password' => bcrypt('password'),
            ]
        );
        $ptTech->assignRole('client');

        $ptRSJ = User::firstOrCreate(
            ['email' => 'pt_rsj@mitra.com'],
            [
                'name' => 'PT RSJ Kota Malang',
                'password' => bcrypt('password'),
            ]
        );
        $ptRSJ->assignRole('client');
    }
}
