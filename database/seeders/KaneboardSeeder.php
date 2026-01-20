<?php
namespace Database\Seeders;

use App\Models\Project;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class KaneboardSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'email' => 'demo@example.com',
        ]);

        $team = Team::create([
            'name' => 'Demo Team',
            'owner_id' => $user->id,
        ]);

        $team->members()->syncWithoutDetaching([$user->id]);

        $project = Project::create([
            'team_id' => $team->id,
            'name' => 'Demo Project',
            'description' => 'Starter kanban project',
        ]);

        $tickets = [
            ['backlog', 'Set up repo'],
            ['todo', 'Create board UI'],
            ['in_progress', 'Drag & drop'],
            ['done', 'Persist order'],
            ['tested', 'Smoke test flows'],
            ['completed', 'Ship MVP'],
        ];

        foreach ($tickets as $i => [$status, $title]) {
            Ticket::create([
                'project_id' => $project->id,
                'title' => $title,
                'status' => $status,
                'position' => 0,
                'created_by' => $user->id,
            ]);
        }
    }
}
