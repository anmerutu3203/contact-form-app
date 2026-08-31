<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageContactsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * created_atは$fillableに含まれていないため、mass assignment（create()）では
     * 反映されない。直接プロパティに代入してsave()することで確実に反映させる。
     */
    private function createContactWithDate(string $date, array $attributes = []): Contact
    {
        $contact = Contact::factory()->create($attributes);
        $contact->created_at = $date;
        $contact->save();

        return $contact->fresh();
    }

    /**
     * keywordフィルタ（姓・名の部分一致）が機能すること
     */
    public function test_admin_index_filters_by_keyword(): void
    {
        $target = Contact::factory()->create(['first_name' => '太郎', 'last_name' => '山田']);
        Contact::factory()->create(['first_name' => '花子', 'last_name' => '鈴木']);

        $response = $this->actingAs($this->user)->get('/admin?keyword=山田');

        $response->assertOk();
        $response->assertViewHas('contacts', function ($contacts) use ($target) {
            return $contacts->total() === 1 && $contacts->first()->is($target);
        });
    }

    /**
     * genderフィルタが機能すること
     */
    public function test_admin_index_filters_by_gender(): void
    {
        $target = Contact::factory()->create(['gender' => 2]);
        Contact::factory()->create(['gender' => 1]);
        Contact::factory()->create(['gender' => 3]);

        $response = $this->actingAs($this->user)->get('/admin?gender=2');

        $response->assertOk();
        $response->assertViewHas('contacts', function ($contacts) use ($target) {
            return $contacts->total() === 1 && $contacts->first()->is($target);
        });
    }

    /**
     * category_idフィルタが機能すること
     */
    public function test_admin_index_filters_by_category(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        $target = Contact::factory()->create(['category_id' => $categoryB->id]);
        Contact::factory()->create(['category_id' => $categoryA->id]);

        $response = $this->actingAs($this->user)->get("/admin?category_id={$categoryB->id}");

        $response->assertOk();
        $response->assertViewHas('contacts', function ($contacts) use ($target) {
            return $contacts->total() === 1 && $contacts->first()->is($target);
        });
    }

    /**
     * dateフィルタが機能すること
     *
     * created_atは$fillableに含まれないため、createContactWithDate()で
     * 直接プロパティ代入してから保存している。
     */
    public function test_admin_index_filters_by_date(): void
    {
        $target = $this->createContactWithDate('2026-01-15');
        $this->createContactWithDate('2026-02-01');

        $response = $this->actingAs($this->user)->get('/admin?date=2026-01-15');

        $response->assertOk();
        $response->assertViewHas('contacts', function ($contacts) use ($target) {
            return $contacts->total() === 1 && $contacts->first()->is($target);
        });
    }

    /**
     * 検索結果が7件ごとにページネーションされること
     */
    public function test_admin_index_paginates_results(): void
    {
        Contact::factory()->count(9)->create();

        $response = $this->actingAs($this->user)->get('/admin');

        $response->assertOk();
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->count() === 7 && $contacts->total() === 9;
        });
    }

    /**
     * 未認証ユーザーが/adminにアクセスすると/loginにリダイレクトされること
     */
    public function test_guest_cannot_access_admin_index(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    /**
     * 未認証ユーザーが削除を実行できず、/loginにリダイレクトされること
     * （データも削除されていないことを合わせて確認）
     */
    public function test_guest_cannot_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    /**
     * 指定したお問い合わせがカテゴリ情報付きで詳細ページに表示されること
     */
    public function test_admin_can_view_contact_detail_with_category(): void
    {
        $category = Category::factory()->create(['content' => 'テストカテゴリ']);
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
        ]);

        $response = $this->actingAs($this->user)->get("/admin/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertViewIs('admin.show');
        $response->assertViewHas('contact', fn ($c) => $c->is($contact));
        $response->assertSee('太郎');
        $response->assertSee('山田');
        $response->assertSee('テストカテゴリ');
    }

    /**
     * 存在しないIDを指定した場合、404が返ること
     */
    public function test_admin_show_returns_404_for_nonexistent_contact(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/contacts/9999');

        $response->assertNotFound();
    }

    /**
     * レコードが正常に削除され、/adminにリダイレクトされること
     */
    public function test_admin_can_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->user)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /**
     * 存在しないIDを削除しようとした場合、404が返ること
     */
    public function test_admin_destroy_returns_404_for_nonexistent_contact(): void
    {
        $response = $this->actingAs($this->user)->delete('/admin/contacts/9999');

        $response->assertNotFound();
    }
}