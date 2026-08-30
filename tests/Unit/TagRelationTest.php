<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagRelationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 中間テーブルを介して、1つのタグが複数のお問い合わせに紐づいていること
     */
    public function test_tag_belongs_to_many_contacts(): void
    {
        $tag = Tag::create(['name' => '質問']);

        $contact1 = Contact::factory()->create();
        $contact2 = Contact::factory()->create();

        $contact1->tags()->attach($tag->id);
        $contact2->tags()->attach($tag->id);

        $contacts = $tag->fresh()->contacts;

        $this->assertCount(2, $contacts);
        $this->assertTrue($contacts->contains($contact1));
        $this->assertTrue($contacts->contains($contact2));
    }
}