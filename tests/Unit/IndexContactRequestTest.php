<?php

namespace Tests\Unit;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * keyword・gender・category_id・dateが有効な場合、バリデーションを通過すること
     */
    public function test_valid_filters_pass_validation(): void
    {
        $category = Category::factory()->create();

        $data = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-01-15',
        ];

        $validator = Validator::make($data, (new IndexContactRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * 検索条件が全て未指定でもバリデーションを通過すること（全て任意項目のため）
     */
    public function test_empty_filters_pass_validation(): void
    {
        $validator = Validator::make([], (new IndexContactRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * 不正な性別値（0,1,2,3以外）を拒否すること
     */
    public function test_invalid_gender_is_rejected(): void
    {
        $data = ['gender' => 9];

        $validator = Validator::make($data, (new IndexContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /**
     * 存在しないカテゴリIDを拒否すること
     */
    public function test_nonexistent_category_id_is_rejected(): void
    {
        $data = ['category_id' => 9999];

        $validator = Validator::make($data, (new IndexContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }
}