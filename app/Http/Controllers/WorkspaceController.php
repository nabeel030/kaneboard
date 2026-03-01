<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;

class WorkspaceController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:workspaces.view', only: ['index']),
            new Middleware('permission:workspaces.manage', only: ['store', 'update', 'destroy', 'switch']),
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $currentWorkspaceId = (int) (session('current_workspace_id') ?? 0);

        $workspaces = $user->workspaces()
            ->with('owner:id,name,email')
            ->orderBy('workspaces.created_at', 'desc')
            ->get(['workspaces.id', 'workspaces.name', 'workspaces.owner_id', 'workspaces.created_at'])
            ->map(fn($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'owner' => $w->owner ? [
                    'id' => $w->owner->id,
                    'name' => $w->owner->name,
                    'email' => $w->owner->email,
                ] : null,
                'created_at' => $w->created_at,
            ])->values();

        $members = [];
        if ($currentWorkspaceId) {
            $ws = $user->workspaces()->where('workspaces.id', $currentWorkspaceId)->first();
            if ($ws) {
                $members = $ws->users()
                    ->orderBy('users.name')
                    ->get(['users.id', 'users.name', 'users.email', 'users.created_at'])
                    ->map(fn($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'created_at' => $u->created_at,
                        // team-scoped role names will be fetched client-side from shared auth for current user,
                        // but for listing members, we can keep simple; or add role fetch later if needed
                    ])->values();
            }
        }

        $roles = Role::orderBy('name')->get(['id', 'name'])->values();

        return Inertia::render('Workspaces/Index', [
            'workspaces' => $workspaces,
            'currentWorkspaceId' => $currentWorkspaceId,
            'members' => $members,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $user = $request->user();

        $workspace = Workspace::create([
            'name' => $data['name'],
            'owner_id' => $user->id,
        ]);

        // attach creator as member
        $workspace->users()->syncWithoutDetaching([$user->id]);

        // set as current workspace
        session(['current_workspace_id' => $workspace->id]);

        // set spatie team for this request too (teams mode)
        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);

        return back()->with('success', 'Workspace created successfully.');
    }

    public function update(Request $request, Workspace $workspace)
    {
        $this->ensureUserBelongsToWorkspace($request, $workspace);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $workspace->update(['name' => $data['name']]);

        return back()->with('success', 'Workspace updated successfully.');
    }

    public function destroy(Request $request, Workspace $workspace)
    {
        $this->ensureUserBelongsToWorkspace($request, $workspace);

        // prevent deleting if it's the only workspace the user has
        $userWorkspaceCount = $request->user()->workspaces()->count();
        if ($userWorkspaceCount <= 1) {
            return back()->with('error', 'You cannot delete your only workspace.');
        }

        // prevent deleting if not owner (optional SaaS rule)
        if ((int) $workspace->owner_id !== (int) $request->user()->id) {
            return back()->with('error', 'Only the workspace owner can delete it.');
        }

        $workspace->delete();

        // if deleted workspace was current, move to another workspace
        if ((int) session('current_workspace_id') === (int) $workspace->id) {
            $next = $request->user()->workspaces()->orderBy('workspaces.created_at')->value('workspaces.id');
            session(['current_workspace_id' => $next]);
            if ($next) {
                app(PermissionRegistrar::class)->setPermissionsTeamId((int) $next);
            }
        }

        return back()->with('success', 'Workspace deleted successfully.');
    }

    public function switch(Request $request, Workspace $workspace)
    {
        $this->ensureUserBelongsToWorkspace($request, $workspace);

        session(['current_workspace_id' => $workspace->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);

        return back()->with('success', 'Workspace switched.');
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
