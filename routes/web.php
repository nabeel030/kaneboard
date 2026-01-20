<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::resource('teams', TeamController::class);
    Route::resource('projects', ProjectController::class);

    Route::get('kaneboard', [BoardController::class, 'index'])->name('kaneboard.index');
    Route::get('/projects/{project}/kaneboard', [BoardController::class, 'show'])->name('projects.kaneboard');
    Route::post('/projects/{project}/kaneboard/reorder', [BoardController::class, 'reorder'])->name('kaneboard.reorder');


    Route::post('/projects/{project}/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

    // drag/drop reorder + move
    Route::post('/projects/{project}/board/reorder', [BoardController::class, 'reorder'])->name('board.reorder');
});

require __DIR__.'/settings.php';
