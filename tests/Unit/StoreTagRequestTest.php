<?php

namespace Tests\Unit;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTagRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 有効なタグ名の場合、バリデーションを通過すること
     */
    public function test_valid_name_passes_validation(): void
    {
        $validator = Validator::make(
            ['name' => '新しいタグ'],
            (new StoreTagRequest())->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /**
     * タグ名が未入力の場合、拒否されること
     */
    public function test_empty_name_is_rejected(): void
    {
        $validator = Validator::make(
            ['name' => ''],
            (new StoreTagRequest())->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * タグ名が50文字を超える場合、拒否されること
     */
    public function test_name_exceeding_max_length_is_rejected(): void
    {
        $validator = Validator::make(
            ['name' => str_repeat('あ', 51)],
            (new StoreTagRequest())->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * 既に使用されているタグ名の場合、拒否されること（重複禁止）
     */
    public function test_duplicate_name_is_rejected(): void
    {
        $tag = Tag::factory()->create();

        $validator = Validator::make(
            ['name' => $tag->name],
            (new StoreTagRequest())->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}