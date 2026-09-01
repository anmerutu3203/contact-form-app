<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactIndexApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * JSON形式でdata配列とmeta情報が返ること
     */
    public function test_index_returns_json_with_data_and_meta(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'category', 'first_name', 'last_name', 'gender', 'email', 'tags'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
        $response->assertJsonPath('meta.total', 3);
    }

    /**
     * keywordフィルタが機能すること
     */
    public function test_index_filters_by_keyword(): void
    {
        $target = Contact::factory()->create(['first_name' => '太郎', 'last_name' => '山田']);
        Contact::factory()->create(['first_name' => '花子', 'last_name' => '鈴木']);

        $response = $this->getJson('/api/v1/contacts?keyword=山田');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $target->id);
    }

    /**
     * ページネーション（per_page指定）が機能すること
     */
    public function test_index_paginates_with_per_page(): void
    {
        Contact::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/contacts?per_page=2');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 5);
    }

    /**
     * バリデーションエラー時、422が返ること
     */
    public function test_index_returns_422_on_invalid_gender(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=9');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('gender');
    }
}