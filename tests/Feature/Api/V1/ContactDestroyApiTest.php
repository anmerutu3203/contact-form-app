<?php

namespace Tests\Feature\Api\V1;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactDestroyApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レコードが削除され204が返ること
     */
    public function test_destroy_deletes_contact_and_returns_204(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /**
     * 存在しないIDで404エラーJSONが返ること
     */
    public function test_destroy_returns_404_json_for_nonexistent_contact(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/9999');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }
}