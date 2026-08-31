<?php

namespace Tests\Unit;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正しいフィルタ条件（keyword/gender/category_id/date）を受け付けること
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

        $validator = Validator::make($data, (new ExportContactRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * フィルタが全て未指定でもバリデーションを通過すること（全て任意項目のため）
     */
    public function test_empty_filters_pass_validation(): void
    {
        $validator = Validator::make([], (new ExportContactRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * gender=0（全件対象を意味する特別な値）がバリデーションを通過すること
     *
     * ExportContactRequestのin:0,1,2,3ルールと、コントローラー側の
     * 「gender=0は絞り込まない」というロジックが噛み合っていることを保証する。
     * 将来ここがin:1,2,3に変更されると、コントローラーの分岐が到達不能になる。
     */
    public function test_gender_zero_passes_validation(): void
    {
        $validator = Validator::make(['gender' => 0], (new ExportContactRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * 不正な性別値（0,1,2,3以外）を拒否すること
     */
    public function test_invalid_gender_is_rejected(): void
    {
        $data = ['gender' => 9];

        $validator = Validator::make($data, (new ExportContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /**
     * 存在しないカテゴリIDを拒否すること
     */
    public function test_nonexistent_category_id_is_rejected(): void
    {
        $data = ['category_id' => 9999];

        $validator = Validator::make($data, (new ExportContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    /**
     * 不正な日付形式を拒否すること
     */
    public function test_invalid_date_format_is_rejected(): void
    {
        $data = ['date' => 'not-a-date'];

        $validator = Validator::make($data, (new ExportContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }
}