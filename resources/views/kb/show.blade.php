<x-layouts.app :title="$faq->translate('question')">
    <x-page-hero :eyebrow="$faq->category->translate('name')" :title="$faq->translate('question')">
        <x-slot:actions>
            <a href="{{ route('kb.category', $faq->category) }}" class="btn btn-outline-light"><x-site-icon name="arrow-left" class="h-4 w-4" /> {{ $faq->category->translate('name') }}</a>
        </x-slot:actions>
    </x-page-hero>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8">
        <div class="space-y-6">
            <article class="card prose-content p-6 sm:p-8">{{ $faq->renderedAnswer() }}</article>
            <x-kb-downloads :faq="$faq" />
        </div>

        <aside class="space-y-6">
            @if ($related->isNotEmpty())
                <div class="card p-6">
                    <p class="eyebrow">{{ __('support.kb.related') }}</p>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach ($related as $item)
                            <li><a href="{{ route('kb.show', $item) }}" class="hover:text-accent-hover">{{ $item->translate('question') }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <p class="font-mono text-xs text-slate">{{ __('support.kb.updated', ['date' => $faq->updated_at->format('d/m/Y')]) }}</p>
        </aside>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8"><x-kb-cta /></div>
</x-layouts.app>
