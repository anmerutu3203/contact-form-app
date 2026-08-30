<?php

namespace Tests\Unit;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 全ての必須項目とタグ入力を満たしている場合、バリデーションを通過すること
     */
    public function test_valid_data_with_tags_passes_validation(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $data = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'category_id' => $category->id,
            'detail' => 'テストお問い合わせ内容',
            'tag_ids' => [$tag->id],
        ];

        $validator = Validator::make($data, (new StoreContactRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * 必須項目が未入力の場合、バリデーションエラーになること
     */
    public function test_missing_required_fields_are_rejected(): void
    {
        $validator = Validator::make([], (new StoreContactRequest())->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();

        foreach (['first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'category_id', 'detail'] as $field) {
            $this->assertArrayHasKey($field, $errors);
        }
    }

    /**
     * 不正な電話番号形式（ハイフンあり・桁数不正）を拒否すること
     */
    public function test_invalid_tel_format_is_rejected(): void
    {
        $category = Category::factory()->create();

        $baseData = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'address' => '東京都渋谷区1-1-1',
            'category_id' => $category->id,
            'detail' => 'テストお問い合わせ内容',
        ];

        // ハイフン付き
        $validator = Validator::make(
            [...$baseData, 'tel' => '090-1234-5678'],
            (new StoreContactRequest())->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tel', $validator->errors()->toArray());

        // 桁数不足
        $validator = Validator::make(
            [...$baseData, 'tel' => '090123'],
            (new StoreContactRequest())->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tel', $validator->errors()->toArray());
    }
}