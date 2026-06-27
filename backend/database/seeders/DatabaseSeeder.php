<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::factory()->create([
            'name' => 'PulseDesk Demo',
            'slug' => 'pulsedesk-demo',
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
        ]);

        $agent1 = User::factory()->agent()->create([
            'name' => 'Demo Agent 1',
            'email' => 'agent1@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
        ]);

        $agent2 = User::factory()->agent()->create([
            'name' => 'Demo Agent 2',
            'email' => 'agent2@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
        ]);

        $customer1 = User::factory()->customer()->create([
            'name' => 'Demo Customer 1',
            'email' => 'customer1@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
        ]);

        $customer2 = User::factory()->customer()->create([
            'name' => 'Demo Customer 2',
            'email' => 'customer2@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
        ]);

        $customers = [$customer1, $customer2];
        $agents = [$agent1, $agent2];

        $ticketData = [
            ['subject' => 'Login issue', 'description' => 'Cannot log in to the portal.', 'status' => 'open', 'priority' => 'high', 'tags' => ['bug', 'urgent'], 'assignee' => $agent1],
            ['subject' => 'Feature request: dark mode', 'description' => 'Please add a dark mode.', 'status' => 'open', 'priority' => 'low', 'tags' => ['feature'], 'assignee' => null],
            ['subject' => 'Billing discrepancy', 'description' => 'My invoice amount is wrong.', 'status' => 'open', 'priority' => 'medium', 'tags' => ['billing'], 'assignee' => $agent2],
            ['subject' => 'API latency', 'description' => 'API calls are very slow today.', 'status' => 'pending', 'priority' => 'urgent', 'tags' => ['bug', 'performance'], 'assignee' => $agent1],
            ['subject' => 'Password reset not working', 'description' => 'I do not receive reset emails.', 'status' => 'pending', 'priority' => 'high', 'tags' => ['bug'], 'assignee' => null],
            ['subject' => 'Refund request', 'description' => 'I would like a refund for last month.', 'status' => 'pending', 'priority' => 'medium', 'tags' => ['billing'], 'assignee' => $agent2],
            ['subject' => 'Integration guide', 'description' => 'Need help integrating with Slack.', 'status' => 'resolved', 'priority' => 'low', 'tags' => null, 'assignee' => $agent1],
            ['subject' => 'Two-factor auth bug', 'description' => '2FA codes are rejected.', 'status' => 'resolved', 'priority' => 'high', 'tags' => ['bug', 'security'], 'assignee' => $agent2],
            ['subject' => 'New workspace onboarding', 'description' => 'Help setting up a new workspace.', 'status' => 'resolved', 'priority' => 'medium', 'tags' => null, 'assignee' => $agent1],
            ['subject' => 'Export to PDF fails', 'description' => 'PDF export throws an error.', 'status' => 'resolved', 'priority' => 'urgent', 'tags' => ['bug'], 'assignee' => null],
            ['subject' => 'Account cancellation', 'description' => 'I want to close my account.', 'status' => 'closed', 'priority' => 'low', 'tags' => null, 'assignee' => $agent2],
            ['subject' => 'Webhook timeouts', 'description' => 'Outgoing webhooks are timing out.', 'status' => 'closed', 'priority' => 'medium', 'tags' => ['bug', 'performance'], 'assignee' => $agent1],
        ];

        $tickets = [];
        foreach ($ticketData as $index => $data) {
            $requester = $customers[$index % count($customers)];
            $tickets[] = Ticket::factory()->create([
                'organization_id' => $org->id,
                'requester_id' => $requester->id,
                'assignee_id' => $data['assignee']?->id ?? null,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'status' => $data['status'],
                'priority' => $data['priority'],
                'tags' => $data['tags'],
            ]);
        }

        // Add comments to a subset of tickets (first 4 tickets get 2-3 comments each)
        $commentSets = [
            [
                ['body' => 'Thanks for reporting, we are looking into it.', 'is_internal' => false, 'user' => $agent1],
                ['body' => 'Escalated to infra team.', 'is_internal' => true, 'user' => $agent1],
            ],
            [
                ['body' => 'Could you provide a screenshot?', 'is_internal' => false, 'user' => $agent2],
                ['body' => 'Reproduced on staging.', 'is_internal' => true, 'user' => $agent2],
                ['body' => 'Fix deployed, please verify.', 'is_internal' => false, 'user' => $agent2],
            ],
            [
                ['body' => 'We need the invoice number.', 'is_internal' => false, 'user' => $agent1],
                ['body' => 'Internal: customer has prior credits.', 'is_internal' => true, 'user' => $agent1],
            ],
            [
                ['body' => 'Monitoring shows elevated latency.', 'is_internal' => false, 'user' => $agent2],
                ['body' => 'Internal: DB query issue identified.', 'is_internal' => true, 'user' => $agent2],
                ['body' => 'Patch rolled out.', 'is_internal' => false, 'user' => $agent2],
            ],
        ];

        foreach ($commentSets as $i => $comments) {
            $ticket = $tickets[$i];
            foreach ($comments as $comment) {
                Comment::factory()->create([
                    'organization_id' => $org->id,
                    'ticket_id' => $ticket->id,
                    'user_id' => $comment['user']->id,
                    'body' => $comment['body'],
                    'is_internal' => $comment['is_internal'],
                ]);
            }
        }
    }
}
