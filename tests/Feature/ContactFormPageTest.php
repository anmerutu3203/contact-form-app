<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * お問い合わせフォーム入力ページ（/）が正常に表示され、
     * categories・tagsがビュー変数として渡され、カテゴリ名・タグ名が画面に表示されること
     */
    public function test_index_page_displays_categories_and_tags(): void
    {
        $category = Category::factory()->create(['content' => 'テストカテゴリ']);
        $tag = Tag::factory()->create(['name' => 'テストタグ']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('contact.index');
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
        $response->assertSee('テストカテゴリ');
        $response->assertSee('テストタグ');
    }

    /**
     * サンクスページ（/thanks）が正常に表示されること
     */
    public function test_thanks_page_is_displayed(): void
    {
        $response = $this->get('/thanks');

        $response->assertOk();
        $response->assertViewIs('contact.thanks');
    }
}