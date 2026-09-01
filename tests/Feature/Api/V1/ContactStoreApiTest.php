<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreApiTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(Category $category, ?Tag $tag = null): array
    {
        return [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'category_id' => $category->id,
            'detail' => 'テスト内容',
            'tag_ids' => $tag ? [$tag->id] : [],
        ];
    }

    /**
     * レコードが作成され201が返ること
     */
    public function test_store_creates_contact_and_returns_201(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->postJson('/api/v1/contacts', $this->validPayload($category, $tag));

        $response->assertCreated();
        $response->assertJsonPath('data.first_name', '太郎');
        $response->assertJsonCount(1, 'data.tags');

        $this->assertDatabaseHas('contacts', ['first_name' => '太郎', 'last_name' => '山田']);
    }

    /**
     * バリデーションエラー時、422が返ること
     */
    public function test_store_returns_422_on_validation_error(): void
    {
        $response = $this->postJson('/api/v1/contacts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'category_id', 'detail',
        ]);
        $this->assertDatabaseCount('contacts', 0);
    }
}