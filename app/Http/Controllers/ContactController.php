<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\View\View;
use App\Http\Requests\StoreContactRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;

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

      /**
     * お問い合わせ内容を保存し、サンクスページへリダイレクトする
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();
 
        $contact = Contact::create($validated);
 
        if (! empty($validated['tag_ids'])) {
            $contact->tags()->attach($validated['tag_ids']);
        }
 
        return redirect()->route('contact.thanks');
    }
 
    /**
     * サンクスページを表示する
     */
    public function thanks(): View
    {
        return view('contact.thanks');
    }


}