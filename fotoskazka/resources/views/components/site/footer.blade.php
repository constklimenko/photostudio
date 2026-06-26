<footer class="bg-gray-900 text-gray-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-white font-semibold mb-3">{{ config('app.name') }}</h3>
                <p class="text-sm text-gray-400">Профессиональная фотосъёмка для ваших важных событий.</p>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-3">Контакты</h3>
                <ul class="space-y-2 text-sm">
                    @if (config('contacts.phone'))
                        <li>
                            <a href="tel:{{ config('contacts.phone') }}" class="hover:text-white transition">
                                {{ config('contacts.phone') }}
                            </a>
                        </li>
                    @endif
                    @if (config('contacts.email'))
                        <li>
                            <a href="mailto:{{ config('contacts.email') }}" class="hover:text-white transition">
                                {{ config('contacts.email') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-3">Информация</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="hover:text-white transition">Политика конфиденциальности</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Все права защищены.
        </div>
    </div>
</footer>
