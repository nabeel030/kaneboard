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
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('projects', ProjectController::class);

    Route::get('kaneboard', [BoardController::class, 'index'])->name('kaneboard.index');
    Route::get('/projects/{project}/kaneboard', [BoardController::class, 'show'])->name('projects.kaneboard');
    Route::post('/projects/{project}/kaneboard/reorder', [BoardController::class, 'reorder'])->name('kaneboard.reorder');


    Route::post('/projects/{project}/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    // drag/drop reorder + move
    Route::post('/projects/{project}/board/reorder', [BoardController::class, 'reorder'])->name('board.reorder');

    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/members/store', [MemberController::class, 'store'])->name('members.store');
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');

    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store']);
    Route::delete('/tickets/{ticket}/comments/{comment}', [TicketCommentController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
});

require __DIR__.'/settings.php';
