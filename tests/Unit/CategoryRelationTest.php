<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRelationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1つのカテゴリから、紐づく複数のお問い合わせ（hasMany）が正しく取得できること
     */
    public function test_category_has_many_contacts(): void
    {
        $category = Category::factory()->create();

        $contact1 = Contact::factory()->create(['category_id' => $category->id]);
        $contact2 = Contact::factory()->create(['category_id' => $category->id]);

        $contacts = $category->contacts;

        $this->assertCount(2, $contacts);
        $this->assertTrue($contacts->contains($contact1));
        $this->assertTrue($contacts->contains($contact2));
    }
}