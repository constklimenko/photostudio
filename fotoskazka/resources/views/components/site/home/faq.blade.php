@props([
    'items',
    'title' => 'Часто задаваемые вопросы',
    'subtitle' => 'Ответы на популярные вопросы',
])

<x-site.faq :items="$items" :title="$title" :subtitle="$subtitle" />
