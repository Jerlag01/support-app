@props(['title' => null])

@php($company = helpdesk()->companyName())

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ __('support.meta.description', ['company' => $company]) }}">
        <meta name="robots" content="noindex">

        <title>{{ $title ? $title.' | ' : '' }}{{ __('support.meta.title', ['company' => $company]) }}</title>

        <link rel="icon" href="{{ app(\App\Support\HelpdeskSettings::class)->faviconUrl() }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>{!! helpdesk()->themeCss() !!}</style>
    </head>
    <body class="flex min-h-screen flex-col bg-steel font-sans text-navy-800 antialiased">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-accent focus:px-4 focus:py-2 focus:text-white">
            {{ __('support.nav.skip') }}
        </a>

        <x-site-header />

        <main id="main" class="flex-1 pt-[72px]">
            {{ $slot }}
        </main>

        <x-site-footer />
    </body>
</html>
