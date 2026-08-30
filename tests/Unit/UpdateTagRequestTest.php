<?php

namespace Tests\Unit;

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTagRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ルートパラメータ {tag} をバインドしたUpdateTagRequestインスタンスを作る
     */
    private function makeRequest(Tag $tag): UpdateTagRequest
    {
        $request = new UpdateTagRequest();
        $route = new Route('PUT', '/admin/tags/{tag}', []);
        $route->bind($request);
        $route->setParameter('tag', $tag);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    /**
     * 自身の現在の名前のまま更新する場合、バリデーションを通過すること
     */
    public function test_keeping_own_current_name_passes_validation(): void
    {
        $tag = Tag::factory()->create();

        $request = $this->makeRequest($tag);

        $validator = Validator::make(['name' => $tag->name], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * 他のタグで既に使用されている名前へ変更する場合、拒否されること
     */
    public function test_changing_to_another_tags_name_is_rejected(): void
    {
        $tag = Tag::factory()->create();
        $otherTag = Tag::factory()->create();

        $request = $this->makeRequest($tag);

        $validator = Validator::make(['name' => $otherTag->name], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * タグ名が未入力の場合、拒否されること
     */
    public function test_empty_name_is_rejected(): void
    {
        $tag = Tag::factory()->create();

        $request = $this->makeRequest($tag);

        $validator = Validator::make(['name' => ''], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}