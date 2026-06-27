<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isCustomer()) {
            return $ticket->requester_id === $user->id;
        }

        return $ticket->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isCustomer()) {
            return false;
        }

        return $ticket->organization_id === $user->organization_id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() && $ticket->organization_id === $user->organization_id;
    }

    public function createComment(User $user, Ticket $ticket): bool
    {
        if ($user->isCustomer()) {
            return $ticket->requester_id === $user->id;
        }

        return $ticket->organization_id === $user->organization_id;
    }
}
