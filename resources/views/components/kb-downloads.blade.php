@props(['faq'])

@if ($downloads = $faq->downloads())
    <div {{ $attributes->merge(['class' => 'card p-5']) }}>
        <p class="eyebrow">{{ __('support.kb.downloads') }}</p>
        <ul class="mt-3 grid gap-2 sm:grid-cols-2">
            @foreach ($downloads as $download)
                <li>
                    <a href="{{ $download['url'] }}" download="{{ $download['name'] }}" class="flex items-center gap-3 rounded border border-line px-3 py-2 text-sm transition hover:border-accent hover:text-accent-hover">
                        <x-site-icon name="paperclip" class="h-4 w-4 text-accent" />
                        <span class="min-w-0 flex-1 truncate">{{ $download['name'] }}</span>
                        <span class="font-mono text-xs text-slate">{{ $download['size'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
