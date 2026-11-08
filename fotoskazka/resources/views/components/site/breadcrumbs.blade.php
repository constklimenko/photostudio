@props(['items' => [], 'center' => false])

@php
    $schemaItems = [];

    foreach ($items as $index => $item) {
        $schemaItems[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['label'],
            'item' => ! empty($item['url']) ? url($item['url']) : url()->current(),
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $schemaItems,
    ];
@endphp

@if (! empty($items))
    <nav class="text-sm text-gray-500 {{ $center ? 'flex justify-center' : '' }} mb-8" aria-label="Хлебные крошки">
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
            @foreach ($items as $item)
                <li class="flex items-center gap-2">
                    @unless ($loop->first)
                        <span class="text-gray-600 ml-0.5" aria-hidden="true">&bull;</span>
                    @endunless

                    @if (! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="hover:text-[#d4af37] transition">{{ $item['label'] }}</a>
                    @else
                        <span class="text-gray-300" aria-current="page">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <script type="application/ld+json">
        @json($schema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    </script>
@endif
