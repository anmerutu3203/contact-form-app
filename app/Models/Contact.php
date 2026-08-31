<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'contact_tag');
    }

    public function getContactsForAdminIndex(array $validated)
    {
        return $this->with(['category', 'tags'])
            ->when($validated['keyword'] ?? null, function ($query, $keyword) {
                // LIKE構文上のワイルドカード文字（\ % _）をエスケープし、
                // ユーザー入力をリテラル文字として扱う
                $escapedKeyword = addcslashes($keyword, '\\%_');

                $query->where(function ($q) use ($escapedKeyword) {
                    $q->where('first_name', 'like', "%{$escapedKeyword}%")
                        ->orWhere('last_name', 'like', "%{$escapedKeyword}%")
                        ->orWhere('email', 'like', "%{$escapedKeyword}%");
                });
            })
            ->when(
                isset($validated['gender']) && (int) $validated['gender'] !== 0,
                fn ($query) => $query->where('gender', $validated['gender'])
            )
            ->when($validated['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($validated['date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(7)
            ->withQueryString();
    }
}