<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $this->authorize('manageMembers', $project);

        $workspaceId = (int) session('current_workspace_id');
        abort_unless((int) $project->workspace_id === $workspaceId, 404);
    
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);
    
        $ids = collect($data['user_ids'])
            ->reject(fn ($id) => (int)$id === (int)$project->owner_id)
            ->values()
            ->all();
    
        $attach = [];
        foreach ($ids as $id) {
            $attach[$id] = ['role' => 'member'];
        }
    
        $project->members()->syncWithoutDetaching($attach);
    
        return back();
    }
    

    public function destroy(Request $request, Project $project, User $user)
    {
        $this->authorize('manageMembers', $project);

        $workspaceId = (int) session('current_workspace_id');
        abort_unless((int) $project->workspace_id === $workspaceId, 404);

        $project->members()->detach($user->id);

        return back();
    }
}
