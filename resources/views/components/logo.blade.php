@if ($logoUrl = helpdesk()->logoUrl())
    <img src="{{ $logoUrl }}" alt="" {{ $attributes->merge(['style' => 'object-fit: contain;']) }}>
@else
    {{-- Default mark; follows the configured colours on the portal, falls back to the originals elsewhere. --}}
    <svg {{ $attributes }} viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <rect width="40" height="40" rx="4" style="fill: var(--color-navy-800, {{ helpdesk()->primaryColor() }})" />
        <path d="M22.5 6 11 22.5h8L16.5 34 29 16.5h-8.5L22.5 6Z" style="fill: var(--color-accent, {{ helpdesk()->accentColor() }})" />
    </svg>
@endif
