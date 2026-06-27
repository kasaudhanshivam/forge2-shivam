<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createOrgAndUser(string $role = 'admin', array $overrides = []): array
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $user = User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => $role,
        ], $overrides));

        return [$org, $user];
    }

    public function test_customer_can_create_ticket(): void
    {
        [$org, $user] = $this->createOrgAndUser('customer');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tickets', [
            'subject' => 'Help me',
            'description' => 'I need support',
            'priority' => 'high',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('requester_id', $user->id);
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Help me',
            'requester_id' => $user->id,
            'organization_id' => $org->id,
            'priority' => 'high',
        ]);
    }

    public function test_customer_can_view_own_ticket(): void
    {
        [$org, $user] = $this->createOrgAndUser('customer');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'subject' => 'My Ticket',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/tickets/{$ticket->id}");
        $response->assertOk();
        $response->assertJsonPath('data.id', $ticket->id);
    }

    public function test_customer_cannot_view_other_customers_ticket(): void
    {
        [$org, $userA] = $this->createOrgAndUser('customer', ['email' => 'a@example.com']);
        $userB = User::create([
            'name' => 'User B',
            'email' => 'b@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'customer',
        ]);

        $ticketB = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $userB->id,
            'subject' => 'Other Ticket',
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson("/api/tickets/{$ticketB->id}");
        $response->assertForbidden();
    }

    public function test_agent_can_view_any_ticket_in_org(): void
    {
        [$org, $agent] = $this->createOrgAndUser('agent');
        $customer = User::create([
            'name' => 'Customer',
            'email' => 'cust@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'customer',
        ]);

        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
            'subject' => 'Customer Ticket',
        ]);

        Sanctum::actingAs($agent);

        $response = $this->getJson("/api/tickets/{$ticket->id}");
        $response->assertOk();
        $response->assertJsonPath('data.id', $ticket->id);
    }

    public function test_agent_can_update_ticket(): void
    {
        [$org, $agent] = $this->createOrgAndUser('agent');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $agent->id,
            'status' => 'open',
            'priority' => 'low',
        ]);

        Sanctum::actingAs($agent);

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'resolved',
            'priority' => 'urgent',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
            'priority' => 'urgent',
        ]);
    }

    public function test_customer_cannot_update_ticket(): void
    {
        [$org, $user] = $this->createOrgAndUser('customer');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'resolved',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_ticket(): void
    {
        [$org, $admin] = $this->createOrgAndUser('admin');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/tickets/{$ticket->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_agent_cannot_delete_ticket(): void
    {
        [$org, $agent] = $this->createOrgAndUser('agent');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $agent->id,
        ]);

        Sanctum::actingAs($agent);

        $response = $this->deleteJson("/api/tickets/{$ticket->id}");
        $response->assertForbidden();
    }

    public function test_index_filters_by_status(): void
    {
        [$org, $user] = $this->createOrgAndUser('agent');
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'status' => 'resolved',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tickets?status=resolved');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'resolved');
    }

    public function test_index_filters_by_priority(): void
    {
        [$org, $user] = $this->createOrgAndUser('agent');
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'priority' => 'low',
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'priority' => 'high',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tickets?priority=high');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.priority', 'high');
    }

    public function test_index_filters_by_assignee_id(): void
    {
        [$org, $user] = $this->createOrgAndUser('agent');
        $other = User::create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'agent',
        ]);

        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'assignee_id' => $user->id,
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'assignee_id' => $other->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/tickets?assignee_id={$user->id}");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.assignee_id', $user->id);
    }

    public function test_index_filters_by_search(): void
    {
        [$org, $user] = $this->createOrgAndUser('agent');
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'subject' => 'Billing issue',
            'description' => 'I cannot pay',
        ]);
        Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
            'subject' => 'Login problem',
            'description' => 'Forgot password',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tickets?search=Billing');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.subject', 'Billing issue');
    }
}
