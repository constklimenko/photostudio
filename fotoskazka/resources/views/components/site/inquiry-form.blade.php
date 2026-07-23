@props([
    'services' => null,
    'selectedServiceId' => null,
    'hiddenServiceId' => null,
    'buttonText' => 'Отправить заявку',
])

<form method="POST" action="{{ route('inquiry.store') }}" {{ $attributes->merge(['class' => 'mt-8 space-y-5']) }}>
    @csrf

    @if ($hiddenServiceId)
        <input type="hidden" name="service_id" value="{{ $hiddenServiceId }}">
    @endif

    <div>
        <input type="text" name="name" id="name" required placeholder="Имя"
               class="w-full border-b border-[#2a2a2a] bg-transparent px-2 py-3 text-sm text-white placeholder-gray-500 focus:border-[#d4af37] focus:ring-0 outline-none transition">
        @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <input type="tel" name="phone" id="phone" required placeholder="Телефон"
               class="w-full border-b border-[#2a2a2a] bg-transparent px-2 py-3 text-sm text-white placeholder-gray-500 focus:border-[#d4af37] focus:ring-0 outline-none transition">
        @error('phone') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <input type="email" name="email" id="email" required placeholder="Email"
               class="w-full border-b border-[#2a2a2a] bg-transparent px-2 py-3 text-sm text-white placeholder-gray-500 focus:border-[#d4af37] focus:ring-0 outline-none transition">
        @error('email') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <input type="date" name="shooting_date" id="shooting_date" min="{{ date('Y-m-d') }}"
               class="w-full border-b border-[#2a2a2a] bg-transparent px-2 py-3 text-sm text-white placeholder-gray-500 focus:border-[#d4af37] focus:ring-0 outline-none transition">
        @error('shooting_date') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    @if ($services && $services->isNotEmpty())
        <div>
            <select name="service_id" id="service_id"
                    class="w-full border-b border-[#2a2a2a] bg-transparent px-2 py-3 text-sm text-white focus:border-[#d4af37] focus:ring-0 outline-none transition">
                <option value="" class="bg-[#1a1a1a]">— Выберите услугу —</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" class="bg-[#1a1a1a]" @selected($selectedServiceId === $service->id)>{{ $service->title }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <textarea name="message" id="message" rows="3" placeholder="Комментарий"
                  class="w-full border-b border-[#2a2a2a] bg-transparent px-2 py-3 text-sm text-white placeholder-gray-500 focus:border-[#d4af37] focus:ring-0 outline-none transition resize-none"></textarea>
    </div>

    <div class="flex items-start gap-2">
        <input type="checkbox" name="agreed_to_terms" id="agreed_to_terms" required
               class="mt-1 rounded border-[#2a2a2a] bg-transparent text-[#d4af37] focus:ring-[#d4af37]">
        <label for="agreed_to_terms" class="text-sm text-gray-500">
            Согласен на обработку персональных данных
            @error('agreed_to_terms') <span class="text-red-400">{{ $message }}</span> @enderror
        </label>
    </div>

    <button type="submit"
            class="w-full px-8 py-3 bg-gold text-black font-semibold uppercase tracking-wider text-sm rounded-lg hover:opacity-90 transition">
        {{ $buttonText }}
    </button>
</form>
