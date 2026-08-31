<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class ContactController extends Controller
{
    /**
     * お問い合わせ一覧を取得する（検索・ページネーション対応）
     */
    public function index(IndexContactRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $keyword = $validated['keyword'] ?? null;
        $gender = $validated['gender'] ?? null;
        $categoryId = $validated['category_id'] ?? null;
        $date = $validated['date'] ?? null;
        $perPage = $validated['per_page'] ?? 20;

        $query = Contact::with(['category', 'tags']);

        if ($keyword) {
            $escapedKeyword = addcslashes($keyword, '\\%_');

            $query->where(function ($q) use ($escapedKeyword) {
                $q->where('first_name', 'like', "%{$escapedKeyword}%")
                    ->orWhere('last_name', 'like', "%{$escapedKeyword}%")
                    ->orWhere('email', 'like', "%{$escapedKeyword}%");
            });
        }

        if ($gender) {
            $query->where('gender', $gender);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $contacts = $query->latest()->paginate($perPage)->withQueryString();

        return ContactResource::collection($contacts);
    }

    /**
     * 指定IDのお問い合わせ詳細（カテゴリ・タグ含む）を取得する
     */
    public function show(Contact $contact): ContactResource
    {
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    /**
     * お問い合わせを新規登録し、任意のタグを紐付ける
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $contact = Contact::create(Arr::except($validated, 'tag_ids'));

        if (! empty($validated['tag_ids'])) {
            $contact->tags()->attach($validated['tag_ids']);
        }

        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 既存のお問い合わせ内容を更新し、タグを同期する
     */
    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $validated = $request->validated();

        $contact->update(Arr::except($validated, 'tag_ids'));

        $contact->tags()->sync($validated['tag_ids'] ?? []);

        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    /**
     * 指定IDのお問い合わせおよび紐付くタグ関連を削除する
     */
    public function destroy(Contact $contact): Response
    {
        // contact_tagは外部キーのcascadeにより自動削除される
        $contact->delete();

        return response()->noContent();
    }
}