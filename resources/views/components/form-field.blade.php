@props(['name', 'label', 'type' => 'text', 'required' => false, 'value' => null, 'hint' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-2 block text-sm font-semibold">
        {{ $label }} @if ($required)<span class="text-accent">*</span>@endif
    </label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $attributes->get('rows', 7) }}" @required($required) @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror {{ $attributes->except(['class', 'rows'])->merge(['class' => 'form-input']) }}>{{ old($name, $value) }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" @required($required) @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror {{ $attributes->except('class')->merge(['class' => 'form-input']) }}>
            {{ $slot }}
        </select>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password') value="{{ old($name, $value) }}" @endif @required($required) @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror {{ $attributes->except('class')->merge(['class' => 'form-input']) }}>
    @endif
    @if ($hint)
        <p class="mt-2 text-xs text-slate">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
