<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\FooterLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A custom link shown in its own row in the portal footer, e.g. "Terms and conditions" or the main website.
 */
#[Fillable(['label', 'url', 'open_in_new_tab', 'is_visible', 'sort_order'])]
class FooterLink extends Model
{
    /** @use HasFactory<FooterLinkFactory> */
    use HasFactory, HasTranslations;

    /**
     * Web addresses (http/https), portal paths ("/kb"), e-mail and phone links.
     * Anything else (e.g. "javascript:") is refused, so a link can never run script.
     */
    public const URL_PATTERN = '/^(https?:\/\/[^\s]+|\/[^\s\/][^\s]*|\/|mailto:[^\s]+|tel:\+?[\d\s\-()]+)$/i';

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The visible links for the footer, in their admin order.
     *
     * @return Collection<int, self>
     */
    public static function forFooter(): Collection
    {
        return static::query()->where('is_visible', true)->ordered()->get();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'label' => 'array',
            'open_in_new_tab' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }
}
