<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(Category $category, ?Tag $tag = null): array
    {
        return [
            'first_name' => '次郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'updated@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'category_id' => $category->id,
            'detail' => '更新テスト',
            'tag_ids' => $tag ? [$tag->id] : [],
        ];
    }

    /**
     * レコードが更新され200が返ること
     */
    public function test_update_updates_contact_and_returns_200(): void
    {
        $contact = Contact::factory()->create(['first_name' => '太郎']);
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $this->validPayload($category, $tag));

        $response->assertOk();
        $response->assertJsonPath('data.first_name', '次郎');

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => '次郎']);
        $this->assertTrue($contact->fresh()->tags->contains($tag));
    }

    /**
     * 存在しないIDで404エラーJSONが返ること
     */
    public function test_update_returns_404_json_for_nonexistent_contact(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson('/api/v1/contacts/9999', $this->validPayload($category));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }

    /**
     * バリデーションエラー時、422が返ること
     */
    public function test_update_returns_422_on_validation_error(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'gender']);
    }
}