<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketCollection;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $query = Ticket::query()->with(['requester', 'assignee'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->input('assignee_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tickets = $query->paginate(15);

        return new TicketCollection($tickets);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Ticket::class);

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:' . implode(',', Ticket::STATUSES),
            'priority' => 'nullable|string|in:' . implode(',', Ticket::PRIORITIES),
            'tags' => 'nullable|array',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $data['requester_id'] = auth()->id();

        $ticket = Ticket::create($data);

        return response()->json(new TicketResource($ticket->loadMissing(['requester', 'assignee', 'comments'])), 201);
    }

    public function show(Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        $ticket->load(['requester', 'assignee', 'comments.user']);

        return new TicketResource($ticket);
    }

    public function update(Request $request, Ticket $ticket)
    {
        Gate::authorize('update', $ticket);

        $data = $request->validate([
            'status' => 'nullable|string|in:' . implode(',', Ticket::STATUSES),
            'priority' => 'nullable|string|in:' . implode(',', Ticket::PRIORITIES),
            'assignee_id' => 'nullable|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $ticket->update($data);

        return new TicketResource($ticket->loadMissing(['requester', 'assignee', 'comments']));
    }

    public function destroy(Ticket $ticket)
    {
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully.']);
    }
}
