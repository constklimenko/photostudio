@component('mail::message')
# Новая заявка

**Имя:** {{ $inquiry->name }}

**Телефон:** {{ $inquiry->phone }}

**Email:** {{ $inquiry->email }}

**Услуга:** {{ $inquiry->service?->title ?? '—' }}

**Дата съёмки:** {{ $inquiry->shooting_date ?? '—' }}

**Комментарий:** {{ $inquiry->message ?? '—' }}

@component('mail::button', ['url' => url("/admin/inquiries/{$inquiry->id}/edit")])
Открыть заявку
@endcomponent

@endcomponent
