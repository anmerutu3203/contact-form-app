<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\View\View;

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

    /**
     * 入力内容をバリデーションし、確認ページを表示する
     */
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

        $contact = Contact::create(Arr::except($validated, 'tag_ids'));

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

    /**
     * 検索条件に一致するお問い合わせをBOM付きCSVでエクスポートする
     */
    public function export(ExportContactRequest $request): Response
    {
        $validated = $request->validated();

        $keyword = $validated['keyword'] ?? null;
        $gender = $validated['gender'] ?? null;
        $categoryId = $validated['category_id'] ?? null;
        $date = $validated['date'] ?? null;

        $query = Contact::with('category');

        if ($keyword) {
            $escapedKeyword = addcslashes($keyword, '\\%_');
            $query->where(function ($q) use ($escapedKeyword) {
                $q->where('first_name', 'like', "%{$escapedKeyword}%")
                    ->orWhere('last_name', 'like', "%{$escapedKeyword}%")
                    ->orWhere('email', 'like', "%{$escapedKeyword}%");
            });
        }

        // gender=0（またはgender未指定）は「全て」を意味するので絞り込まない
        if ($gender !== null && $gender !== '' && (int) $gender !== 0) {
            $query->where('gender', $gender);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $contacts = $query->latest()->get();

        $genderLabels = [1 => '男性', 2 => '女性', 3 => 'その他'];

        // php://temp上にCSVを組み立てる（fputcsvにRFC4180準拠のエスケープを任せる）
        $stream = fopen('php://temp', 'r+');

        // BOM（UTF-8であることをExcel等に伝えるための先頭バイト列）
        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, ['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時']);

        foreach ($contacts as $contact) {
            fputcsv($stream, [
                $contact->id,
                $contact->last_name.' '.$contact->first_name,
                $genderLabels[$contact->gender] ?? '',
                $contact->email,
                $contact->tel,
                $contact->address,
                $contact->building ?? '',
                $contact->category->content ?? '',
                // 1レコード1行で見やすくするため、内容中の改行はスペースに変換する
                // （fputcsvのクォート処理により改行を含めたまま出力することも可能だが、
                //   表計算ソフトで開いた際の見た目を優先しこの方針とした）
                str_replace(["\r\n", "\r", "\n"], ' ', $contact->detail),
                $contact->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $fileName = 'contacts_'.now()->format('YmdHis').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}