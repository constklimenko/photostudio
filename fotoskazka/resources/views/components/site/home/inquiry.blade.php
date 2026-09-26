@props([
    'services' => null,
    'title' => 'Оставить заявку',
    'subtitle' => 'Заполните форму, и мы свяжемся с вами',
])

<section id="inquiry-form" class="py-24" data-aos="fade-up">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-heading text-3xl font-normal tracking-wide text-white text-center">{{ $title }}</h2>
        <p class="mt-3 text-gray-400 text-center">{{ $subtitle }}</p>

        @if (session('success'))
            <div class="mt-6 p-4 bg-green-900/30 border border-green-800 text-green-400 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-site.inquiry-form
            :services="$services"
            button-text="Отправить"
        />
    </div>
</section>
