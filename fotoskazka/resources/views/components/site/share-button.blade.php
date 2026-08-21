@props([
    'url' => url()->current(),
    'title' => config('app.name'),
    'class' => '',
])

@php $id = 'share-' . md5($url . $title); @endphp

<button type="button"
        id="{{ $id }}"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-400 hover:text-[#d4af37] border border-[#2a2a2a] rounded-lg hover:border-[#d4af37] transition cursor-pointer {{ $class }}">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
    </svg>
    <span>Поделиться</span>
</button>

<script>
document.getElementById('{{ $id }}')?.addEventListener('click', function() {
    const url = '{{ $url }}';
    const title = '{{ $title }}';
    const span = this.querySelector('span');

    if (navigator.share) {
        navigator.share({ title, url }).catch(() => {});
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            span.textContent = 'Ссылка скопирована';
            setTimeout(() => { span.textContent = 'Поделиться'; }, 2000);
        }).catch(() => {
            window.open('https://vk.com/share.php?url=' + encodeURIComponent(url), '_blank', 'width=600,height=400');
        });
    } else {
        window.open('https://vk.com/share.php?url=' + encodeURIComponent(url), '_blank', 'width=600,height=400');
    }
});
</script>
