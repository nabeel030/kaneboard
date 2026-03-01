<?php

namespace App\Http\Controllers;

use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MemberController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:members.view', only: ['index']),
            new Middleware('permission:members.create', only: ['store']),
            new Middleware('permission:members.update', only: ['update']),
            new Middleware('permission:members.delete', only: ['destroy']),
            // OR you can simplify writes:
            // new Middleware('permission:members.manage', only: ['store','update','destroy']),
        ];
    }

    public function index(Request $request)
    {
        $workspace = $this->currentWorkspaceOrFail($request);
        $this->setTeamContext($workspace->id);

        // Members ONLY inside current workspace
        $members = $workspace->users()
            ->orderBy('users.created_at', 'desc')
            ->get(['users.id', 'users.name', 'users.email', 'users.created_at'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at,
                // ✅ team-scoped roles (workspace_id)
                'roles' => $u->getRoleNames()->values(),
            ])
            ->values();

        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
            ->values();

        return Inertia::render('Members/Index', [
            'members' => $members,
            'roles' => $roles,
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'owner_id' => $workspace->owner_id,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->currentWorkspaceOrFail($request);
        $this->setTeamContext($workspace->id);

        $data = $request->validate([
            'name' => ['nullable','string','max:80'],
            'email' => ['required','email','max:255'],
            'password' => ['nullable','string','min:8'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $plainPassword = null;

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            // Create user if not exists
            $plainPassword = $data['password'] ?? ('KB-' . Str::random(12));

            $user = User::create([
                'name' => $data['name'] ?: Str::before($data['email'], '@'),
                'email' => $data['email'],
                'password' => Hash::make($plainPassword),
                'email_verified_at' => now(),
            ]);
        } else {
            // If user exists and password was provided, optionally update it
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                $user->save();
            }

            // If name provided, optionally update it
            if (!empty($data['name'])) {
                $user->name = $data['name'];
                $user->save();
            }
        }

        // Attach to workspace
        $workspace->users()->syncWithoutDetaching([$user->id]);

        // Assign workspace-scoped role(s)
        $user->syncRoles($data['roles'] ?? []);

        // Only send welcome email if newly created
        if ($plainPassword) {
            SendWelcomeEmailJob::dispatch($user, $plainPassword, $request->user());
        }

        return back()->with('success', 'Member added to workspace successfully.');
    }

    public function update(Request $request, User $user)
    {
        $workspace = $this->currentWorkspaceOrFail($request);
        $this->setTeamContext($workspace->id);

        // Must be a member of current workspace
        abort_unless($this->isWorkspaceMember($workspace, $user->id), 403);

        $data = $request->validate([
            'name' => ['required','string','max:80'],
            'email' => ['required','email','max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable','string','min:8'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        // Update roles within this workspace
        $user->syncRoles($data['roles'] ?? []);

        return back()->with('success', 'Member updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        $workspace = $this->currentWorkspaceOrFail($request);
        $this->setTeamContext($workspace->id);

        // Must be a member of current workspace
        abort_unless($this->isWorkspaceMember($workspace, $user->id), 403);

        // ✅ Safety rules
        if ((int) $workspace->owner_id === (int) $user->id) {
            return back()->with('error', 'You cannot remove the workspace owner.');
        }

        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'You cannot remove yourself from the workspace.');
        }

        // Prevent removing last Admin/Owner in this workspace
        if ($this->isCriticalRole($user, $workspace->id) && $this->criticalRoleCount($workspace->id) <= 1) {
            return back()->with('error', 'You cannot remove the last Admin/Owner from the workspace.');
        }

        // Detach membership (do NOT delete user globally)
        $workspace->users()->detach($user->id);

        // Remove roles for this workspace context (optional but recommended)
        $user->syncRoles([]);

        return back()->with('success', 'Member removed from workspace.');
    }

    // -------------------------
    // Helpers
    // -------------------------

    private function currentWorkspaceOrFail(Request $request): Workspace
    {
        $id = (int) session('current_workspace_id');

        abort_unless($id > 0, 403);

        $workspace = $request->user()
            ->workspaces()
            ->where('workspaces.id', $id)
            ->first();

        abort_unless($workspace, 403);

        return $workspace;
    }

    private function setTeamContext(int $workspaceId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($workspaceId);
    }

    private function isWorkspaceMember(Workspace $workspace, int $userId): bool
    {
        return $workspace->users()->where('users.id', $userId)->exists();
    }

    private function isCriticalRole(User $user, int $workspaceId): bool
    {
        $this->setTeamContext($workspaceId);
        $roles = $user->getRoleNames();
        return $roles->contains('Owner') || $roles->contains('Admin');
    }

    private function criticalRoleCount(int $workspaceId): int
    {
        // Count users that have Admin/Owner in this workspace (team-aware pivot)
        return DB::table('model_has_roles as mhr')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.workspace_id', $workspaceId)
            ->whereIn('r.name', ['Owner', 'Admin'])
            ->distinct('mhr.model_id')
            ->count('mhr.model_id');
    }
}