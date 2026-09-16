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
            'show_in_menu' => true,
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
            'show_in_menu' => true,
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
            'show_in_menu' => true,
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
            'show_in_menu' => true,
            'home_title' => 'Последние статьи',
            'home_subtitle' => 'Полезная информация из мира фотографии',
            'show_on_home' => true,
            'home_sort_order' => 40,
            'seo_title' => 'Блог — Фотосказка',
            'seo_description' => 'Полезные статьи о фотосъёмке, подготовке к выпускному и семейных фотосессиях.',
            'is_published' => true,
            'sort_order' => 3,
        ]);

        Page::updateOrCreate(['slug' => 'video'], [
            'title' => 'Видеогалерея',
            'subtitle' => 'Смотрите наши работы в движении',
            'menu_title' => 'Видеогалерея',
            'show_in_menu' => true,
            'show_on_home' => true,
            'home_sort_order' => 30,
            'seo_title' => 'Видеогалерея — Фотосказка',
            'seo_description' => 'Смотрите наши работы в формате видео.',
            'is_published' => true,
            'sort_order' => 4,
        ]);

        Page::updateOrCreate(['slug' => 'shooting'], [
            'title' => 'Фото со съёмок',
            'subtitle' => 'Загляните на съёмочную площадку',
            'menu_title' => 'Фото со съёмок',
            'show_in_menu' => false,
            'show_on_home' => true,
            'home_sort_order' => 25,
            'seo_title' => 'Фото со съёмок — Фотосказка',
            'seo_description' => 'Кадры со съёмочной площадки: как проходит работа фотографа.',
            'is_published' => true,
            'sort_order' => 5,
        ]);

        Page::updateOrCreate(['slug' => 'testimonials'], [
            'title' => 'Отзывы',
            'subtitle' => 'Что говорят наши клиенты',
            'menu_title' => 'Отзывы',
            'show_in_menu' => false,
            'show_on_home' => true,
            'home_sort_order' => 35,
            'seo_title' => 'Отзывы — Фотосказка',
            'seo_description' => 'Отзывы клиентов о работе Фотосказки.',
            'is_published' => true,
            'sort_order' => 6,
        ]);

        Page::updateOrCreate(['slug' => 'faq'], [
            'title' => 'Часто задаваемые вопросы',
            'subtitle' => 'Ответы на популярные вопросы',
            'menu_title' => 'Вопросы',
            'show_in_menu' => false,
            'show_on_home' => true,
            'home_sort_order' => 50,
            'seo_title' => 'Вопросы и ответы — Фотосказка',
            'seo_description' => 'Ответы на часто задаваемые вопросы о фотосъёмке и выпускных альбомах.',
            'is_published' => true,
            'sort_order' => 7,
        ]);

        Page::updateOrCreate(['slug' => 'inquiry'], [
            'title' => 'Оставить заявку',
            'subtitle' => 'Заполните форму, и мы свяжемся с вами',
            'menu_title' => 'Оставить заявку',
            'show_in_menu' => false,
            'show_on_home' => true,
            'home_sort_order' => 60,
            'seo_title' => 'Оставить заявку — Фотосказка',
            'seo_description' => 'Заявка на фотосъёмку: заполните форму, и мы свяжемся с вами.',
            'is_published' => true,
            'sort_order' => 8,
        ]);
    }
}
