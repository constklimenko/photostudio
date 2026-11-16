<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_route_renders_site_404_page(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('Страница не найдена')
            ->assertSee(route('home'), false)
            ->assertSee(route('services.index'), false)
            ->assertSee(route('portfolio.index'), false)
            ->assertDontSee('Not Found');
    }

    public function test_404_page_uses_site_layout(): void
    {
        $this->get('/another-missing-page')
            ->assertNotFound()
            ->assertSee('<header', false)
            ->assertSee('name="description"', false)
            ->assertSee('Все права защищены');
    }
}
