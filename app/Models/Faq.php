<?php

namespace App\Models;

use App\Enums\FaqFormat;
use App\Models\Concerns\HasTranslations;
use Database\Factories\FaqFactory;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

#[Fillable(['faq_category_id', 'question', 'format', 'answer', 'attachments', 'attachment_names', 'is_published', 'sort_order'])]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory, HasTranslations;

    /**
     * Knowledge base files and images live on the public disk: the knowledge base itself is public.
     */
    public const DISK = 'public';

    protected $attributes = [
        'format' => 'rich',
    ];

    protected function casts(): array
    {
        return [
            'question' => 'array',
            'format' => FaqFormat::class,
            'answer' => 'array',
            'attachments' => 'array',
            'attachment_names' => 'array',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Filament doesn't delete files that are removed from an upload field.
        static::updated(function (Faq $faq): void {
            $removed = array_diff((array) $faq->getOriginal('attachments'), (array) $faq->attachments);
            Storage::disk(self::DISK)->delete(array_values($removed));
        });

        static::deleted(function (Faq $faq): void {
            Storage::disk(self::DISK)->delete((array) $faq->attachments);
        });
    }

    /**
     * @return BelongsTo<FaqCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    /**
     * Published articles in a public category.
     *
     * @param  Builder<Faq>  $query
     */
    public function scopeVisibleToClients(Builder $query): void
    {
        $query->where('is_published', true)->whereHas('category', fn (Builder $query) => $query->where('is_public', true));
    }

    /**
     * The answer as safe HTML in the visitor's language.
     */
    public function renderedAnswer(?string $locale = null): HtmlString
    {
        $content = $this->translate('answer', $locale);

        $html = match ($this->format) {
            // Raw HTML in Markdown is stripped; agents format with Markdown syntax only.
            FaqFormat::Markdown => Str::markdown($content, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
            // The renderer only outputs nodes the editor knows, so stray markup is dropped.
            FaqFormat::RichText => RichContentRenderer::make($content)
                ->fileAttachmentsDisk(self::DISK)
                ->fileAttachmentsVisibility('public')
                ->toHtml(),
        };

        return new HtmlString($html);
    }

    /**
     * Plain text of question and answer, for searching.
     */
    public function searchableText(?string $locale = null): string
    {
        return $this->translate('question', $locale).' '.strip_tags($this->renderedAnswer($locale)->toHtml());
    }

    /**
     * @return list<array{url: string, name: string, size: string}>
     */
    public function downloads(): array
    {
        $disk = Storage::disk(self::DISK);

        return collect((array) $this->attachments)
            ->filter(fn (string $path): bool => $disk->exists($path))
            ->map(fn (string $path): array => [
                'url' => $disk->url($path),
                'name' => $this->attachment_names[$path] ?? basename($path),
                'size' => Number::fileSize($disk->size($path), precision: 1),
            ])
            ->values()
            ->all();
    }
}
