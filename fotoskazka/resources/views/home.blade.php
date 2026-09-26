@extends('layouts.site')

@section('title', $page?->seo_title ?: 'Фотосказка — профессиональная фотосъёмка')
@section('meta_description', $page?->seo_description ?: 'Профессиональная фотосъёмка для ваших важных событий. Услуги фотографа, портфолио, выпускные альбомы.')

@section('content')

    <x-site.hero
        :title="$page?->title ?: 'ФОТОСКАЗКА УФА'"
        :subtitle="$page?->subtitle ?: 'Выпускные альбомы под ключ в Уфе — красиво, вовремя, без стресса'"
        :buttons="$heroButtons"
        :images="$heroImages"
    />

    <x-site.social-links variant="section" :social-links="$socialLinks" />

    <x-site.home.featured-works
        :albums="$featuredWorks"
        :title="$featuredWorksBlock['title']"
        :subtitle="$featuredWorksBlock['subtitle']"
        :content="$featuredWorksBlock['content']"
    />

@endsection
