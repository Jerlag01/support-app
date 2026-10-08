<x-layouts.app :title="$category->translate('name')">
    <x-page-hero :eyebrow="__('support.nav.kb')" :title="$category->translate('name')">
        {{ $category->translate('description') }}
        <x-slot:actions>
            <a href="{{ route('kb.index') }}" class="btn btn-outline-light"><x-site-icon name="arrow-left" class="h-4 w-4" /> {{ __('support.kb.back') }}</a>
        </x-slot:actions>
    </x-page-hero>

    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="space-y-3">
            @forelse ($faqs as $faq)
                <details class="card group overflow-hidden">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold transition hover:bg-steel">
                        {{ $faq->translate('question') }}
                        <x-site-icon name="plus" class="h-4 w-4 shrink-0 text-accent transition group-open:rotate-45" />
                    </summary>
                    <div class="prose-content border-t border-line px-5 py-5">{{ $faq->renderedAnswer() }}</div>
                    @if ($faq->attachments)
                        <x-kb-downloads :faq="$faq" class="mx-5 mb-4" />
                    @endif
                    <div class="px-5 pb-4"><a href="{{ route('kb.show', $faq) }}" class="text-sm font-semibold text-accent-hover hover:underline">{{ __('support.kb.permalink') }}</a></div>
                </details>
            @empty
                <p class="card px-5 py-8 text-center text-slate">{{ __('support.kb.empty') }}</p>
            @endforelse
        </div>

        <x-kb-cta class="mt-12" />
    </div>
</x-layouts.app>
