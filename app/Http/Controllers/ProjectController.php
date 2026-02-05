<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectHealthService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $projects = Project::query()
            ->where('owner_id', $userId)
            ->orWhereHas('members', fn ($q) => $q->where('users.id', $userId))
            ->with('owner:id,name,email')
            ->latest()
            ->get(['id', 'owner_id', 'name', 'description', 'created_at', 'start_date', 'end_date', 'baseline_start_date', 'baseline_end_date']);

        return inertia('Projects/Index', [
            'projects' => $projects->map(function ($p) use ($userId) {
                return [
                    'id' => $p->id,
                    'owner_id' => $p->owner_id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'created_at' => $p->created_at,
                    'owner' => $p->owner?->only(['id', 'name', 'email']),
                    'is_owner' => $p->owner_id === $userId,
                    'start_date' => Carbon::parse($p->start_date)->format('Y-m-d'),
                    'end_date' => Carbon::parse($p->end_date)->format('Y-m-d'),
                    'baseline_start_date' => Carbon::parse($p->baseline_start_date)->format('Y-m-d'),
                    'baseline_end_date' => Carbon::parse($p->baseline_end_date)->format('Y-m-d'),
                ];
            }),
        ]);
    }

    public function create(Request $request)
    {
        // Only logged-in user can create; usually owner-only is fine
        // If you want *any* user to create their own projects, keep as-is.
        return inertia('Projects/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'baseline_start_date' => ['nullable','date'],
            'baseline_end_date' => ['nullable','date','after_or_equal:baseline_start_date'],
        ]);

        $project = Project::create([
            'owner_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'baseline_start_date' => $data['baseline_start_date'] ?? null,
            'baseline_end_date' => $data['baseline_end_date'] ?? null,
        ]);

        // Make sure owner can access via pivot too (optional but handy)
        $project->members()->syncWithoutDetaching([
            $request->user()->id => ['role' => 'owner'],
        ]);

        if($project) {
            return back()->with('success', 'Project created successfully.');
        }

        return back()->with('error', 'Something went wrong.');
    }

    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'owner:id,name,email',
            'members:id,name,email',
        ]);

        $allMembers = User::query()
        ->where('id', '!=', $request->user()->id) // optional
        ->orderBy('name')
        ->get(['id','name','email']);

        $projectHealthService = new ProjectHealthService();
        $projectHealth = $projectHealthService->calculate($project);

        return inertia('Projects/Show', [
            'project' => $project->only(['id', 'owner_id', 'name', 'description', 'start_date','end_date','baseline_start_date','baseline_end_date']),
            'owner' => $project->owner?->only(['id', 'name', 'email']),
            'members' => $project->members->map(fn ($u) => $u->only(['id', 'name', 'email'])),
            'allMembers' => $allMembers,
            'can' => [
                'update' => $request->user()->can('update', $project),
                'delete' => $request->user()->can('delete', $project),
                'manageMembers' => $request->user()->can('manageMembers', $project),
            ],
            'projectHealth' => $projectHealth
        ]);
    }

    public function edit(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        return inertia('Projects/Edit', [
            'project' => $project->only([
                    'id', 'owner_id', 'name', 'description', 'start_date',
                    'end_date', 'baseline_start_date', 'baseline_end_date'
                ]),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'baseline_start_date' => ['nullable','date'],
            'baseline_end_date' => ['nullable','date','after_or_equal:baseline_start_date'],
        ]);

        $updated = $project->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'baseline_start_date' => $data['baseline_start_date'] ?? null,
            'baseline_end_date' => $data['baseline_end_date'] ?? null,
        ]);

        if($updated) {
            return back()->with('success', 'Project updated successfully.');
        }

        return back()->with('error', 'Something went wrong.');

    }

    public function destroy(Request $request, Project $project)
    {
        $this->authorize('delete', $project);

        $deleted = $project->delete();

        if($deleted) {
            return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
        }

        return redirect()->route('projects.index')->with('error', 'Something went wrong.');
    }
}
