<footer class="bg-[#050505] text-gray-500 border-t border-[#1a1a1a]">
    @php
        $phone = \App\Services\ContactSettings::phone();
        $phoneHref = \App\Services\ContactSettings::phoneHref();
        $email = \App\Services\ContactSettings::email();
    @endphp
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="font-heading text-white font-semibold tracking-wide mb-3">{{ config('app.name') }}</h3>
                <p class="text-sm text-gray-500">Профессиональная фотосъёмка для ваших важных событий.</p>
            </div>

            <div>
                <h3 class="font-heading text-white font-semibold tracking-wide mb-3">Контакты</h3>
                <ul class="space-y-2 text-sm">
                    @if ($phone && $phoneHref)
                        <li>
                            <a href="{{ $phoneHref }}" class="hover:text-[#d4af37] transition">
                                {{ $phone }}
                            </a>
                        </li>
                    @endif
                    @if ($email)
                        <li>
                            <a href="mailto:{{ $email }}" class="hover:text-[#d4af37] transition">
                                {{ $email }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <div>
                <h3 class="font-heading text-white font-semibold tracking-wide mb-3">Информация</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('privacy') }}" class="hover:text-[#d4af37] transition">Политика конфиденциальности</a></li>
                </ul>
            </div>

            <x-site.social-links variant="compact" />
        </div>

        <div class="mt-12 pt-8 border-t border-[#1a1a1a] text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Все права защищены.
        </div>
    </div>
</footer>
