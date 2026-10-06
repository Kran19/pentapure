<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $cashierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->cashierUser = User::create([
            'name' => 'huzefa lehriwala',
            'email' => 'huzefa@example.com',
            'password' => 'password123',
            'role' => 'CASHIER',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_can_view_notifications_list(): void
    {
        $notifId = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'App\Notifications\GeneralNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->cashierUser->id,
            'data' => json_encode([
                'title' => 'URGENT',
                'message' => 'PLZ SHARE PDF UP TO DATE',
                'type' => 'danger',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession(['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]])->get('/admin/notifications');

        $response->assertStatus(200);
        $response->assertSee('URGENT');
        $response->assertSee('PLZ SHARE PDF UP TO DATE');
        $response->assertSee('Delete');
        $response->assertSee('Clear All Notifications');
    }

    public function test_admin_can_delete_single_notification(): void
    {
        $notifId = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'App\Notifications\GeneralNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->cashierUser->id,
            'data' => json_encode([
                'title' => 'URGENT',
                'message' => 'PLZ SHARE PDF UP TO DATE',
                'type' => 'danger',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('notifications', ['id' => $notifId]);

        $response = $this->withSession(['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]])->deleteJson("/admin/notifications/{$notifId}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('notifications', ['id' => $notifId]);
    }

    public function test_admin_can_clear_all_notifications(): void
    {
        for ($i = 0; $i < 3; $i++) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\Notifications\GeneralNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $this->cashierUser->id,
                'data' => json_encode([
                    'title' => "Notif {$i}",
                    'message' => "Message {$i}",
                    'type' => 'info',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertEquals(3, DB::table('notifications')->count());

        $response = $this->withSession(['auth_user' => [
            'id' => $this->adminUser->id,
            'name' => $this->adminUser->name,
            'role' => 'ADMIN',
        ]])->postJson("/admin/notifications/clear");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals(0, DB::table('notifications')->count());
    }

    public function test_artisan_notification_delete_command_with_search(): void
    {
        $notifId = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'App\Notifications\GeneralNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $this->cashierUser->id,
            'data' => json_encode([
                'title' => 'URGENT',
                'message' => 'PLZ SHARE PDF UP TO DATE',
                'type' => 'danger',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('notification:delete', [
            'search' => 'PLZ SHARE PDF',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('notifications', ['id' => $notifId]);
    }
}
