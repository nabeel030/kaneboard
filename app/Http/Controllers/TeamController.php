<?php
namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $teams = Team::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('users.id', $user->id))
            ->latest()
            ->get(['id', 'name', 'owner_id', 'created_at']);

        return inertia('Teams/Index', [
            'teams' => $teams,
        ]);
    }

    public function create()
    {
        return inertia('Teams/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $team = Team::create([
            'name' => $data['name'],
            'owner_id' => $request->user()->id,
        ]);

        // ensure owner is also a member
        $team->members()->syncWithoutDetaching([$request->user()->id]);

        return redirect()->route('teams.show', $team);
    }

    public function show(Request $request, Team $team)
    {
        $this->authorizeTeam($request, $team);

        $team->load(['projects:id,team_id,name,description,created_at']);

        return inertia('Teams/Show', [
            'team' => $team->only(['id','name','owner_id']),
            'projects' => $team->projects->map->only(['id','team_id','name','description','created_at']),
        ]);
    }

    public function edit(Request $request, Team $team)
    {
        $this->authorizeTeam($request, $team);

        return inertia('Teams/Edit', [
            'team' => $team->only(['id','name']),
        ]);
    }

    public function update(Request $request, Team $team)
    {
        $this->authorizeTeam($request, $team);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $team->update($data);

        return redirect()->route('teams.show', $team);
    }

    public function destroy(Request $request, Team $team)
    {
        $this->authorizeTeam($request, $team);

        $team->delete();

        return redirect()->route('teams.index');
    }

    private function authorizeTeam(Request $request, Team $team): void
    {
        $userId = $request->user()->id;

        $isAllowed = $team->owner_id === $userId
            || $team->members()->where('users.id', $userId)->exists();

        abort_unless($isAllowed, 403);
    }
}
