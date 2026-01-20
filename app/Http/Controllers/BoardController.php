<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $projects = Project::query()
            ->whereHas('team', function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                  ->orWhereHas('members', fn ($m) => $m->where('users.id', $userId));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedProjectId = (int) $request->query('project', $projects->first()?->id ?? 0);
        $selectedProject = $projects->firstWhere('id', $selectedProjectId);

        $columns = collect(Ticket::STATUSES)
            ->mapWithKeys(fn ($s) => [$s => []])
            ->toArray();

        if ($selectedProject) {
            $tickets = Ticket::query()
                ->where('project_id', $selectedProject->id)
                ->orderBy('status')
                ->orderBy('position')
                ->get(['id', 'title', 'description', 'status', 'position']);

            foreach ($tickets as $t) {
                $columns[$t->status][] = $t;
            }
        }

        return inertia('Board/Index', [
            'projects' => $projects,
            'selectedProjectId' => $selectedProject?->id,
            'columns' => $columns,
            'statuses' => Ticket::STATUSES,
        ]);
    }

    public function show(Project $project)
    {
        $tickets = $project->tickets()
            ->orderBy('status')
            ->orderBy('position')
            ->get();

        $columns = collect(Ticket::STATUSES)
            ->mapWithKeys(fn($s) => [$s => []])
            ->toArray();

        foreach ($tickets as $t) {
            $columns[$t->status][] = $t;
        }

        return inertia('Board/Show', [
            'project' => $project->only(['id', 'name', 'description', 'team_id']),
            'columns' => $columns,
            'statuses' => Ticket::STATUSES,
        ]);
    }

    public function reorder(Request $request, Project $project)
    {
        $data = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*' => ['array'],
            'columns.*.*' => ['integer'],
        ]);

        DB::transaction(function () use ($data, $project) {
            foreach ($data['columns'] as $status => $ticketIds) {
                if (!in_array($status, Ticket::STATUSES, true)) continue;

                foreach ($ticketIds as $index => $ticketId) {
                    Ticket::where('id', $ticketId)
                        ->where('project_id', $project->id)
                        ->update([
                            'status' => $status,
                            'position' => $index,
                        ]);
                }
            }
        });

        return back();
    }
}
