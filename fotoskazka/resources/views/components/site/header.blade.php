<header class="sticky top-0 z-50 bg-[#0a0a0a]/95 backdrop-blur border-b border-[#1a1a1a]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="text-xl font-semibold text-white">
                {{ config('app.name') }}
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-300">
                @foreach ($menuItems as $item)
                    <a href="{{ $item['url'] }}" class="hover:text-white transition">{{ $item['title'] }}</a>
                @endforeach

                @auth
                    <a href="{{ route('cabinet.index') }}" class="hover:text-white transition">Личный кабинет</a>
                    @if (Auth::user()->isAdmin())
                        <a href="/admin" class="hover:text-[#d4af37] transition font-semibold">Админка</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-white transition cursor-pointer">Выйти</button>
                    </form>
                @else
                    <a href="#" data-open-modal="inquiry" class="inline-block px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg hover:opacity-90 transition">
                        Оставить заявку
                    </a>
                    <a href="{{ route('login') }}" class="hover:text-white transition">Войти</a>
                @endauth
            </nav>

            <button type="button" id="menu-toggle" class="md:hidden p-2 text-gray-300 hover:text-white" aria-label="Меню" aria-expanded="false">
                <svg id="menu-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <div id="mobile-menu" class="hidden md:hidden pb-4 border-t border-[#1a1a1a] pt-4">
            <nav class="flex flex-col gap-3 text-sm font-medium text-gray-300">
                @foreach ($menuItems as $item)
                    <a href="{{ $item['url'] }}" class="hover:text-white transition px-2 py-1.5 rounded hover:bg-[#1a1a1a]">{{ $item['title'] }}</a>
                @endforeach

                @auth
                    <a href="{{ route('cabinet.index') }}" class="hover:text-white transition px-2 py-1.5 rounded hover:bg-[#1a1a1a]">Личный кабинет</a>
                    @if (Auth::user()->isAdmin())
                        <a href="/admin" class="hover:text-[#d4af37] transition px-2 py-1.5 rounded hover:bg-[#1a1a1a] font-semibold">Админка</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-2 py-1.5 rounded hover:bg-[#1a1a1a] cursor-pointer hover:text-white">Выйти</button>
                    </form>
                @else
                    <a href="#" data-open-modal="inquiry" class="inline-block px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg hover:opacity-90 transition text-center mt-2">
                        Оставить заявку
                    </a>
                    <a href="{{ route('login') }}" class="hover:text-white transition px-2 py-1.5 rounded hover:bg-[#1a1a1a]">Войти</a>
                @endauth
            </nav>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('menu-toggle');
    const menu = document.getElementById('mobile-menu');
    const icon = document.getElementById('menu-icon');

    if (!toggle || !menu) return;

    toggle.addEventListener('click', () => {
        const isOpen = menu.classList.toggle('hidden');
        toggle.setAttribute('aria-expanded', !isOpen);

        if (isOpen) {
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>';
        } else {
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';
        }
    });
});
</script>
