<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Setting;
use App\Models\SlaPlan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Helpdesk-wide settings managed in the admin panel (osTicket's Admin Panel > Settings).
 */
class HelpdeskSettings
{
    private const BEHAVIOUR_DEFAULTS = [
        'default_department_id' => null,
        'default_sla_plan_id' => null,
        'allow_registration' => true,
        'auto_assign_on_reply' => true,
        'clients_can_reopen' => true,
        'show_knowledge_base' => true,
        'show_who_is_who' => true,
        'mail' => [
            'mailer' => null,
            'host' => null,
            'port' => 587,
            'scheme' => null,
            'username' => null,
            'password' => null,
            'from_address' => null,
            'from_name' => null,
        ],
    ];

    private const CACHE_KEY = 'helpdesk-settings';

    /**
     * Every setting with its default; branding defaults come from config/support.php.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            ...self::BEHAVIOUR_DEFAULTS,
            'branding' => config('support.branding'),
            'appearance' => config('support.appearance'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $defaults = self::defaults();
        $stored = rescue(
            fn (): array => Cache::rememberForever(self::CACHE_KEY, fn (): array => Setting::pluck('value', 'key')->all()),
            [],
            report: false,
        );

        return array_replace_recursive($defaults, array_intersect_key($stored, $defaults));
    }

    /**
     * @param  string  $key  Top-level key or dot notation, e.g. "mail.host".
     */
    public function get(string $key): mixed
    {
        return data_get($this->all(), $key);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        foreach (array_intersect_key($values, self::defaults()) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function defaultDepartment(): ?Department
    {
        return Department::find($this->get('default_department_id')) ?? Department::orderBy('id')->first();
    }

    public function defaultSlaPlan(): ?SlaPlan
    {
        return SlaPlan::where('is_active', true)->find($this->get('default_sla_plan_id'));
    }

    /**
     * Override the mail configuration with the values set in the admin panel.
     * When no mailer is chosen there, the .env configuration stays in effect.
     */
    public function applyMailConfig(): void
    {
        // Mail templates show the app name in their header and footer.
        config(['app.name' => $this->companyName()]);

        $mail = $this->get('mail');

        if (blank($mail['mailer'] ?? null)) {
            return;
        }

        config(array_filter([
            'mail.default' => $mail['mailer'],
            'mail.mailers.smtp.host' => $mail['host'],
            'mail.mailers.smtp.port' => $mail['port'],
            'mail.mailers.smtp.username' => $mail['username'],
            'mail.mailers.smtp.password' => $this->decryptedMailPassword($mail['password']),
            'mail.mailers.smtp.scheme' => $mail['scheme'],
            'mail.from.address' => $mail['from_address'],
            'mail.from.name' => $mail['from_name'] ?: $this->companyName(),
        ], fn (mixed $value): bool => filled($value)));
    }

    public function companyName(): string
    {
        return (string) ($this->get('branding.company_name') ?: config('app.name'));
    }

    /**
     * A branding value in the visitor's language (falling back to the default locale), or a plain value as-is.
     */
    public function translated(string $key): ?string
    {
        $value = $this->get($key);

        if (! is_array($value)) {
            return filled($value) ? (string) $value : null;
        }

        return ($value[app()->getLocale()] ?? null) ?: (($value[config('app.locale')] ?? null) ?: null);
    }

    /**
     * Public URL of the uploaded favicon, or the default logo.
     */
    public function faviconUrl(): string
    {
        $favicon = $this->get('appearance.favicon');

        return filled($favicon) ? Storage::disk('public')->url($favicon) : asset('favicon.svg');
    }

    /**
     * Public URL of the uploaded logo, or null for the default lightning-bolt mark.
     */
    /**
     * Public URL of the homepage hero background, or null to keep the default navy background.
     */
    public function heroImageUrl(): ?string
    {
        $image = $this->get('appearance.hero_image');

        return filled($image) ? Storage::disk('public')->url($image) : null;
    }

    public function logoUrl(): ?string
    {
        $logo = $this->get('appearance.logo');

        return filled($logo) ? Storage::disk('public')->url($logo) : null;
    }

    public function primaryColor(): string
    {
        return $this->validHex($this->get('appearance.primary'), '#002b45');
    }

    public function accentColor(): string
    {
        return $this->validHex($this->get('appearance.accent'), '#f7941d');
    }

    /**
     * CSS custom properties that recolour the portal's Tailwind theme.
     */
    public function themeCss(): string
    {
        $primary = $this->primaryColor();
        $accent = $this->accentColor();

        return ':root{'
            ."--color-navy-700:color-mix(in srgb,{$primary} 82%,white);"
            ."--color-navy-800:{$primary};"
            ."--color-navy-900:color-mix(in srgb,{$primary} 70%,black);"
            ."--color-navy-950:color-mix(in srgb,{$primary} 45%,black);"
            ."--color-accent:{$accent};"
            ."--color-accent-hover:color-mix(in srgb,{$accent} 70%,black);"
            .'}';
    }

    private function validHex(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-f]{6}$/i', $value) ? $value : $fallback;
    }

    private function decryptedMailPassword(?string $encrypted): ?string
    {
        if (blank($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            // APP_KEY changed since the password was saved; it has to be entered again.
            return null;
        }
    }
}
