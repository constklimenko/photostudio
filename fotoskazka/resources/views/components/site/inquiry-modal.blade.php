@props(['services' => null])

<div id="inquiry-modal" class="fixed inset-0 z-[1000] hidden" role="dialog" aria-modal="true" aria-labelledby="inquiry-modal-title">
    <div id="inquiry-modal-backdrop" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div id="inquiry-modal-panel" class="relative w-full md:w-[70%] bg-[#0a0a0a] border border-[#1a1a1a] rounded-3xl shadow-2xl shadow-black/50 overflow-hidden">
            <div class="flex items-center justify-between px-6 sm:px-8 pt-6 pb-2">
                <div class="flex-1 text-center">
                    <h2 id="inquiry-modal-title" class="font-heading text-2xl sm:text-3xl font-normal tracking-wide text-white">Оставить заявку</h2>
                    <p class="mt-1 text-sm text-gray-400">Заполните форму, и мы свяжемся с вами</p>
                </div>
                <button type="button" id="inquiry-modal-close" class="absolute top-5 right-5 p-2 text-gray-400 hover:text-white transition rounded-lg hover:bg-[#1a1a1a]" aria-label="Закрыть">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-6 sm:px-8 pb-8 pt-2">
                @if (session('success'))
                    <div id="inquiry-success" class="mb-5 p-4 bg-green-900/30 border border-green-800 text-green-400 rounded-lg text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <x-site.inquiry-form
                    :services="$services"
                    :hidden-service-id="null"
                    button-text="Отправить"
                />
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('inquiry-modal');
    const backdrop = document.getElementById('inquiry-modal-backdrop');
    const panel = document.getElementById('inquiry-modal-panel');
    const closeBtn = document.getElementById('inquiry-modal-close');

    if (!modal) return;

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-open-modal="inquiry"]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            openModal();
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    @if (session('success'))
        openModal();
        setTimeout(closeModal, 5000);
    @endif
})();
</script>
