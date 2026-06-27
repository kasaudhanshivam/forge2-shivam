<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index(Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        $query = $ticket->comments()->with('user')->orderBy('created_at');

        if (auth()->user()->isCustomer()) {
            $query->where('is_internal', false);
        }

        $comments = $query->get();

        return CommentResource::collection($comments);
    }

    public function store(Request $request, Ticket $ticket)
    {
        Gate::authorize('createComment', $ticket);

        $data = $request->validate([
            'body' => 'required|string',
            'is_internal' => 'nullable|boolean',
        ]);

        $isInternal = false;
        if (auth()->user()->isCustomer()) {
            $isInternal = false;
        } elseif (isset($data['is_internal'])) {
            $isInternal = $data['is_internal'];
        }

        $comment = $ticket->comments()->create([
            'body' => $data['body'],
            'is_internal' => $isInternal,
            'user_id' => auth()->id(),
            'organization_id' => auth()->user()->organization_id,
        ]);

        $comment->load('user');

        return response()->json(new CommentResource($comment), 201);
    }

    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully.']);
    }
}
