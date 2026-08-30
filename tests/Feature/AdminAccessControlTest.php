<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessControlTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証されたユーザーは管理ダッシュボード（/admin）を表示できること
     */
    public function test_authenticated_user_can_view_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.index');
    }

    /**
     * 未認証ユーザーは /login にリダイレクトされること
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }
}