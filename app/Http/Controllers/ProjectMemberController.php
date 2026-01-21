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

        // prevent removing owner by mistake if you also store owner in pivot
        $project->members()->detach($user->id);

        return back();
    }
}
