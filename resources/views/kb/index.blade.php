<x-layouts.app :title="__('support.nav.kb')">
    <x-page-hero :eyebrow="__('support.kb.eyebrow')" :title="__('support.kb.title')">
        {{ __('support.kb.intro') }}
    </x-page-hero>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('kb.index') }}" class="relative max-w-2xl" role="search">
            <label for="search" class="sr-only">{{ __('support.kb.search') }}</label>
            <x-site-icon name="search" class="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-slate" />
            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="{{ __('support.kb.search') }}" class="form-input py-4 pl-12">
        </form>

        @if ($results !== null)
            <section class="mt-10" aria-labelledby="results-title">
                <h2 id="results-title" class="text-xl font-bold">{{ trans_choice('support.kb.results', $results->count(), ['search' => $search]) }}</h2>
                <ul class="card mt-4 divide-y divide-line">
                    @forelse ($results as $faq)
                        <li>
                            <a href="{{ route('kb.show', $faq) }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-steel">
                                <span>
                                    <span class="block font-semibold">{{ $faq->translate('question') }}</span>
                                    <span class="text-sm text-slate">{{ $faq->category->translate('name') }}</span>
                                </span>
                                <x-site-icon name="arrow-right" class="h-4 w-4 text-accent" />
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-slate">{{ __('support.kb.no_results') }}</li>
                    @endforelse
                </ul>
            </section>
        @endif

        <div class="mt-10 grid gap-6 lg:grid-cols-2">
            @forelse ($categories as $category)
                <section class="card p-6" data-reveal>
                    <div class="flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded bg-navy-800 text-accent"><x-site-icon name="book" class="h-5 w-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold"><a href="{{ route('kb.category', $category) }}" class="hover:text-accent-hover">{{ $category->translate('name') }}</a></h2>
                            @if (filled($category->translate('description')))
                                <p class="mt-1 text-sm text-slate">{{ $category->translate('description') }}</p>
                            @endif
                        </div>
                    </div>
                    <ul class="mt-5 space-y-2 border-t border-line pt-4">
                        @foreach ($category->publishedFaqs->take(5) as $faq)
                            <li><a href="{{ route('kb.show', $faq) }}" class="flex gap-2 text-sm hover:text-accent-hover"><x-site-icon name="chat" class="mt-0.5 h-4 w-4 text-accent" /> {{ $faq->translate('question') }}</a></li>
                        @endforeach
                    </ul>
                    @if ($category->publishedFaqs->count() > 5)
                        <a href="{{ route('kb.category', $category) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-accent-hover">{{ __('support.kb.all_articles', ['count' => $category->publishedFaqs->count()]) }} <x-site-icon name="arrow-right" class="h-4 w-4" /></a>
                    @endif
                </section>
            @empty
                <div class="card col-span-full flex flex-col items-center px-6 py-16 text-center">
                    <x-site-icon name="book" class="h-10 w-10 text-line" />
                    <p class="mt-4 font-semibold">{{ __('support.kb.empty') }}</p>
                </div>
            @endforelse
        </div>

        <x-kb-cta class="mt-12" />
    </div>
</x-layouts.app>
