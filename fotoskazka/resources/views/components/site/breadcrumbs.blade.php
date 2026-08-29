@props(['items' => [], 'center' => false])

@if (! empty($items))
    <nav class="text-sm text-gray-500 {{ $center ? 'flex justify-center' : '' }} mb-8" aria-label="Хлебные крошки">
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
            @foreach ($items as $item)
                <li class="flex items-center gap-2">
                    @unless ($loop->first)
                        <span></span>
                        <span class="text-gray-600" aria-hidden="true">&bull;</span>
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
@endif
