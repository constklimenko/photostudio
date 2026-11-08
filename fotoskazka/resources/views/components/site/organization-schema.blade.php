@php
    $phoneDigits = preg_replace('/\D+/', '', (string) \App\Services\ContactSettings::phone());
    $email = \App\Services\ContactSettings::email();
    $city = \App\Services\ContactSettings::city() ?: config('contacts.city');

    $sameAs = collect($socialLinks ?? [])
        ->filter(fn ($link) => in_array($link->icon, ['instagram', 'vk', 'telegram', 'whatsapp', 'viber', 'youtube'], true))
        ->pluck('url')
        ->filter()
        ->values()
        ->all();

    $organization = [
        '@type' => 'ProfessionalService',
        '@id' => url('/').'#organization',
        'name' => config('app.name'),
        'description' => 'Фотограф в Уфе — семейные, детские, индивидуальные и свадебные фотосессии, выпускные альбомы. С 2008 года.',
        'url' => url('/'),
        'image' => url('web-app-manifest-512x512.png'),
        'priceRange' => '₽₽',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => \App\Services\ContactSettings::address() ?: config('contacts.address'),
            'addressLocality' => $city,
            'addressCountry' => 'RU',
        ],
        'areaServed' => [
            '@type' => 'City',
            'name' => $city,
        ],
    ];

    if ($phoneDigits !== '') {
        $organization['telephone'] = '+'.$phoneDigits;
    }

    if ($email !== null) {
        $organization['email'] = $email;
    }

    if ($sameAs !== []) {
        $organization['sameAs'] = $sameAs;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            $organization,
            [
                '@type' => 'WebSite',
                'name' => config('app.name'),
                'url' => url('/'),
            ],
        ],
    ];
@endphp

<script type="application/ld+json">
    @json($schema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
</script>
