<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function destroy(Request $request, Ticket $ticket, TicketComment $comment)
    {
        abort_unless($comment->ticket_id === $ticket->id, 404);
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}
