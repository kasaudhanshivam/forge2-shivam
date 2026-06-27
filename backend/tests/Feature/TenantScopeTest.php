<?php

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('scopes queries to the authenticated users organization', function () {
    // Create Organization A
    $orgA = Organization::create([
        'name' => 'Organization A',
        'slug' => 'org-a',
    ]);

    // Create Organization B
    $orgB = Organization::create([
        'name' => 'Organization B',
        'slug' => 'org-b',
    ]);

    // Create user in Org A
    $userA = User::create([
        'name' => 'User A',
        'email' => 'usera@example.com',
        'password' => Hash::make('password'),
        'organization_id' => $orgA->id,
        'role' => 'agent',
    ]);

    // Create user in Org B
    $userB = User::create([
        'name' => 'User B',
        'email' => 'userb@example.com',
        'password' => Hash::make('password'),
        'organization_id' => $orgB->id,
        'role' => 'agent',
    ]);

    // Create a ticket in Org A
    $ticketA = Ticket::create([
        'organization_id' => $orgA->id,
        'requester_id' => $userA->id,
        'subject' => 'Ticket A',
        'description' => 'Description for ticket A',
        'status' => 'open',
        'priority' => 'medium',
    ]);

    // Create a ticket in Org B
    $ticketB = Ticket::create([
        'organization_id' => $orgB->id,
        'requester_id' => $userB->id,
        'subject' => 'Ticket B',
        'description' => 'Description for ticket B',
        'status' => 'open',
        'priority' => 'medium',
    ]);

    // Authenticate as User A
    Sanctum::actingAs($userA);

    // User A should see their own ticket
    $response = $this->getJson('/api/tickets');
    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonPath('0.id', $ticketA->id);

    // User A should be able to access ticket A
    $response = $this->getJson("/api/tickets/{$ticketA->id}");
    $response->assertOk();
    $response->assertJsonPath('id', $ticketA->id);

    // User A should NOT be able to access ticket B (scoped to org)
    $response = $this->getJson("/api/tickets/{$ticketB->id}");
    $response->assertNotFound();
});

it('returns token on login', function () {
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
});

it('registers a user and creates an organization', function () {
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
});

it('logs out the user', function () {
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
});

it('enforces role middleware', function () {
    $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
    $user = User::create([
        'name' => 'Test User',
        'email' => 'customer@example.com',
        'password' => Hash::make('password'),
        'organization_id' => $org->id,
        'role' => 'customer',
    ]);

    Sanctum::actingAs($user);

    // Add a route that requires admin role
    \Illuminate\Support\Facades\Route::middleware(['auth:sanctum', 'role:admin'])->get('/api/admin-only', function () {
        return response()->json(['ok' => true]);
    });

    $response = $this->getJson('/api/admin-only');
    $response->assertForbidden();
});
