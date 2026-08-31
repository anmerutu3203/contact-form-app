<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactConfirmAndStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 有効なデータをテスト用に組み立てるヘルパー
     */
    private function validData(Category $category, ?Tag $tag = null): array
    {
        return [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '千駄ヶ谷マンション305',
            'category_id' => $category->id,
            'detail' => 'テストお問い合わせ内容',
            'tag_ids' => $tag ? [$tag->id] : [],
        ];
    }

    /**
     * バリデーション通過時、確認ページ（contact.confirm）が表示され、
     * 入力内容（氏名・メール・カテゴリ名等）が画面に表示されること
     */
    public function test_confirm_page_is_displayed_with_valid_data(): void
    {
        $category = Category::factory()->create(['content' => 'テストカテゴリ']);
        $tag = Tag::factory()->create(['name' => 'テストタグ']);

        $response = $this->post('/contacts/confirm', $this->validData($category, $tag));

        $response->assertOk();
        $response->assertViewIs('contact.confirm');
        $response->assertSee('太郎');
        $response->assertSee('山田');
        $response->assertSee('test@example.com');
        $response->assertSee('テストカテゴリ');
        $response->assertSee('テストタグ');
    }

    /**
     * バリデーションエラー時、リダイレクトされエラーが返ること
     */
    public function test_confirm_redirects_back_with_errors_on_invalid_data(): void
    {
        $response = $this->post('/contacts/confirm', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'category_id', 'detail',
        ]);
    }

    /**
     * バリデーション通過時、contactsテーブルに保存され、
     * タグがcontact_tagテーブルに記録され、/thanksへリダイレクトされること
     */
    public function test_store_saves_contact_with_tags_and_redirects_to_thanks(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->post('/contacts', $this->validData($category, $tag));

        $response->assertRedirect(route('contact.thanks'));

        $this->assertDatabaseHas('contacts', [
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'test@example.com',
        ]);

        $contact = Contact::first();
        $this->assertCount(1, $contact->tags);
        $this->assertTrue($contact->tags->contains($tag));
    }

    /**
     * バリデーションエラー時、リダイレクトされエラーが返ること
     */
    public function test_store_redirects_back_with_errors_on_invalid_data(): void
    {
        $response = $this->post('/contacts', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'category_id', 'detail',
        ]);

        $this->assertDatabaseCount('contacts', 0);
    }
} 