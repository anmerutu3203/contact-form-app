<?php

namespace Tests\Feature\Api\V1;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactShowApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * JSON形式でカテゴリ・タグがネストされた詳細が返ること
     */
    public function test_show_returns_json_with_category_and_tags(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonStructure([
            'data' => ['id', 'category' => ['id', 'content'], 'tags'],
        ]);
    }

    /**
     * 存在しないIDで404エラーJSONが返ること
     */
    public function test_show_returns_404_json_for_nonexistent_contact(): void
    {
        $response = $this->getJson('/api/v1/contacts/9999');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }
}