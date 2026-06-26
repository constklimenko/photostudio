<header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="text-xl font-semibold text-gray-900">
                {{ config('app.name') }}
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="/" class="hover:text-gray-900 transition">Главная</a>
                <a href="{{ route('services.index') }}" class="hover:text-gray-900 transition">Услуги</a>
                <a href="{{ route('portfolio.index') }}" class="hover:text-gray-900 transition">Портфолио</a>
                <a href="{{ route('blog.index') }}" class="hover:text-gray-900 transition">Блог</a>
                <a href="#inquiry-form" class="inline-block px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition">
                    Оставить заявку
                </a>
            </nav>

            <button type="button" id="menu-toggle" class="md:hidden p-2 text-gray-600" aria-label="Меню" aria-expanded="false">
                <svg id="menu-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <div id="mobile-menu" class="hidden md:hidden pb-4 border-t border-gray-100 pt-4">
            <nav class="flex flex-col gap-3 text-sm font-medium text-gray-600">
                <a href="/" class="hover:text-gray-900 transition px-2 py-1.5 rounded hover:bg-gray-50">Главная</a>
                <a href="{{ route('services.index') }}" class="hover:text-gray-900 transition px-2 py-1.5 rounded hover:bg-gray-50">Услуги</a>
                <a href="{{ route('portfolio.index') }}" class="hover:text-gray-900 transition px-2 py-1.5 rounded hover:bg-gray-50">Портфолио</a>
                <a href="{{ route('blog.index') }}" class="hover:text-gray-900 transition px-2 py-1.5 rounded hover:bg-gray-50">Блог</a>
                <a href="#inquiry-form" class="inline-block px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-center mt-2">
                    Оставить заявку
                </a>
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
