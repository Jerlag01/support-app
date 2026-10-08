<x-layouts.app :title="__('support.nav.new_ticket')">
    <x-page-hero :eyebrow="__('support.tickets.eyebrow')" :title="__('support.tickets.create_title')">
        {{ __('support.tickets.create_intro') }}
    </x-page-hero>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="card space-y-6 p-6 sm:p-8" data-submit-once>
            @csrf

            <x-form-field name="subject" :label="__('support.fields.subject')" required maxlength="150" autofocus />

            <div class="grid gap-6 sm:grid-cols-2">
                <x-form-field name="help_topic_id" type="select" :label="__('support.fields.help_topic_id')" required>
                    <option value="" disabled @selected(! old('help_topic_id', request('topic')))>{{ __('support.tickets.choose') }}</option>
                    @foreach ($topics as $topic)
                        <option value="{{ $topic->id }}" @selected((string) old('help_topic_id', request('topic')) === (string) $topic->id)>{{ $topic->label() }}</option>
                    @endforeach
                </x-form-field>

                <x-form-field name="priority" type="select" :label="__('support.fields.priority')" required>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}" @selected(old('priority', request('priority', 'normal')) === $priority->value)>{{ $priority->getLabel() }}</option>
                    @endforeach
                </x-form-field>
            </div>

            <x-form-field name="site_address" :label="__('support.fields.site_address')" :hint="__('support.tickets.site_address_hint')" maxlength="255" />
            <x-form-field name="message" type="textarea" :label="__('support.fields.message')" :placeholder="__('support.tickets.message_placeholder')" required maxlength="10000" />
            <x-attachments-field />

            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line pt-6">
                <a href="{{ route('tickets.index') }}" class="btn btn-ghost px-0">
                    <x-site-icon name="arrow-left" class="h-4 w-4" /> {{ __('support.tickets.back') }}
                </a>
                <button type="submit" class="btn btn-primary">{{ __('support.tickets.submit') }} <x-site-icon name="arrow-right" class="h-4 w-4" /></button>
            </div>
        </form>

        <aside class="space-y-6">
            <div class="card p-6">
                <p class="eyebrow">{{ __('support.tickets.tips_title') }}</p>
                <ul class="mt-4 space-y-3 text-sm text-slate">
                    @foreach (__('support.tickets.tips') as $tip)
                        <li class="flex gap-3"><x-site-icon name="check" class="mt-0.5 h-4 w-4 text-accent" /> {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-lg bg-navy-800 p-6 text-white">
                <p class="eyebrow">{{ __('support.priorities.urgent') }}</p>
                <p class="mt-3 text-sm text-white/75">{{ __('support.home.urgent_text') }}</p>
                @if (filled(helpdesk()->get('branding.phone')))
                    <a href="tel:{{ preg_replace('/[^+\d]/', '', helpdesk()->get('branding.phone')) }}" class="mt-4 inline-flex items-center gap-2 font-semibold text-accent">
                        <x-site-icon name="phone" class="h-4 w-4" /> {{ helpdesk()->get('branding.phone') }}
                    </a>
                @endif
            </div>
        </aside>
    </div>
</x-layouts.app>
