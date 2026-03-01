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

    Route::get('ticket-board', [BoardController::class, 'index'])->name('kaneboard.index');
    Route::post('/projects/{project}/kaneboard/reorder', [BoardController::class, 'reorder'])->name('kaneboard.reorder');


    Route::post('/projects/{project}/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    // drag/drop reorder + move
    Route::post('/projects/{project}/board/reorder', [BoardController::class, 'reorder'])->name('board.reorder');

    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::put('/members/{user}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');

    Route::post('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store'])
    ->name('workspaces.members.store');

    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy'])
    ->name('workspaces.members.destroy');

    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store']);
    Route::delete('/tickets/{ticket}/comments/{comment}', [TicketCommentController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');


    Route::controller(TicketTimerController::class)
        ->prefix('tickets/{ticket}/timer')
        ->name('tickets.timer.')
        ->group(function () {
            Route::post('start', 'start')->name('start');
            Route::post('pause', 'pause')->name('pause');
            Route::post('stop',  'stop')->name('stop');
            Route::post('resume', 'start')->name('resume');
            Route::get('status', 'status')->name('status');
        }); 

    Route::controller(TicketTimeLogController::class)
        ->prefix('ticket/timelogs')
        ->name('timelog.')
        ->group(function () {
            Route::put('{ticketTimeLog}', 'update');
            Route::delete('{ticketTimeLog}', 'destroy');
        }); 

    Route::resource('roles', RoleController::class);

    Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::put('/workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');

    // workspace switcher
    Route::post('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
});

require __DIR__.'/settings.php';
