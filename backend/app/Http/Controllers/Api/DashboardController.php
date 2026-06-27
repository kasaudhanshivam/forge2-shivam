<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = auth()->user()->organization_id;

        $totalTickets = Ticket::where('organization_id', $organizationId)->count();
        $openTickets = Ticket::where('organization_id', $organizationId)->where('status', 'open')->count();
        $resolvedTickets = Ticket::where('organization_id', $organizationId)->where('status', 'resolved')->count();
        $slaBreached = 2; // hardcoded/mock value for now

        return response()->json([
            'data' => [
                'total_tickets' => $totalTickets,
                'open_tickets' => $openTickets,
                'resolved_tickets' => $resolvedTickets,
                'sla_breached' => $slaBreached,
            ],
        ]);
    }
}
