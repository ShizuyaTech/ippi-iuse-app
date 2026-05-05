<?php

namespace Tests\Feature;

use App\Models\BusinessEventLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BusinessEventLogAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_business_event_logs_page(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.logs@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        BusinessEventLog::create([
            'event_type' => 'test.event',
            'entity_type' => 'TestEntity',
            'entity_id' => 1,
            'user_id' => $admin->id,
            'payload' => ['source' => 'test'],
        ]);

        $this->actingAs($admin)
            ->get(route('mm.business-event-logs.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('mm.business-event-logs.export'))
            ->assertOk();
    }

    public function test_non_admin_user_is_forbidden_from_business_event_logs(): void
    {
        $user = User::create([
            'name' => 'Planner User',
            'email' => 'planner.logs@test.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'ppic',
        ]);

        $this->actingAs($user)
            ->get(route('mm.business-event-logs.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('mm.business-event-logs.export'))
            ->assertForbidden();
    }
}
