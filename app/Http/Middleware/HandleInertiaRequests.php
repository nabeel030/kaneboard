<?php

namespace App\Http\Middleware;

use App\Services\RunningTimerService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => fn () => $user
                ? [
                    'user' => $user,
                    'roles' => $user->getRoleNames()->values(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                ]
            : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'runningTimer' => function () use ($user) {
                $user = $user;
                if (!$user) return null;

                return app(RunningTimerService::class)->forUser($user->id);
            },
            'workspaces' => $user
                ? $user->workspaces()->orderBy('name')->get(['workspaces.id','workspaces.name'])->values()
                : [],

            'currentWorkspaceId' => (int) (session('current_workspace_id') ?? 0),
        ];
    }
}
