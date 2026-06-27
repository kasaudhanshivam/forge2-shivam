<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentTest extends TestCase
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

    public function test_customer_can_add_public_comment(): void
    {
        [$org, $customer] = $this->createOrgAndUser('customer');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'Please help me with this issue.',
            'is_internal' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonMissingPath('is_internal');
        $this->assertDatabaseHas('comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'is_internal' => false,
        ]);
    }

    public function test_agent_can_add_internal_comment(): void
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
        ]);

        Sanctum::actingAs($agent);

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'Internal note about escalation.',
            'is_internal' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('is_internal', true);
        $this->assertDatabaseHas('comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'is_internal' => true,
        ]);
    }

    public function test_customer_cannot_see_internal_comments(): void
    {
        [$org, $customer] = $this->createOrgAndUser('customer');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $customer->id,
        ]);

        $agent = User::create([
            'name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'agent',
        ]);

        Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'Public response.',
            'is_internal' => false,
            'organization_id' => $org->id,
        ]);

        Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'Internal escalation note.',
            'is_internal' => true,
            'organization_id' => $org->id,
        ]);

        Sanctum::actingAs($customer);

        $response = $this->getJson("/api/tickets/{$ticket->id}/comments");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.body', 'Public response.');
        $response->assertJsonMissingPath('data.0.is_internal');
    }

    public function test_agent_can_see_internal_comments(): void
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
        ]);

        Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'Internal note.',
            'is_internal' => true,
            'organization_id' => $org->id,
        ]);

        Sanctum::actingAs($agent);

        $response = $this->getJson("/api/tickets/{$ticket->id}/comments");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.is_internal', true);
    }

    public function test_user_can_delete_own_comment(): void
    {
        [$org, $user] = $this->createOrgAndUser('customer');
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $comment = Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'My comment.',
            'is_internal' => false,
            'organization_id' => $org->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/comments/{$comment->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_admin_can_delete_any_comment(): void
    {
        [$org, $admin] = $this->createOrgAndUser('admin');
        $user = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'customer',
        ]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $user->id,
        ]);

        $comment = Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Someone else\'s comment.',
            'is_internal' => false,
            'organization_id' => $org->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/comments/{$comment->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_others_comment(): void
    {
        [$org, $userA] = $this->createOrgAndUser('customer', ['email' => 'a@example.com']);
        $userB = User::create([
            'name' => 'User B',
            'email' => 'b@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'customer',
        ]);
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'requester_id' => $userA->id,
        ]);

        $comment = Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userB->id,
            'body' => 'User B comment.',
            'is_internal' => false,
            'organization_id' => $org->id,
        ]);

        Sanctum::actingAs($userA);

        $response = $this->deleteJson("/api/comments/{$comment->id}");
        $response->assertForbidden();
    }
}
