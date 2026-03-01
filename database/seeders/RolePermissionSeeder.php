<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
// ✅ Use Spatie Role unless you have a custom Role model extending it
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // ✅ All permissions (SaaS-style)
        $permissions = [
            // Workspace / account
            'workspaces.view',
            'workspaces.manage',

            // Members
            'members.view',
            'members.create',
            'members.update',
            'members.delete',
            'members.manage', // optional shortcut if you want it

            // Roles & Permissions management
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view', // optional (if you ever show permissions list)

            // Projects
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',

            // Tickets
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'tickets.delete',
            'tickets.assign',

            // Time logs
            'timelogs.view',
            'timelogs.manage',

            // Board (optional, but useful)
            'board.view',
            'board.reorder',
        ];

        // ✅ Create or update permissions (guard included)
        foreach ($permissions as $name) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // ✅ Create roles
        $owner  = Role::updateOrCreate(['name' => 'Owner'],  ['guard_name' => 'web']);
        $admin  = Role::updateOrCreate(['name' => 'Admin'],  ['guard_name' => 'web']);
        $member = Role::updateOrCreate(['name' => 'Member'], ['guard_name' => 'web']);
        $viewer = Role::updateOrCreate(['name' => 'Viewer'], ['guard_name' => 'web']);

        // ✅ Owner/Admin get ALL permissions
        $owner->syncPermissions($permissions);
        $admin->syncPermissions($permissions);

        // ✅ Member permissions (adjust as you like)
        $member->syncPermissions([
            'workspaces.view',
            'projects.view',
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'timelogs.view',
            'timelogs.manage',
            'board.view',
        ]);

        // ✅ Viewer permissions
        $viewer->syncPermissions([
            'workspaces.view',
            'projects.view',
            'tickets.view',
            'timelogs.view',
            'board.view',
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'], 
            [
                'name' => 'System Admin',
                'password' => 'Admin12#',
                'email_verified_at' => now(),
            ]
        );

        $workspace = Workspace::firstOrCreate(
            ['name' => 'Main Workspace'],
            [
            'name' => 'Main Workspace',
            'owner_id' => $admin->id,
            ]
        );
        
        $workspace->users()->syncWithoutDetaching([$admin->id]);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);

        $admin->assignRole('Admin');
    }
}