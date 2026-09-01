<?php

namespace Tests\Unit\Api\V1;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * keyword・gender・category_id・date・per_pageが有効な場合、通過すること
     */
    public function test_valid_filters_pass_validation(): void
    {
        $category = Category::factory()->create();

        $data = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-01-15',
            'page' => 1,
            'per_page' => 50,
        ];

        $validator = Validator::make($data, (new IndexContactRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * 全て未指定でも通過すること
     */
    public function test_empty_filters_pass_validation(): void
    {
        $validator = Validator::make([], (new IndexContactRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * gender=0（Web版では「全て」を意味する値）はAPI版では許可されないこと
     */
    public function test_gender_zero_is_rejected(): void
    {
        $validator = Validator::make(['gender' => 0], (new IndexContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /**
     * 不正な性別値を拒否すること
     */
    public function test_invalid_gender_is_rejected(): void
    {
        $validator = Validator::make(['gender' => 9], (new IndexContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /**
     * 存在しないカテゴリIDを拒否すること
     */
    public function test_nonexistent_category_id_is_rejected(): void
    {
        $validator = Validator::make(['category_id' => 9999], (new IndexContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    /**
     * per_pageが100を超える場合、拒否されること
     */
    public function test_per_page_exceeding_max_is_rejected(): void
    {
        $validator = Validator::make(['per_page' => 101], (new IndexContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
    }
}