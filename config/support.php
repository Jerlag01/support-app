<?php

/*
|--------------------------------------------------------------------------
| Branding defaults
|--------------------------------------------------------------------------
|
| Starting values for a fresh install. Everything here can be changed in the
| admin panel (Settings > Organization / Appearance), so the same code base can
| serve any company. Translated values are keyed by locale.
|
*/

return [

    'branding' => [
        'company_name' => env('SUPPORT_COMPANY_NAME', 'Power Installation NV'),
        'tagline' => ['nl' => 'Klantenservice', 'fr' => 'Service client', 'en' => 'Customer support'],
        'main_site_url' => env('SUPPORT_MAIN_SITE_URL'),
        'founded' => 1998,
        'street' => 'Drukpersstraat 4',
        'postal_code' => '1000',
        'city' => ['nl' => 'Brussel', 'fr' => 'Bruxelles', 'en' => 'Brussels'],
        'phone' => env('SUPPORT_PHONE'),
        'email' => env('SUPPORT_EMAIL'),
        'vat_number' => env('SUPPORT_VAT_NUMBER'),
        'social' => [
            'instagram' => null,
            'facebook' => null,
            'linkedin' => null,
            'twitter' => null,
        ],
        'opening_hours' => [
            'nl' => 'Maandag - vrijdag: 7u30 - 17u00',
            'fr' => 'Lundi - vendredi : 7h30 - 17h00',
            'en' => 'Monday - Friday: 7:30 am - 5:00 pm',
        ],
        'about' => [
            'nl' => 'Sinds 1998 ontwerpt, installeert en onderhoudt Power Installation NV elektrische installaties voor bedrijven, overheden en particulieren. Via dit portaal volgen we je vragen en meldingen op.',
            'fr' => 'Depuis 1998, Power Installation NV conçoit, installe et entretient des installations électriques pour les entreprises, les pouvoirs publics et les particuliers. Ce portail nous permet de suivre vos questions et signalements.',
            'en' => 'Since 1998, Power Installation NV has designed, installed and maintained electrical installations for businesses, public authorities and private clients. This portal is where we follow up on your questions and reports.',
        ],
    ],

    'appearance' => [
        'primary' => '#002b45',
        'accent' => '#f7941d',
        'logo' => null,
        'favicon' => null,
        'hero_image' => null,
    ],

];
