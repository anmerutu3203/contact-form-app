<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactRelationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1つのお問い合わせが特定のカテゴリに属していること（belongsTo）
     */
    public function test_contact_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $contact->category);
        $this->assertEquals($category->id, $contact->category->id);
    }

    /**
     * 1つのお問い合わせが複数のタグと同期（sync）できること
     */
    public function test_contact_can_sync_multiple_tags(): void
    {
        $contact = Contact::factory()->create();

        $tag1 = Tag::create(['name' => '質問']);
        $tag2 = Tag::create(['name' => '要望']);
        $tag3 = Tag::create(['name' => 'ご意見']);

        // 初回: tag1, tag2をattach
        $contact->tags()->attach([$tag1->id, $tag2->id]);
        $this->assertCount(2, $contact->fresh()->tags);

        // sync: tag2, tag3のみに置き換え
        $contact->tags()->sync([$tag2->id, $tag3->id]);
        $freshTags = $contact->fresh()->tags;

        $this->assertCount(2, $freshTags);
        $this->assertTrue($freshTags->contains($tag2));
        $this->assertTrue($freshTags->contains($tag3));
        $this->assertFalse($freshTags->contains($tag1));
    }
}