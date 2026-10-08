<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A person on the "who is who" page, with job title (per language), contact details and photo.
 */
#[Fillable(['name', 'job_title', 'email', 'phone', 'mobile', 'photo', 'is_visible', 'sort_order'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, HasTranslations;

    public const DISK = 'public';

    protected static function booted(): void
    {
        // The admin form does not remove replaced uploads itself, so clean up here.
        static::updated(function (Employee $employee): void {
            $old = $employee->getOriginal('photo');

            if (filled($old) && $old !== $employee->photo) {
                Storage::disk(self::DISK)->delete($old);
            }
        });

        static::deleted(function (Employee $employee): void {
            if (filled($employee->photo)) {
                Storage::disk(self::DISK)->delete($employee->photo);
            }
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * Whether the "who is who" page is shown: switched on in the settings and with at least one visible person.
     */
    public static function pageIsAvailable(): bool
    {
        return (bool) helpdesk()->get('show_who_is_who') && static::published()->exists();
    }

    /**
     * The uploaded photo, or null when the portal logo should be shown instead.
     */
    public function photoUrl(): ?string
    {
        return filled($this->photo) ? Storage::disk(self::DISK)->url($this->photo) : null;
    }

    /**
     * Phone number as a tel: link target, e.g. "+3241234567".
     */
    public static function telLink(?string $number): ?string
    {
        return filled($number) ? 'tel:'.preg_replace('/[^+\d]/', '', $number) : null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'job_title' => 'array',
            'is_visible' => 'boolean',
        ];
    }
}
