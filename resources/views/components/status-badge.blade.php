@props(['status'])

<span {{ $attributes->merge(['class' => 'badge '.$status->badgeClasses()]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $status->getLabel() }}
</span>
