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
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
        <input type="text" name="name" id="name" required
               class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
        <input type="tel" name="phone" id="phone" required
               class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" id="email" required
               class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="shooting_date" class="block text-sm font-medium text-gray-700 mb-1">Желаемая дата съёмки</label>
        <input type="date" name="shooting_date" id="shooting_date" min="{{ date('Y-m-d') }}"
               class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
        @error('shooting_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    @if ($services && $services->isNotEmpty())
        <div>
            <label for="service_id" class="block text-sm font-medium text-gray-700 mb-1">Услуга</label>
            <select name="service_id" id="service_id"
                    class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500">
                <option value="">— Выберите услугу —</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected($selectedServiceId === $service->id)>{{ $service->title }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Комментарий</label>
        <textarea name="message" id="message" rows="4"
                  class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm focus:border-amber-500 focus:ring-amber-500"></textarea>
    </div>

    <div class="flex items-start gap-2">
        <input type="checkbox" name="agreed_to_terms" id="agreed_to_terms" required
               class="mt-1 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
        <label for="agreed_to_terms" class="text-sm text-gray-500">
            Согласен на обработку персональных данных
            @error('agreed_to_terms') <span class="text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <button type="submit"
            class="w-full px-6 py-3 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition">
        {{ $buttonText }}
    </button>
</form>
