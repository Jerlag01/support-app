<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\FaqCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'is_public', 'sort_order'])]
class FaqCategory extends Model
{
    /** @use HasFactory<FaqCategoryFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Faq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<Faq, $this>
     */
    public function publishedFaqs(): HasMany
    {
        return $this->faqs()->where('is_published', true);
    }

    /**
     * @param  Builder<FaqCategory>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->where('is_public', true)->orderBy('sort_order')->orderBy('id');
    }
}
