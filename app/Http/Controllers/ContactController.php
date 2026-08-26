<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\View\View;
use App\Http\Requests\StoreContactRequest;

class ContactController extends Controller
{
    /**
     * お問い合わせフォーム入力ページを表示する
     */
    public function index(): View
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(StoreContactRequest $request): View
    {
        $validated = $request->validated();
 
        $category = Category::find($validated['category_id']);
 
        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();
 
        return view('contact.confirm', [
            'validated' => $validated,
            'category' => $category,
            'tags' => $tags,
        ]);
    }
}