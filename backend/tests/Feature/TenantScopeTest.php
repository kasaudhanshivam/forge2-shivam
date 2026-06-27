<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_queries_are_scoped_to_authenticated_users_organization(): void
    {
        $orgA = Organization::create(['name' => 'Organization A', 'slug' => 'org-a']);
        $orgB = Organization::create(['name' => 'Organization B', 'slug' => 'org-b']);

        $userA = User::create([
            'name' => 'User A',
            'email' => 'usera@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $orgA->id,
            'role' => 'agent',
        ]);

        $userB = User::create([
            'name' => 'User B',
            'email' => 'userb@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $orgB->id,
            'role' => 'agent',
        ]);

        $ticketA = Ticket::create([
            'organization_id' => $orgA->id,
            'requester_id' => $userA->id,
            'subject' => 'Ticket A',
            'description' => 'Description for ticket A',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $ticketB = Ticket::create([
            'organization_id' => $orgB->id,
            'requester_id' => $userB->id,
            'subject' => 'Ticket B',
            'description' => 'Description for ticket B',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/tickets');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $ticketA->id);

        $response = $this->getJson("/api/tickets/{$ticketA->id}");
        $response->assertOk();
        $response->assertJsonPath('data.id', $ticketA->id);

        $response = $this->getJson("/api/tickets/{$ticketB->id}");
        $response->assertNotFound();
    }

    public function test_login_returns_token(): void
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user']);
    }

    public function test_registration_creates_user_and_organization(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'organization_name' => 'New Org',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('organizations', ['name' => 'New Org']);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com', 'role' => 'admin']);
    }

    public function test_logout_revokes_token(): void
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'admin',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');
        $response->assertOk();
        $response->assertJson(['message' => 'Logged out successfully.']);
    }

    public function test_role_middleware_blocks_unauthorized_roles(): void
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'customer',
        ]);

        Sanctum::actingAs($user);

        Route::middleware(['auth:sanctum', 'role:admin'])->get('/api/admin-only', function () {
            return response()->json(['ok' => true]);
        });

        $response = $this->getJson('/api/admin-only');
        $response->assertForbidden();
    }
}
