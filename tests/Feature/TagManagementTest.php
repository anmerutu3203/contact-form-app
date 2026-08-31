<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * 認証済みユーザーがタグ編集画面を表示できること
     */
    public function test_authenticated_user_can_view_tag_edit_page(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->actingAs($this->user)->get("/admin/tags/{$tag->id}/edit");

        $response->assertOk();
        $response->assertViewIs('admin.tags.edit');
        $response->assertViewHas('tag', fn ($t) => $t->is($tag));
    }

    /**
     * 認証済みユーザーがタグを新規作成でき、/adminへリダイレクトされ、
     * 一覧画面に作成したタグが表示されること
     */
    public function test_authenticated_user_can_create_tag(): void
    {
        $response = $this->actingAs($this->user)->post('/admin/tags', ['name' => '新しいタグ']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '新しいタグ']);

        $this->actingAs($this->user)->get('/admin')->assertSee('新しいタグ');
    }

    /**
     * タグ名が重複している場合、作成が拒否されバリデーションエラーが返ること
     * （FormRequestのrules()だけでなく、コントローラーが実際にvalidated()を
     * 使っているかまでHTTP経由で確認する）
     */
    public function test_creating_tag_with_duplicate_name_fails(): void
    {
        Tag::factory()->create(['name' => '質問']);

        $response = $this->actingAs($this->user)->post('/admin/tags', ['name' => '質問']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('tags', 1);
    }

    /**
     * 認証済みユーザーがタグを更新でき、/adminへリダイレクトされること
     */
    public function test_authenticated_user_can_update_tag(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->actingAs($this->user)->put("/admin/tags/{$tag->id}", ['name' => '更新後タグ']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '更新後タグ']);
    }

    /**
     * 他のタグで既に使用されている名前へ変更しようとした場合、
     * 更新が拒否されバリデーションエラーが返ること
     */
    public function test_updating_tag_with_duplicate_name_fails(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);
        Tag::factory()->create(['name' => '要望']);

        $response = $this->actingAs($this->user)->put("/admin/tags/{$tag->id}", ['name' => '要望']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '質問']);
    }

    /**
     * 認証済みユーザーがタグを削除でき、/adminへリダイレクトされること
     */
    public function test_authenticated_user_can_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    /**
     * 存在しないタグIDで編集画面にアクセスした場合、404が返ること
     */
    public function test_admin_tag_edit_returns_404_for_nonexistent_tag(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/tags/9999/edit');

        $response->assertNotFound();
    }

    /**
     * 存在しないタグIDを更新しようとした場合、404が返ること
     */
    public function test_admin_tag_update_returns_404_for_nonexistent_tag(): void
    {
        $response = $this->actingAs($this->user)->put('/admin/tags/9999', ['name' => '更新後タグ']);

        $response->assertNotFound();
    }

    /**
     * 存在しないタグIDを削除しようとした場合、404が返ること
     */
    public function test_admin_tag_destroy_returns_404_for_nonexistent_tag(): void
    {
        $response = $this->actingAs($this->user)->delete('/admin/tags/9999');

        $response->assertNotFound();
    }

    /**
     * 未認証ユーザーがタグ編集画面にアクセスできず、/loginにリダイレクトされること
     */
    public function test_guest_cannot_view_tag_edit_page(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->get("/admin/tags/{$tag->id}/edit");

        $response->assertRedirect('/login');
    }

    /**
     * 未認証ユーザーがタグを作成できず、/loginにリダイレクトされること
     */
    public function test_guest_cannot_create_tag(): void
    {
        $response = $this->post('/admin/tags', ['name' => '新しいタグ']);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('tags', ['name' => '新しいタグ']);
    }

    /**
     * 未認証ユーザーがタグを更新できず、/loginにリダイレクトされること
     */
    public function test_guest_cannot_update_tag(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->put("/admin/tags/{$tag->id}", ['name' => '更新後タグ']);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '質問']);
    }

    /**
     * 未認証ユーザーがタグを削除できず、/loginにリダイレクトされること
     */
    public function test_guest_cannot_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }
}