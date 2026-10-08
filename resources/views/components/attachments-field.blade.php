@php
    use App\Http\Requests\AttachmentRules;
@endphp

<div {{ $attributes }}>
    <label for="attachments" class="mb-2 block text-sm font-semibold">{{ __('support.fields.attachments') }}</label>
    <label for="attachments" class="flex cursor-pointer items-center gap-3 rounded border border-dashed border-line bg-steel px-4 py-4 text-sm text-slate transition hover:border-accent">
        <x-site-icon name="paperclip" class="h-5 w-5 text-accent" />
        <span>{{ __('support.tickets.attachments_hint', ['count' => AttachmentRules::MAX_FILES, 'size' => AttachmentRules::MAX_KILOBYTES / 1024]) }}</span>
    </label>
    <input id="attachments" name="attachments[]" type="file" multiple data-file-input class="sr-only"
           accept="{{ collect(AttachmentRules::EXTENSIONS)->map(fn ($extension) => '.'.$extension)->implode(',') }}">
    <ul data-file-list="attachments" class="mt-2 space-y-1 font-mono text-xs text-slate"></ul>
    @error('attachments')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @foreach ($errors->get('attachments.*') as $messages)
        <p class="mt-2 text-sm text-red-600">{{ $messages[0] }}</p>
    @endforeach
</div>
