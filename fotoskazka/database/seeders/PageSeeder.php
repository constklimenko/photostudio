<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(['slug' => 'home'], [
            'title' => 'Фотосказка — профессиональная фотосъёмка',
            'subtitle' => 'Выпускные альбомы под ключ в Уфе — красиво, вовремя, без стресса',
            'menu_title' => 'Главная',
            'show_on_home' => false,
            'home_sort_order' => 0,
            'seo_title' => 'Фотосказка — профессиональная фотосъёмка',
            'seo_description' => 'Профессиональная фотосъёмка для ваших важных событий. Услуги фотографа, портфолио, выпускные альбомы.',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        Page::updateOrCreate(['slug' => 'services'], [
            'title' => 'Наши услуги',
            'subtitle' => 'Профессиональная фотосъёмка для любых событий. Выберите подходящий формат.',
            'menu_title' => 'Услуги',
            'home_title' => 'Наши услуги',
            'home_subtitle' => 'Выберите подходящий формат съёмки',
            'show_on_home' => true,
            'home_sort_order' => 10,
            'seo_title' => 'Услуги — Фотосказка',
            'seo_description' => 'Профессиональная фотосъёмка для выпускных альбомов, детских садов, школ, семейных и индивидуальных фотосессий, мероприятий и свадеб.',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        Page::updateOrCreate(['slug' => 'portfolio'], [
            'title' => 'Портфолио',
            'subtitle' => 'Избранные проекты, которые рассказывают истории',
            'menu_title' => 'Портфолио',
            'home_title' => 'Избранные работы',
            'home_subtitle' => 'Наши лучшие проекты',
            'show_on_home' => true,
            'home_sort_order' => 20,
            'seo_title' => 'Портфолио — Фотосказка',
            'seo_description' => 'Фотопортфолио профессионального фотографа. Свадебные, семейные, индивидуальные фотосессии и выпускные альбомы.',
            'is_published' => true,
            'sort_order' => 2,
        ]);

        Page::updateOrCreate(['slug' => 'blog'], [
            'title' => 'Блог',
            'subtitle' => 'Полезные статьи, советы и новости из мира фотографии',
            'menu_title' => 'Блог',
            'home_title' => 'Последние статьи',
            'home_subtitle' => 'Полезная информация из мира фотографии',
            'show_on_home' => true,
            'home_sort_order' => 40,
            'seo_title' => 'Блог — Фотосказка',
            'seo_description' => 'Полезные статьи о фотосъёмке, подготовке к выпускному и семейных фотосессиях.',
            'is_published' => true,
            'sort_order' => 3,
        ]);
    }
}
