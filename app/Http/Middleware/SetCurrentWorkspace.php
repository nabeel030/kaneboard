<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetCurrentWorkspace
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        // 1) session
        $workspaceId = session('current_workspace_id');

        // 2) fallback to first workspace user belongs to
        if (!$workspaceId) {
            $workspaceId = auth()->user()
                ->workspaces()
                ->orderBy('workspace_user.created_at')
                ->value('workspaces.id');

            if ($workspaceId) {
                session(['current_workspace_id' => $workspaceId]);
            }
        }

        // 3) set spatie team id (critical)
        if ($workspaceId) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $workspaceId);
        }

        return $next($request);
    }
}