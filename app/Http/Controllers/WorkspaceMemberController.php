<?php

namespace App\Http\Controllers;

use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WorkspaceMemberController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:workspaces.manage', only: ['store', 'destroy']),
            // or: new Middleware('permission:members.manage', only: ['store','destroy']),
        ];
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->ensureUserBelongsToWorkspace($request, $workspace);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:80'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        // Ensure requested role exists
        $role = Role::where('name', $data['role'])->firstOrFail();

        // Find or create user
        $plainPassword = null;

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            $plainPassword = 'KB-' . Str::random(12);

            $user = User::create([
                'name' => $data['name'] ?: Str::before($data['email'], '@'),
                'email' => $data['email'],
                'password' => Hash::make($plainPassword),
                'email_verified_at' => now(),
            ]);
        }

        // Attach to workspace (no duplicates)
        $workspace->users()->syncWithoutDetaching([$user->id]);

        // Set Spatie team/workspace context BEFORE role assignment
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $workspace->id);

        // Assign role scoped to this workspace
        // Use syncRoles to ensure single role per workspace (optional SaaS rule)
        $user->syncRoles([$role->name]);

        // optional: send welcome email only when newly created
        if ($plainPassword) {
            SendWelcomeEmailJob::dispatch($user, $plainPassword, $request->user());
        }

        return back()->with('success', 'Member invited to workspace successfully.');
    }

    public function destroy(Request $request, Workspace $workspace, User $user)
    {
        $this->ensureUserBelongsToWorkspace($request, $workspace);

        // prevent removing workspace owner (optional rule)
        if ((int) $workspace->owner_id === (int) $user->id) {
            return back()->with('error', 'You cannot remove the workspace owner.');
        }

        // prevent removing yourself (optional)
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'You cannot remove yourself from the workspace.');
        }

        // Detach membership
        $workspace->users()->detach($user->id);

        // Optional: also remove roles for this workspace
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $workspace->id);
        $user->syncRoles([]); // removes roles for this workspace context

        return back()->with('success', 'Member removed from workspace.');
    }

    private function ensureUserBelongsToWorkspace(Request $request, Workspace $workspace): void
    {
        $ok = $request->user()
            ->workspaces()
            ->where('workspaces.id', $workspace->id)
            ->exists();

        abort_unless($ok, 403);
    }
}