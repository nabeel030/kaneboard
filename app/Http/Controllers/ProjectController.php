<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $projects = Project::query()
            ->whereHas('team', function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                  ->orWhereHas('members', fn ($m) => $m->where('users.id', $userId));
            })
            ->with('team:id,name')
            ->latest()
            ->get(['id','team_id','name','description','created_at']);

        return inertia('Projects/Index', [
            'projects' => $projects->map(function ($p) {
                return [
                    'id' => $p->id,
                    'team_id' => $p->team_id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'created_at' => $p->created_at,
                    'team' => $p->team?->only(['id','name']),
                ];
            }),
        ]);
    }

    public function create(Request $request)
    {
        $teams = $this->userTeams($request);

        return inertia('Projects/Create', [
            'teams' => $teams,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $team = Team::findOrFail($data['team_id']);
        $this->authorizeTeam($request, $team);

        $project = Project::create($data);

        return redirect()->route('projects.show', $project);
    }

    public function show(Request $request, Project $project)
    {
        $project->load('team:id,name,owner_id');
        $this->authorizeTeam($request, $project->team);

        return inertia('Projects/Show', [
            'project' => $project->only(['id','team_id','name','description']),
            'team' => $project->team->only(['id','name']),
        ]);
    }

    public function edit(Request $request, Project $project)
    {
        $project->load('team:id,name,owner_id');
        $this->authorizeTeam($request, $project->team);

        $teams = $this->userTeams($request);

        return inertia('Projects/Edit', [
            'project' => $project->only(['id','team_id','name','description']),
            'teams' => $teams,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $project->load('team:id,name,owner_id');
        $this->authorizeTeam($request, $project->team);

        $data = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $newTeam = Team::findOrFail($data['team_id']);
        $this->authorizeTeam($request, $newTeam);

        $project->update($data);

        return redirect()->route('projects.show', $project);
    }

    public function destroy(Request $request, Project $project)
    {
        $project->load('team:id,owner_id');
        $this->authorizeTeam($request, $project->team);

        $project->delete();

        return redirect()->route('projects.index');
    }

    private function userTeams(Request $request)
    {
        $userId = $request->user()->id;

        return Team::query()
            ->where('owner_id', $userId)
            ->orWhereHas('members', fn ($q) => $q->where('users.id', $userId))
            ->orderBy('name')
            ->get(['id','name']);
    }

    private function authorizeTeam(Request $request, Team $team): void
    {
        $userId = $request->user()->id;

        $isAllowed = $team->owner_id === $userId
            || $team->members()->where('users.id', $userId)->exists();

        abort_unless($isAllowed, 403);
    }
}
