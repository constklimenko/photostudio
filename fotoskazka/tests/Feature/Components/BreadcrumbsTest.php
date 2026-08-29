<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BreadcrumbsTest extends TestCase
{
    public function test_renders_trail_of_arbitrary_depth_in_order(): void
    {
        $items = [
            ['label' => 'Главная', 'url' => '/'],
            ['label' => 'Услуги', 'url' => '/services'],
            ['label' => 'Выпускные альбомы', 'url' => '/services/vypusknye-albomy'],
            ['label' => 'Для школ', 'url' => '/services/vypusknye-albomy/dlya-shkol'],
            ['label' => 'Классика'],
        ];

        $html = Blade::render('<x-site.breadcrumbs :items="$items" />', ['items' => $items]);

        $positions = array_map(fn (string $label) => strpos($html, $label), [
            'Главная', 'Услуги', 'Выпускные альбомы', 'Для школ', 'Классика',
        ]);

        $this->assertNotContains(false, $positions);

        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Элементы должны быть в заданном порядке');
    }

    public function test_renders_links_for_intermediate_items(): void
    {
        $html = Blade::render('<x-site.breadcrumbs :items="$items" />', [
            'items' => [
                ['label' => 'Главная', 'url' => '/'],
                ['label' => 'Услуги', 'url' => '/services'],
                ['label' => 'Классика'],
            ],
        ]);

        $this->assertStringContainsString('href="/"', $html);
        $this->assertStringContainsString('href="/services"', $html);
        $this->assertStringContainsString('>Услуги</a>', $html);
    }

    public function test_last_item_is_not_a_link(): void
    {
        $items = [
            ['label' => 'Главная', 'url' => '/'],
            ['label' => 'Услуги', 'url' => '/services'],
            ['label' => 'Выпускные альбомы'],
        ];

        $html = Blade::render('<x-site.breadcrumbs :items="$items" />', ['items' => $items]);

        $this->assertStringContainsString('aria-current="page">Выпускные альбомы</span>', $html);
        $this->assertStringNotContainsString('href="/services/vypusknye-albomy"', $html);
    }

    public function test_renders_separator_between_items(): void
    {
        $items = [
            ['label' => 'Главная', 'url' => '/'],
            ['label' => 'Услуги', 'url' => '/services'],
            ['label' => 'Выпускные альбомы', 'url' => '/services/vypusknye-albomy'],
            ['label' => 'Для школ'],
        ];

        $html = Blade::render('<x-site.breadcrumbs :items="$items" />', ['items' => $items]);

        $this->assertSame(3, substr_count($html, '&bull;'));
    }

    public function test_renders_nothing_when_items_empty(): void
    {
        $html = Blade::render('<x-site.breadcrumbs :items="[]" />');

        $this->assertSame('', trim($html));
    }

    public function test_center_variant_centers_nav(): void
    {
        $html = Blade::render('<x-site.breadcrumbs :center="true" :items="$items" />', [
            'items' => [
                ['label' => 'Главная', 'url' => '/'],
                ['label' => 'Услуги'],
            ],
        ]);

        $this->assertStringContainsString('flex justify-center', $html);
    }
}
