@if (session('status'))
    <div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900']) }} role="status">
        <x-site-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-600" />
        <span>{{ session('status') }}</span>
    </div>
@endif
